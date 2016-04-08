<?php

namespace CredStash\Console\Command;

use CredStash\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

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
        $this
            ->setName('put')
            ->setDescription('Put a credential into the store')
            ->addArgument('credential', InputArgument::REQUIRED, 'The name of the credential to store')
            ->addArgument(
                'value',
                InputArgument::REQUIRED,
                <<<EOL
The value of the credential to store or, if beginning with 
the <comment>"@"</> character, the filename of the file
containing the value, or pass <comment>"-"</> to read the
value from stdin
EOL
            )
            ->addOption('cred-version', 'c', InputOption::VALUE_REQUIRED, 'Put a specific version of the credential', 1)
            ->addOption(
                'autoversion',
                'a',
                null,
                <<<EOL
Automatically increment the version of the credential to be stored.
This option causes the <info>--cred-version</info> flag to be ignored.
(This option will fail if the currently stored version is not numeric.)
EOL
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
        $value = $input->getArgument('value');
        if ($value === '-') {
            $value = stream_get_contents(STDIN);
        } elseif (substr($value, 0, 1) === '@') {
            $value = $this->readFile(substr($value, 1));
        }

        $version = null;
        if (!$input->getOption('autoversion')) {
            $version = $input->getOption('cred-version');
        }

        $this->getCredStash()->put(
            $input->getArgument('credential'),
            $value,
            $input->getArgument('context'),
            $version
        );
    }

    /**
     * Reads and returns file contents.
     *
     * @param string $filename
     *
     * @throws RuntimeException If reading file fails
     * 
     * @return string
     */
    protected function readFile($filename)
    {
        $filename = Path::canonicalize($filename);

        $level = error_reporting(0);
        $content = file_get_contents($filename);
        error_reporting($level);
        if ($content === false) {
            $error = error_get_last();
            throw new RuntimeException($error['message']);
        }

        return $content;
    }
}
