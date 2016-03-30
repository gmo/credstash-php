<?php

namespace CredStash\Encryption;

use CredStash\Credential;

/**
 * An encryption algorithm interface.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface EncryptionInterface
{
    /**
     * Verify and decrypt credential.
     *
     * @param Credential $credential The credential.
     * @param array      $context    Encryption context key value pairs.
     *
     * @return string The secret.
     */
    public function decrypt(Credential $credential, array $context);

    /**
     * Encrypt the secret data.
     *
     * @param string $secret  The secret data.
     * @param array  $context Encryption context key value pairs.
     *
     * @return Credential The credential containing the encrypted data, the wrapped data key, and the hash.
     */
    public function encrypt($secret, array $context);
}
