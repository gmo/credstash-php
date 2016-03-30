<?php

namespace CredStash\Console\Command;

/**
 * Put Command.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class PutCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('put')
            ->setDescription('Put a credential into the store')
        ;
    }
}
