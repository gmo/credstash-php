<?php

namespace CredStash\Store;

use CredStash\Credential;
use CredStash\Exception\DuplicateCredentialVersionException;
use CredStash\Exception\CredentialNotFoundException;

/**
 * A credential storage.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface StoreInterface
{
    /**
     * Fetches the names and version of every credential in the store.
     *
     * @return array [name => normalized (padded) numeric version string]
     */
    public function listCredentials();

    /**
     * Fetches the credential from the store with the highest version.
     *
     * @param string $name The credential's name.
     *
     * @throws CredentialNotFoundException If the credential does not exist.
     *
     * @return Credential The credential.
     */
    public function get($name);

    /**
     * Fetches the credential from the store for the given version.
     *
     * @param string $name    The credential's name.
     * @param string $version A normalized (padded) numeric version string.
     *
     * @throws CredentialNotFoundException If the credential does not exist.
     *
     * @return Credential The credential.
     */
    public function getAtVersion($name, $version);

    /**
     * Fetches the highest version of given credential in the store.
     *
     * @param string $name The credential's name.
     *
     * @return string The numeric version string or '0' if not found.
     */
    public function getHighestVersion($name);

    /**
     * Puts the credential in the store if it does not already exist.
     *
     * @param Credential $credential The credential to store.
     *
     * @throws DuplicateCredentialVersionException If the credential with the version already exists.
     */
    public function put(Credential $credential);

    /**
     * Delete a credential from the store (including all versions).
     *
     * @param string $name The credential's name.
     */
    public function delete($name);
}
