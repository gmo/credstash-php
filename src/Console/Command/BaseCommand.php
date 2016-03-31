<?php

namespace CredStash\Console\Command;

use CredStash\Console\Helper\CredStashHelper;
use CredStash\CredStashInterface;
use CredStash\Encryption\KmsEncryption;
use CredStash\Store\DynamoDbStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base CredStash Command.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class BaseCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->addOption(
                'table',
                't',
                InputOption::VALUE_REQUIRED,
                'DynamoDB table to use for credential storage',
                DynamoDbStore::DEFAULT_TABLE_NAME
            )
            ->addOption(
                'kms',
                'k',
                InputOption::VALUE_REQUIRED,
                'AWS KMS key name to use for encryption',
                KmsEncryption::DEFAULT_KMS_KEY
            )
            ->addOption(
                'region',
                'r',
                InputOption::VALUE_REQUIRED,
                <<<EOL
The AWS region in which to operate. CredStash will determine region is this order:
- Passed in as CLI parameter
- <comment>AWS_DEFAULT_REGION</> environment variable
- Value in <comment>~/.aws/config</>
- Or lastly fall back to <comment>"us-east-1"</>
EOL
            )
            ->addOption(
                'profile',
                'p',
                InputOption::VALUE_REQUIRED,
                'AWS profile name to use <comment>[default: "default"]</comment>'
            )
        ;
    }

    /**
     * Add context parameter to input definition.
     *
     * @return BaseCommand
     */
    protected function addContextArgument()
    {
        $this->addArgument(
            'context',
            InputArgument::IS_ARRAY,
            "Encryption context key/value pairs associated with the credential\n" . 
            "in the form of <comment>\"key=value\"</comment>"
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        
        if ($input->hasArgument('context')) {
            $input->setArgument('context', $this->normalizeContext($input->getArgument('context')));
        }
    }

    /**
     * @return CredStashInterface
     */
    protected function getCredStash()
    {
        /** @var CredStashHelper $helper */
        $helper = $this->getHelper('credstash');

        return $helper->getCredStash();
    }

    /**
     * Normalize context args by splitting them
     * from key=value into [key => value]
     *
     * @param array $args
     *
     * @return array
     */
    private function normalizeContext($args)
    {
        $context = [];

        foreach ($args as $arg) {
            $parts = explode('=', $arg, 2);
            if (empty($parts[1])) {
                throw new \InvalidArgumentException(sprintf('"%s" is not the form of "key=value"', $arg));
            }
            $context[$parts[0]] = $parts[1];
        }

        return $context;
    }
}
