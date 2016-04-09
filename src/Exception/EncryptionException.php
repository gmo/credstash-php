<?php

namespace CredStash\Exception;

use Exception;

class EncryptionException extends \Exception implements CredStashException
{
    /**
     * Constructor.
     *
     * @param string         $message
     * @param Exception|null $previous
     */
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
