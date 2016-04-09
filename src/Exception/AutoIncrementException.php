<?php

namespace CredStash\Exception;

use Exception;

class AutoIncrementException extends \UnexpectedValueException implements CredStashException
{
    /**
     * Constructor.
     *
     * @param string         $version
     * @param string|null    $message
     * @param Exception|null $previous
     */
    public function __construct($version, $message = null, Exception $previous = null)
    {
        $message = sprintf($message ?: 'Version "%s" cannot be auto incremented as it is not numeric.', $version);
        parent::__construct($message, 0, $previous);
    }
}
