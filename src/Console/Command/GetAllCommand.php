<?php

namespace CredStash\Console\Command;

use Symfony\Component\Console\Input\InputInterface;

/**
 * GetAll Command.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class GetAllCommand extends GetCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        // Remove credential argument
        $args = $this->getDefinition()->getArguments();
        unset($args['credential']);
        $this->getDefinition()->setArguments($args);

        $this
            ->setName('getall')
            ->setDescription('Get all credentials from the store')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchCredentials(InputInterface $input, array $context, $version)
    {
        return $this->getCredStash()->getAll($context, $version);
    }
}
