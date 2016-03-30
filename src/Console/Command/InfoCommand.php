<?php

namespace CredStash\Console\Command;

/**
 * Info Command.
 *
 * Note: In python this is "list", but that is already taken by Symfony here.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class InfoCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('info')
            ->setDescription('List credentials and their versions')
        ;
    }
}
