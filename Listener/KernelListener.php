<?php

namespace Stadline\ExecutionCacheBundle\Listener;

use Doctrine\Common\Annotations\Reader;
use Stadline\ExecutionCacheBundle\Annotation\ExecutionCache;
use Stadline\ExecutionCacheBundle\Cache\Storage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * KernelListener handles execution cache.
 * Its purpose is to prevent controller execution when response is cached.
 */
class KernelListener implements EventSubscriberInterface
{
    /**
     * The annotation class used to activate and configure the cache
     */
    const ANNOTATION_CLASS = 'Stadline\\ExecutionCacheBundle\\Annotation\\ExecutionCache';

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * Constructor.
     *
     * @param Storage $storage
     */
    public function __construct(Reader $reader, Storage $storage)
    {
        $this->reader = $reader;
        $this->storage = $storage;
    }

    /**
     * Handles cache lookup.
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $annotation = $this->getCacheAnnotation($event->getController());

        // no cache annotation is defined for this controller
        if (!$annotation) {
            return;
        }

        $request = $event->getRequest();
        $response = $this->storage->fetch($request);

        if ($response) {
            // a cached response was found, bypass controller execution and return response object
            $event->setController(function () use ($response) {
                return $response;
            });
        } else {
            // store ExecutionCache annotation config
            // this attribute will enable response caching
            $request->attributes->set('_execution_cache', $annotation);
        }
    }

    /**
     * Handles cache storage.
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // retrieve previously stored annotation config
        // this is a signal that response must be cached
        $annotation = $request->attributes->get('_execution_cache');

        if ($annotation && $response->isSuccessful()) {
            // response is cacheable
            $this->storage->cache($request, $response, $annotation->lifetime);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        );
    }

    /**
     * Returns the cache annotation.
     *
     * @param mixed $controller
     * @return ExecutionCache
     */
    public function getCacheAnnotation($controller)
    {
        if (!is_array($controller)) {
            return;
        }

        $refl = new \ReflectionObject($controller[0]);
        $reflMethod = $refl->getMethod($controller[1]);

        // use the annotation reader to retrieve ExecutionCache on controller method
        return $this->reader->getMethodAnnotation($reflMethod, self::ANNOTATION_CLASS);
    }
}
