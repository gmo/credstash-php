<?php

namespace CredStash\Console\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $this
            ->setName('info')
            ->setDescription('List credentials and their versions')
            ->setAliases(['search'])
            ->addArgument('pattern', null, 'Filter credentials to those matching this pattern', '*')
            ->addOption('name-only', null, null, 'Only output names not versions')
        ;
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pattern = $input->getArgument('pattern');
        $nameOnly = $input->getOption('name-only');

        $credentials = $this->getCredStash()->listCredentials($pattern);

        $table = new Table($output);
        $table->setStyle('compact');
        $table->setHeaders(['Name', 'Version']);

        // If only name show data like a list
        if ($nameOnly) {
            $table->setHeaders([]);
            $table->getStyle()->setVerticalBorderChar('');
        }

        foreach ($credentials as $name => $version) {
            $row = [$name];
            if (!$nameOnly) {
                $row[] = "<comment>$version</comment>";
            }
            $table->addRow($row);
        }

        $table->render();
    }
}
