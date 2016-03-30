<?php

namespace CredStash\Exception;

use Exception;

class CredentialNotFoundException extends RuntimeException
{
    /**
     * Constructor.
     *
     * @param string         $name
     * @param Exception|null $previous
     */
    public function __construct($name, Exception $previous = null)
    {
        $message = sprintf('Credential "%s" could not be found in the store.', $name);
        parent::__construct($message, 0, $previous);
    }
}
