<?php

namespace CredStash;

/**
 * A credential.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Credential
{
    /** @var string The credential's name */
    protected $name;
    /** @var string The normalized (padded) numeric version */
    protected $version;
    /** @var string The wrapped data key */
    protected $key;
    /** @var string The encrypted data */
    protected $contents;
    /** @var string The hash */
    protected $hash;

    /**
     * Get the credential's name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the credential's name.
     *
     * @param string $name
     *
     * @return Credential
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the normalized (padded) numeric version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the numeric version. It should be normalized (padded).
     *
     * @param string $version
     *
     * @return Credential
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get the wrapped data key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the wrapped data key.
     *
     * @param string $key
     *
     * @return Credential
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get the encrypted contents.
     *
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Set the encrypted contents.
     *
     * @param string $contents
     *
     * @return Credential
     */
    public function setContents($contents)
    {
        $this->contents = $contents;

        return $this;
    }

    /**
     * Get the hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set the hash.
     *
     * @param string $hash
     *
     * @return Credential
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }
}
