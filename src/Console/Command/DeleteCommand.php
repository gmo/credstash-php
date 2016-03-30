<?php

namespace CredStash\Console\Command;

/**
 * Delete Command.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DeleteCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('delete')
            ->setDescription('Deletes a credential from the store')
        ;
    }
}
