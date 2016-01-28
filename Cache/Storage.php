<?php

namespace StadLine\ExecutionCacheBundle\Cache;

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
     * @param Request $request
     * @param Response $response
     */
    public function cache(Request $request, Response $response)
    {
        if (!in_array($request->getMethod(), array('GET', 'HEAD'))) {
            return;
        }

        if ($request->isNoCache()) {
            return;
        }

        if ($response->headers->has('X-ServerCache-Key')) {
            return;
        }

        $key = $this->getCacheKey($request);
        $expirationDate = date_create('NOW + ' . $this->defaultTtl . ' seconds');

        $item = new CacheItem($key, true, $response, $expirationDate);
        $this->cache->save($item);
    }

    /**
     * @param Request $request
     */
    public function delete(Request $request)
    {
        $key = $this->getCacheKey($request);

        $this->cache->deleteItem($key);
    }

    /**
     * @param type $url
     * @throws \BadMethodCallException
     */
    public function purge($url)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function fetch(Request $request)
    {
        $key = $this->getCacheKey($request);

        $item = $this->cache->getItem($key);
        $response = $item->get();

        if ($response instanceof Response) {
            $expirationDate = $item->getExpirationDate();
            $ttl = $expirationDate->getTimestamp() - date_create('NOW')->getTimestamp();

            $response->headers->set('X-ServerCache-Key', $key);
            $response->headers->set('X-ServerCache-Expires', $ttl);
        }

        return $response;
    }

    /**
     * Hash a request URL into a string that returns cache metadata
     *
     * @param Request $request
     * @return string
     */
    protected function getCacheKey(Request $request)
    {
        if ($this->keyProvider) {
            $key = $this->keyProvider->getCacheKey($request);
        } else {
            $key = md5($request->getMethod() . ' ' . $request->getUri());
        }

        return $this->keyPrefix . $key;
    }
}
