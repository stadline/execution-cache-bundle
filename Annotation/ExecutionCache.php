<?php

namespace Stadline\ExecutionCacheBundle\Annotation;

/**
 * Annotation to enable execution cache on controller methods
 *
 * @Annotation
 * @Target("METHOD")
 */
final class ExecutionCache
{
    /**
     * Parameter lifetime
     *
     * @var integer
     */
    public $lifetime;
}
