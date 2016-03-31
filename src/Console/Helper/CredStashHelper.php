<?php

namespace CredStash\Console\Helper;

use CredStash\CredStash;
use CredStash\CredStashInterface;
use Symfony\Component\Console\Helper\InputAwareHelper;
use Symfony\Component\Console\Input\InputInterface;

/**
 * The CredStashHelper allows a CredStash instance
 * to be given to the console application, instead
 * of the application creating it itself.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class CredStashHelper extends InputAwareHelper
{
    /** @var InputInterface */
    protected $input;
    /** @var CredStashInterface */
    protected $credStash;

    /**
     * Constructor.
     *
     * @param CredStashInterface|null $credStash
     */
    public function __construct(CredStashInterface $credStash = null)
    {
        $this->credStash = $credStash;
    }

    /**
     * Set the CredStash instance to use.
     *
     * @param CredStashInterface|null $credStash
     */
    public function setCredStash(CredStashInterface $credStash = null)
    {
        $this->credStash = $credStash;
    }

    /**
     * Get the CredStash instance.
     *
     * @return CredStashInterface
     */
    public function getCredStash()
    {
        if ($this->credStash === null) {
            $this->credStash = $this->createCredStash();
        }

        return $this->credStash;
    }

    /**
     * Create the CredStash instance with AwsHelper and "table" and "kms" input options.
     *
     * @return CredStashInterface
     */
    protected function createCredStash()
    {
        /** @var AwsHelper $helper */
        $helper = $this->getHelperSet()->get('aws');

        $table = $this->input->hasOption('table') ? $this->input->getOption('table') : null;
        $key = $this->input->hasOption('kms') ? $this->input->getOption('kms') : null;

        return CredStash::createFromSdk($helper->getSdk(), $table, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'credstash';
    }
}
