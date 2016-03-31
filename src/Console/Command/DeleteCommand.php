<?php

namespace CredStash\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $this
            ->setName('delete')
            ->setDescription('Deletes a credential from the store')
            ->addArgument('credential', InputArgument::REQUIRED, 'The name of the credential to delete')
        ;
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getCredStash()->delete($input->getArgument('credential'));
    }
}
