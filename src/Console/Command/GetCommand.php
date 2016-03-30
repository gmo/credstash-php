<?php

namespace CredStash\Console\Command;

/**
 * Get Command.
 * 
 * @author Carson Full <carsonfull@gmail.com>
 */
class GetCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('get')
            ->setDescription('Get a credential from the store')
        ;
    }
}
