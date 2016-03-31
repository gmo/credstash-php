<?php

namespace CredStash\Console\Helper;

use Aws\Sdk;
use Symfony\Component\Console\Helper\InputAwareHelper;
use Symfony\Component\Console\Input\InputInterface;

/**
 * The AwsHelper class allows an SDK to be given to
 * the console application, instead of the application
 * creating the SDK itself.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class AwsHelper extends InputAwareHelper
{
    const ENV_REGION = 'AWS_DEFAULT_REGION';
    const DEFAULT_REGION = 'us-east-1';

    /** @var InputInterface */
    protected $input;
    /** @var Sdk */
    protected $sdk;

    /**
     * Constructor.
     *
     * @param Sdk|null $sdk
     */
    public function __construct(Sdk $sdk = null)
    {
        $this->sdk = $sdk;
    }

    /**
     * Set the AWS SDK.
     *
     * @param Sdk|null $sdk
     */
    public function setSdk(Sdk $sdk = null)
    {
        $this->sdk = $sdk;
    }

    /**
     * Get the AWS SDK. Creating it from input if needed.
     *
     * @return Sdk
     */
    public function getSdk()
    {
        if ($this->sdk === null) {
            $this->sdk = $this->createSdk();
        }

        return $this->sdk;
    }

    /**
     * Create the SDK with "profile" and "region" input options.
     *
     * @return Sdk
     */
    protected function createSdk()
    {
        $config = [];

        if ($this->input->hasOption('profile')) {
            $profile = $this->input->getOption('profile');
            if ($profile !== null) {
                $config['profile'] = $profile;
            }
        }

        $region = null;
        if ($this->input->hasOption('region')) {
            $region = $this->input->getOption('region');
        }
        $config['region'] = $region ?: getenv(static::ENV_REGION) ?: static::DEFAULT_REGION;

        return new Sdk($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'aws';
    }
}
