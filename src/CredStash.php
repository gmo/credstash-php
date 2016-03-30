<?php

namespace CredStash;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\Kms\KmsClient;
use CredStash\Exception\CredentialNotFoundException;
use CredStash\Exception\DuplicateCredentialVersionException;
use CredStash\Exception\IntegrityException;
use CredStash\Exception\RuntimeException;

/**
 * The CredStash.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class CredStash implements CredStashInterface
{
    /** @var DynamoDbClient */
    protected $db;
    /** @var KmsClient */
    protected $kms;
    /** @var string */
    protected $kmsKey;
    /** @var string */
    protected $table;

    /**
     * Constructor.
     *
     * @param DynamoDbClient $db
     * @param KmsClient      $kms
     * @param string         $kmsKey
     * @param string         $table
     */
    public function __construct(DynamoDbClient $db, KmsClient $kms, $kmsKey = 'alias/credstash', $table = 'credential-store')
    {
        $this->db = $db;
        $this->kms = $kms;
        $this->kmsKey = $kmsKey;
        $this->table = $table;
    }

    /**
     * {@inheritdoc}
     */
    public function listCredentials()
    {
        $response = $this->db->scan([
            'TableName' => $this->table,
            'ProjectionExpression' => '#N, version',
            'ExpressionAttributeNames' => [
                '#N' => 'name',
            ],
        ]);

        if ($response['Count'] === 0) {
            return [];
        }

        $result = [];
        foreach ($response['Items'] as $item) {
            $result[$item['name']['S']] = (int) $item['version']['S'];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($context = [], $version = null)
    {
        $result = [];

        $credentials = $this->listCredentials();
        foreach ($credentials as $name => $credentialVersion) {
            $result[$name] = $this->get($name, $context, $version !== null ? $version : $credentialVersion);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name, $context = [], $version = null)
    {
        $item = $this->getItem($name, $version);

        // Check the HMAC before we decrypt to verify ciphertext integrity
        $response = $this->kms->decrypt([
            'KeyId'             => $this->kmsKey,
            'CiphertextBlob'    => base64_decode($item['key']),
            'EncryptionContext' => $context,
        ]);

        $contents = base64_decode($item['contents']);
        $key = substr($response['Plaintext'], 0, 32);
        $hmacKey = substr($response['Plaintext'], 32);

        $hmac = hash_hmac('sha256', $contents, $hmacKey);
        if (!hash_equals($hmac, $item['hmac'])) {
            throw new IntegrityException(sprintf('Computed HMAC on %s does not match stored HMAC', $name));
        }

        return $this->decrypt($contents, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function put($name, $secret, $context = [], $version = null)
    {
        // Generate a 64 byte key
        // Half will be for data encryption, the other half for HMAC
        $response = $this->kms->generateDataKey([
            'KeyId'             => $this->kmsKey,
            'EncryptionContext' => $context,
            'NumberOfBytes'     => 64,
        ]);

        $dataKey = substr($response['Plaintext'], 0, 32);
        $hmacKey = substr($response['Plaintext'], 32);
        $wrappedKey = $response['CiphertextBlob'];

        $cText = $this->encrypt($secret, $dataKey);

        // Compute HMAC using the hmac key and the ciphertext
        $hmac = hash_hmac('sha256', $cText, $hmacKey);

        if ($version === null) {
            $version = $this->getHighestVersion($name) + 1;
        }
        $version = $this->paddedInt($version);

        $key = base64_encode($wrappedKey);
        $contents = base64_encode($cText);

        $this->putItem([
            'name'     => $name,
            'version'  => $version,
            'key'      => $key,
            'contents' => $contents,
            'hmac'     => $hmac,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($name)
    {
        $response = $this->db->scan([
            'TableName'                 => $this->table,
            'FilterExpression'          => '#N = :name',
            'ProjectionExpression'      => '#N, version',
            'ExpressionAttributeNames'  => [
                '#N' => 'name',
            ],
            'ExpressionAttributeValues' => [
                ':name' => ['S' => $name],
            ],
        ]);

        foreach ($response['Items'] as $item) {
            $this->db->deleteItem([
                'TableName' => $this->table,
                'Key' => $item,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHighestVersion($name)
    {
        $params = [
            'TableName'                 => $this->table,
            'Limit'                     => 1,
            'ScanIndexForward'          => false,
            'ConsistentRead'            => true,
            'KeyConditionExpression'    => '#N = :name',
            'ExpressionAttributeNames'  => [
                '#N' => 'name',
            ],
            'ExpressionAttributeValues' => [
                ':name' => ['S' => $name],
            ],
            'ProjectionExpression'      => 'version',
        ];

        $response = $this->db->query($params);

        if ($response['Count'] === 0) {
            return 0;
        }

        return (int) $response['Items'][0]['version']['S'];
    }

    /**
     * Puts the item in the DB if it does not already exist.
     *
     * @param array $item
     */
    private function putItem($item)
    {
        $item = array_map(
            function ($prop) {
                return ['S' => $prop];
            },
            $item
        );

        $params = [
            'TableName'                => $this->table,
            'Item'                     => $item,
            'ConditionExpression'      => 'attribute_not_exists(#N)',
            'ExpressionAttributeNames' => [
                '#N' => 'name',
            ],
        ];

        try {
            $this->db->putItem($params);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === 'ConditionalCheckFailedException') {
                throw new DuplicateCredentialVersionException($item['name']['S'], $item['version']['S']);
            }

            throw $e;
        }
    }

    /**
     * Fetches the item for the given key from DB with
     * the given version or the latest version.
     *
     * @param string          $name
     * @param int|string|null $version
     *
     * @return array
     */
    private function getItem($name, $version = null)
    {
        if ($version === null) {
            $response = $this->db->query([
                'TableName'                 => $this->table,
                'Limit'                     => 1,
                'ScanIndexForward'          => false,
                'ConsistentRead'            => true,
                'KeyConditionExpression'    => '#N = :name',
                'ExpressionAttributeNames'  => [
                    '#N' => 'name',
                ],
                'ExpressionAttributeValues' => [
                    ':name' => ['S' => $name],
                ],
            ]);
            if ($response['Count'] === 0) {
                throw new CredentialNotFoundException($name);
            }
            $item = $response['Items'][0];
        } else {
            $response = $this->db->getItem([
                'TableName' => $this->table,
                'Key'       => [
                    'name'    => $name,
                    'version' => $this->paddedInt($version),
                ],
            ]);
            $item = $response['Item'];
        }

        return array_map(function ($prop) {
            return $prop['S'];
        }, $item);
    }

    /**
     * Left pads an integer to 19 digits.
     *
     * This ensures version sorting is consistent regardless of length.
     *
     * @param int|string $int
     *
     * @return string
     */
    private function paddedInt($int)
    {
        return str_pad($int, 19, '0', STR_PAD_LEFT);
    }

    /**
     * Encrypts data.
     *
     * @param string $contents
     * @param string $key
     *
     * @return string
     */
    private function encrypt($contents, $key)
    {
        $result = openssl_encrypt($contents, 'aes-256-ctr', $key, true, $this->getCounter());
        if ($result === false) {
            throw new RuntimeException('Failed to encrypt');
        }

        return $result;
    }

    /**
     * Decrypts data.
     *
     * @param string $contents
     * @param string $key
     *
     * @return string
     */
    private function decrypt($contents, $key)
    {
        $result = openssl_decrypt($contents, 'aes-256-ctr', $key, true, $this->getCounter());
        if ($result === false) {
            throw new RuntimeException('Failed to decrypt secret contents');
        }

        return $result;
    }

    /**
     * Creates a counter value for AES in CTR mode.
     *
     * Equivalent of python code: `Cyrpto.Util.Counter.new(128, initial_value = 1)`
     *
     * Taken from: {@see http://stackoverflow.com/a/32590050}
     *
     * @param int $initialValue
     *
     * @return string
     */
    private function getCounter($initialValue = 1)
    {
        // int to byte array
        $b = array_reverse(unpack('C*', pack('L', $initialValue)));
        // byte array to string
        $ctrStr = implode(array_map('chr', $b));
        // create 16 byte IV from counter
        $ctrVal = str_repeat("\x0", 12) . $ctrStr;

        return $ctrVal;
    }
}
