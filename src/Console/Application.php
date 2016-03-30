<?php

namespace CredStash\Console;

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

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        return array_merge(
            parent::getDefaultCommands(),
            [
                new Command\DeleteCommand(),
                new Command\GetAllCommand(),
                new Command\GetCommand(),
                new Command\PutCommand(),
                new Command\InfoCommand(),
            ]
        );
    }
}
