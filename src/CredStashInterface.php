<?php

namespace CredStash;

use CredStash\Exception\CredentialNotFoundException;
use CredStash\Exception\DuplicateCredentialVersionException;
use CredStash\Exception\IntegrityException;
use CredStash\Exception\RuntimeException;
use Traversable;

/**
 * A CredStash.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface CredStashInterface
{
    /**
     * Fetches the names and version of every credential in the
     * store matching the pattern given.
     *
     * The pattern can contain "*" and "?" wildcard characters and "[]" grouping.
     *
     * @example "gr[ae]y"
     * @example "group*"
     *
     * @param string $pattern The pattern to search for.
     *
     * @return array [name => version]
     */
    public function listCredentials($pattern = '*');

    /**
     * Fetches and decrypts all credentials.
     *
     * @param array|Traversable $context Encryption Context key value pairs.
     * @param int|string|null   $version Numeric version for all credentials or null for highest of each credential.
     *
     * @throws CredentialNotFoundException If the credential does not exist.
     * @throws IntegrityException If the HMAC does not match.
     * @throws RuntimeException If decryption fails.
     *
     * @return array [name => secret]
     */
    public function getAll($context = [], $version = null);

    /**
     * Fetches and decrypts all credentials matching the pattern given.
     *
     * @param string            $pattern The pattern to search for. See {@see listCredentials} for details.
     * @param array|Traversable $context Encryption Context key value pairs.
     * @param int|string|null   $version Numeric version for all credentials or null for highest of each credential.
     *
     * @throws CredentialNotFoundException If the credential does not exist.
     * @throws IntegrityException If the HMAC does not match.
     * @throws RuntimeException If decryption fails.
     *
     * @return array [name => secret]
     */
    public function search($pattern = '*', $context = [], $version = null);

    /**
     * Fetches and decrypts the credential.
     *
     * @param string            $name    The credential's name.
     * @param array|Traversable $context Encryption Context key value pairs.
     * @param int|string|null   $version Numeric version or null for highest.
     *
     * @throws CredentialNotFoundException If the credential does not exist.
     * @throws IntegrityException If the HMAC does not match.
     * @throws RuntimeException If decryption fails.
     *
     * @return string The secret.
     */
    public function get($name, $context = [], $version = null);

    /**
     * Put a credential into the store.
     *
     * @param string            $name    The credential's name.
     * @param string            $secret  The secret value.
     * @param array|Traversable $context Encryption Context key value pairs.
     * @param int|string|null   $version Numeric version or null for next auto-incremented version.
     *
     * @throws DuplicateCredentialVersionException If the credential with the version already exists.
     * @throws RuntimeException If encryption fails.
     */
    public function put($name, $secret, $context = [], $version = null);

    /**
     * Delete a credential from the store (including all versions).
     *
     * @param string $name
     */
    public function delete($name);

    /**
     * Fetches the highest version of given credential in the store.
     *
     * @param string $name The credential's name.
     *
     * @return int The version or 0 if not found.
     */
    public function getHighestVersion($name);
}
