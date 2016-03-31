<?php

namespace CredStash\Console;

use CredStash\CredStashInterface;

/**
 * The CredStash Console Application.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * Constructor.
     *
     * @param CredStashInterface|null $credStash Optional CredStash instance.
     */
    public function __construct(CredStashInterface $credStash = null)
    {
        parent::__construct('credstash', null);

        $this->getHelperSet()->set(new Helper\CredStashHelper($credStash));
        $this->getHelperSet()->set(new Helper\AwsHelper());
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
