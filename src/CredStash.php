<?php

namespace CredStash;

use Aws\Sdk;
use CredStash\Encryption\EncryptionInterface;
use CredStash\Encryption\KmsEncryption;
use CredStash\Store\DynamoDbStore;
use CredStash\Store\StoreInterface;
use Traversable;

/**
 * The CredStash.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class CredStash implements CredStashInterface, ContextAwareInterface
{
    /** @var StoreInterface */
    protected $store;
    /** @var EncryptionInterface */
    protected $encryption;
    /** @var array */
    protected $context = [];

    /**
     * Constructor.
     *
     * @param StoreInterface      $store      The store implementation.
     * @param EncryptionInterface $encryption The encryption implementation.
     */
    public function __construct(StoreInterface $store, EncryptionInterface $encryption)
    {
        $this->store = $store;
        $this->encryption = $encryption;
    }

    /**
     * Shortcut to create CredStash with default setup.
     *
     * @param Sdk    $aws       An AWS SDK instance.
     * @param string $tableName The table name. Defaults to "credential-store".
     * @param string $kmsKey    The KMS key. Defaults to "alias/credstash".
     *
     * @return CredStash
     */
    public static function createFromSdk(
        Sdk $aws,
        $tableName = DynamoDbStore::DEFAULT_TABLE_NAME,
        $kmsKey = KmsEncryption::DEFAULT_KMS_KEY
    ) {
        $db = $aws->createDynamoDb(['version' => '2012-08-10']);
        $store = new DynamoDbStore($db, $tableName ?: DynamoDbStore::DEFAULT_TABLE_NAME);

        $kms = $aws->createKms(['version' => '2014-11-01']);
        $encryption = new KmsEncryption($kms, $kmsKey ?: KmsEncryption::DEFAULT_KMS_KEY);

        return new static($store, $encryption);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceContext($context)
    {
        $this->context = [];
        $this->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function setContext($context)
    {
        $this->context = $this->mergeContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function listCredentials($pattern = '*', $pad = true)
    {
        $credentials = $this->store->listCredentials();

        $credentials = $this->filterCredentials($pattern, $credentials);

        if (!$pad) {
            $credentials = array_map('intval', $credentials);
        }

        return $credentials;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($context = [], $version = null)
    {
        return $this->search(null, $context, $version);
    }

    /**
     * {@inheritdoc}
     */
    public function search($pattern = '*', $context = [], $version = null)
    {
        $context = $this->mergeContext($context);

        $credentials = $this->listCredentials($pattern);

        $result = [];
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
        $context = $this->mergeContext($context);

        if ($version === null) {
            $credential = $this->store->get($name);
        } else {
            $credential = $this->store->getAtVersion($name, $this->paddedInt($version));
        }

        $credential->setKey(base64_decode($credential->getKey()));
        $credential->setContents(base64_decode($credential->getContents()));

        $secret = $this->encryption->decrypt($credential, $context);

        return $secret;
    }

    /**
     * {@inheritdoc}
     */
    public function put($name, $secret, $context = [], $version = null)
    {
        $context = $this->mergeContext($context);

        if ($version === null) {
            $version = $this->getHighestVersion($name) + 1;
        }

        $credential = $this->encryption->encrypt($secret, $context);

        $credential
            ->setName($name)
            ->setVersion($this->paddedInt($version))
            ->setKey(base64_encode($credential->getKey()))
            ->setContents(base64_encode($credential->getContents()))
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
     * Normalize context given and merge it with global context.
     *
     * Nulls values in given context will remove those key pairs
     * from the merged context returned.
     *
     * @param array|Traversable $context
     *
     * @return array
     */
    protected function mergeContext($context = [])
    {
        $context = $this->normalizeContext($context);
        $context = array_replace($this->context, $context);

        // Remove null values, but allow "0"
        $context = array_filter($context, function ($value) {
            return $value !== null;
        });

        return $context;
    }

    /**
     * Normalizes context keys and values.
     *
     * Values can be strings or nulls.
     * Booleans and numbers are converted to strings.
     * "null" and empty strings is converted to null type.
     * All strings are trimmed.
     *
     * @param array|Traversable $context
     *
     * @throws \InvalidArgumentException If values are not scalar or null.
     *
     * @return array
     */
    private function normalizeContext($context)
    {
        $normalized = [];

        foreach ($context as $key => $value) {
            if ($value !== null && !is_scalar($value)) {
                throw new \InvalidArgumentException('CredStash expects context values to be scalar or null.');
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_numeric($value) || is_string($value)) {
                $value = trim($value);
            }
            if ($value === 'null' || $value === '') {
                $value = null;
            }

            $normalized[trim($key)] = $value;
        }

        return $normalized;
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
     * Filter list of credentials to those that match the pattern given.
     *
     * @param string $pattern
     * @param array  $credentials
     *
     * @return array
     */
    private function filterCredentials($pattern, array $credentials)
    {
        if (!$pattern || $pattern === '*') {
            return $credentials;
        }

        $pattern = str_replace('\\', '\\\\', $pattern);

        $result = [];

        foreach ($credentials as $name => $version) {
            if (fnmatch($pattern, $name)) {
                $result[$name] = $version;
            }
        }

        return $result;
    }
}
