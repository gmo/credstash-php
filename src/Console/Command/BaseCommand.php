<?php

namespace CredStash\Console\Command;

use CredStash\Encryption\KmsEncryption;
use CredStash\Store\DynamoDbStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

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
}
