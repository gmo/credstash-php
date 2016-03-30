<?php

namespace CredStash\Console;

use CredStash\Command;

/**
 * The CredStash Console Application.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct('credstash', null);
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        return parent::getHelp() . ' - <comment>A credential/secret storage system</comment>';
    }
}
