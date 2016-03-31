<?php

namespace CredStash\Console\Command;

use CredStash\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

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
        $this
            ->setName('getall')
            ->setDescription('Get all credentials from the store')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format. json, yaml, or csv.', 'json')
            ->addOption(
                'cred-version',
                'c',
                InputOption::VALUE_REQUIRED,
                'Get a specific version of the credential <comment>[default: latest version]</comment>'
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
        $format = $input->getOption('format');
        $accepted = ['json', 'yaml', 'csv'];
        if (!in_array($format, $accepted)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid format: "%s". Valid formats are %s.', $format, implode(', ', $accepted))
            );
        }

        $credentials = $this->getCredStash()->getAll($input->getArgument('context'), $input->getOption('cred-version'));

        if ($format === 'json') {
            $out = json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif ($format === 'csv') {
            $out = $this->dumpCsv($credentials);
        } else {
            if (!class_exists(Yaml::class)) {
                throw new RuntimeException(
                    "YAML format requires Symfony's YAML component to be installed.\ncomposer require symfony/yaml"
                );
            }

            $out = Yaml::dump($credentials);
        }

        $output->writeln($out);
    }

    /**
     * Dumps key/value pair credentials to two column csv rows.
     *
     * @param array $data
     *
     * @return string
     */
    protected function dumpCsv(array $data)
    {
        $fh = fopen('php://temp', 'rw');

        foreach ($data as $key => $value) {
            fputcsv($fh, [$key, $value]);
        }
        rewind($fh);

        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }
}
