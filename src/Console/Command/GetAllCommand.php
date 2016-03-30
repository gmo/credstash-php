<?php

namespace CredStash\Console\Command;

/**
 * GetAll Command.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class GetAllCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('getall')
            ->setDescription('Get all credentials from the store')
        ;
    }
}
