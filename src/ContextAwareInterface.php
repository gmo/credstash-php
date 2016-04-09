<?php

namespace CredStash;

use Traversable;

/**
 * This defines the CredStash instance supports global context.
 * The global context is merged with the context given to the
 * applicable CredStash methods. This allows common context to
 * be defined once instead of with each method call.
 * 
 * For example:
 * 
 *   $credstash->setContext(['hello' => 'world']);
 *   $credstash->get('foo');
 *   $credstash->get('bar');
 * 
 * is the same as:
 * 
 *   $credstash->get('foo', ['hello' => 'world']);
 *   $credstash->get('bar', ['hello' => 'world']);
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface ContextAwareInterface
{
    /**
     * Gets the global encryption context key value pairs.
     *
     * @return array
     */
    public function getContext();

    /**
     * Replaces the current global context with the context given.
     *
     * @param array|Traversable $context Encryption context key value pairs.
     */
    public function replaceContext($context);

    /**
     * Merge the current global context with the context given.
     *
     * Note: Null values in given context will remove those pairs
     * from the resulting context.
     *
     * @param array|Traversable $context Encryption context key value pairs.
     */
    public function setContext($context);
}
