<?php

namespace CredStash\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
        $this
            ->setName('get')
            ->setDescription('Get a credential from the store')
            ->addArgument(
                'credential',
                InputArgument::REQUIRED,
                "The name of the credential to get. Using the wildcard character\n" .
                '<comment>"*"</comment> will search for credentials that match the pattern.'
            )
            ->addOption(
                'cred-version',
                'c',
                InputOption::VALUE_REQUIRED,
                'Get a specific version of the credential <comment>[default: latest version]</comment>'
            )
            ->addOption(
                'no-break',
                'b',
                null,
                'Don\'t append newline to returned value (useful in scripts or with binary files)'
            )
        ;
        $this->addContextArgument();
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $value = $this->getCredStash()->get($input->getArgument('credential'), $input->getArgument('context'));
        $output->write($value);

        if (!$input->getOption('no-break')) {
            $output->writeln('');
        }
    }
}
