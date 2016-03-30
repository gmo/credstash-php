<?php

namespace CredStash;

use Aws\Kms\KmsClient;
use CredStash\Exception\IntegrityException;
use CredStash\Exception\RuntimeException;
use CredStash\Store\StoreInterface;

/**
 * The CredStash.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class CredStash implements CredStashInterface
{
    /** @var StoreInterface */
    protected $store;
    /** @var KmsClient */
    protected $kms;
    /** @var string */
    protected $kmsKey;

    /**
     * Constructor.
     *
     * @param StoreInterface $store
     * @param KmsClient      $kms
     * @param string         $kmsKey
     */
    public function __construct(StoreInterface $store, KmsClient $kms, $kmsKey = 'alias/credstash')
    {
        $this->store = $store;
        $this->kms = $kms;
        $this->kmsKey = $kmsKey;
    }

    /**
     * {@inheritdoc}
     */
    public function listCredentials()
    {
        $credentials = $this->store->listCredentials();
        $credentials = array_map('intval', $credentials);

        return $credentials;
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
        if ($version === null) {
            $credential = $this->store->get($name);
        } else {
            $credential = $this->store->getAtVersion($name, $this->paddedInt($version));
        }

        // Check the HMAC before we decrypt to verify ciphertext integrity
        $response = $this->kms->decrypt([
            'KeyId'             => $this->kmsKey,
            'CiphertextBlob'    => base64_decode($credential->getKey()),
            'EncryptionContext' => $context,
        ]);

        $contents = base64_decode($credential->getContents());
        $key = substr($response['Plaintext'], 0, 32);
        $hmacKey = substr($response['Plaintext'], 32);

        $hmac = hash_hmac('sha256', $contents, $hmacKey);
        if (!hash_equals($hmac, $credential->getHmac())) {
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

        $credential = (new Credential())
            ->setName($name)
            ->setVersion($version)
            ->setKey($key)
            ->setContents($contents)
            ->setHmac($hmac)
        ;

        $this->store->put($credential);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($name)
    {
        $this->store->delete($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHighestVersion($name)
    {
        return (int) $this->store->getHighestVersion($name);
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
