<?php

namespace CredStash\Exception;

use Exception;

class DuplicateCredentialVersionException extends RuntimeException
{
    /**
     * Constructor.
     *
     * @param string         $name
     * @param string|int     $version
     * @param Exception|null $previous
     */
    public function __construct($name, $version, Exception $previous = null)
    {
        $message = sprintf('Credential "%s" already has a version %d in the store.', $name, $version);
        parent::__construct($message, 0, $previous);
    }
}
