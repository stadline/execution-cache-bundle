<?php

namespace StadLine\ExecutionCacheBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use StadLine\ExecutionCacheBundle\Cache\Storage;

/**
 * KernelListener handles execution cache.
 * Its purpose is to prevent controller execution when response is cached.
 */
class KernelListener implements EventSubscriberInterface
{
    /**
     * @var Storage
     */
    private $storage;

    /**
     * Constructor.
     *
     * @param Storage $storage
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Handles cache lookup.
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $response = $this->storage->fetch($request);

        if ($response) {
            $event->setController(function () use ($response) {
                return $response;
            });
        }
    }

    /**
     * Handles cache storage.
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($response->isSuccessful()) {
            $this->storage->cache($request, $response);
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        );
    }
}
