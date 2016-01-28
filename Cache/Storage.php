<?php

namespace Stadline\ExecutionCacheBundle\Cache;

use Cache\Adapter\Common\CacheItem;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default cache storage implementation
 */
class Storage
{
    /** @var string */
    protected $keyPrefix;

    /** @var CacheItemPoolInterface Cache used to store cache data */
    protected $cache;

    /** @var int Default cache TTL */
    protected $defaultTtl;

    /** @var KeyProvider Alternative CacheKey provider */
    protected $keyProvider;

    /**
     * @param CacheItemPoolInterface $cache      Cache used to store cache data
     * @param string                 $keyPrefix  Provide an optional key prefix to prefix on all cache keys
     * @param int                    $defaultTtl Default cache TTL
     */
    public function __construct(CacheItemPoolInterface $cache, $keyPrefix = '', $defaultTtl = 3600)
    {
        $this->cache = $cache;
        $this->keyPrefix = $keyPrefix;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * @param KeyProvider $keyProvider
     */
    public function setKeyProvider(KeyProvider $keyProvider)
    {
        $this->keyProvider = $keyProvider;
    }

    /**
     * Store a response generated by a request.
     *
     * @param Request $request
     * @param Response $response
     * @param integer $ttl
     */
    public function cache(Request $request, Response $response, $ttl = null)
    {
        // only cache GET and HEAD requests
        if (!in_array($request->getMethod(), array('GET', 'HEAD'))) {
            return;
        }

        // respect Cache-Control: no-cache
        if ($request->isNoCache()) {
            return;
        }

        // skip already cached response
        if ($response->headers->has('X-ServerCache-Key')) {
            return;
        }

        // set cache lifetime
        if (is_null($ttl)) {
            $ttl = $this->defaultTtl;
        }

        $expirationDate = date_create('NOW + ' . $ttl . ' seconds');

        // save cache item
        $key = $this->getCacheKey($request);

        $item = new CacheItem($key, true, $response, $expirationDate);
        $this->cache->save($item);
    }

    /**
     * Remove the cached response matching a request.
     *
     * @param Request $request
     */
    public function delete(Request $request)
    {
        $key = $this->getCacheKey($request);

        $this->cache->deleteItem($key);
    }

    /**
     * @param string $url
     * @throws \BadMethodCallException
     */
    public function purge($url)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * Retrieve a cached response matching a request.
     *
     * @param Request $request
     * @return Response
     */
    public function fetch(Request $request)
    {
        $key = $this->getCacheKey($request);

        // retrieve a cache item
        $item = $this->cache->getItem($key);
        $response = $item->get();

        if ($response instanceof Response) {
            // compute remaining ttl
            $expirationDate = $item->getExpirationDate();
            $ttl = $expirationDate->getTimestamp() - date_create('NOW')->getTimestamp();

            // expose the cache key and ttl
            $response->headers->set('X-ServerCache-Key', $key);
            $response->headers->set('X-ServerCache-Expires', $ttl);
        }

        return $response;
    }

    /**
     * Hash a request into a string that returns cache metadata
     *
     * @param Request $request
     * @return string
     */
    protected function getCacheKey(Request $request)
    {
        if ($this->keyProvider) {
            // use the key provider
            $key = $this->keyProvider->getCacheKey($request);
        } else {
            // use default method
            $key = md5($request->getMethod() . ' ' . $request->getUri());
        }

        // append the prefix to the cache key
        return $this->keyPrefix . $key;
    }
}
