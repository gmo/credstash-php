<?php

namespace CredStash\Console\Command;

use CredStash\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Get Command.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class GetCommand extends BaseCommand
{
    protected static $VALID_FORMATS = ['json', 'yaml', 'csv'];

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
                "The name of the credential to get. Using the wildcard characters\n" .
                '<comment>"*"</> or <comment>"?"</> will search for credentials that match the pattern.'
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
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                "Output format for multiple credentials.\nValid formats: " . implode(', ', static::$VALID_FORMATS),
                'json'
            )
        ;
        $this->addContextArgument();
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $format = $input->getOption('format');
        if (!in_array($format, static::$VALID_FORMATS)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid format: "%s". Valid formats are %s.', $format, implode(', ', static::$VALID_FORMATS))
            );
        }

        if ($format === 'yaml' && !class_exists(Yaml::class)) {
            throw new RuntimeException(
                "YAML format requires Symfony's YAML component to be installed.\ncomposer require symfony/yaml"
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = $input->getArgument('context');
        $version = $input->getOption('cred-version');

        $value = $this->fetchCredentials($input, $context, $version);

        $output->write($value);

        if (!$input->getOption('no-break')) {
            $output->writeln('');
        }
    }

    /**
     * Fetch credentials from given input.
     * 
     * Context and version are passed in as they are always required.
     *
     * @param InputInterface $input
     * @param array          $context
     * @param string|null    $version
     *
     * @return string
     */
    protected function fetchCredentials(InputInterface $input, array $context, $version)
    {
        $name = $input->getArgument('credential');
        $credStash = $this->getCredStash();

        // If $name contains these wildcard characters call search() instead of get()
        $search = false;
        $wildcards = ['*', '?', '['];
        foreach ($wildcards as $wildcard) {
            if (strpos($wildcard, $name) !== false) {
                $search = true;
                break;
            }
        }

        if (!$search) {
            return $credStash->get($name, $context, $version);
        }

        $credentials = $credStash->search($name, $context, $version);

        return $this->dumpCredentials($input, $credentials);
    }

    /**
     * Dump given credentials to a string based on "format" option from input given.
     *
     * @param InputInterface $input
     * @param array          $credentials
     *
     * @return string
     */
    protected function dumpCredentials(InputInterface $input, array $credentials)
    {
        $format = $input->getOption('format');

        if ($format === 'json') {
            return json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($format === 'csv') {
            return $this->dumpCsv($credentials);
        }

        return Yaml::dump($credentials);
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
