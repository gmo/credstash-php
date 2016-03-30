<?php

namespace CredStash;

use Aws\Sdk;
use CredStash\Encryption\EncryptionInterface;
use CredStash\Encryption\KmsEncryption;
use CredStash\Store\DynamoDbStore;
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
    /** @var EncryptionInterface */
    protected $encryption;

    /**
     * Constructor.
     *
     * @param StoreInterface      $store
     * @param EncryptionInterface $encryption
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
     * @return CredStashInterface
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
}
