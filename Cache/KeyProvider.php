<?php

namespace StadLine\ExecutionCacheBundle\Cache;

use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default cache_key provider implementation
 */
class KeyProvider
{
    /**
     * Hash a request into a string that returns cache metadata
     *
     * @param Request $request
     * @return string
     */
    public function getCacheKey(Request $request)
    {
        $reducedRequest = $request->duplicate();
        $this->persistHeaders($reducedRequest->headers);

        return hash('sha256', (string) $reducedRequest);
    }

    /**
     * Creates an array of cacheable and normalized request headers
     *
     * @param HeaderBag $headers
     * @return array
     */
    private function persistHeaders(HeaderBag $headers)
    {
        // Headers are excluded from the caching (see RFC 2616:13.5.1)
        static $noCache = array(
            'age',
            'connection',
            'keep-alive',
            'proxy-authenticate',
            'proxy-authorization',
            'te',
            'trailers',
            'transfer-encoding',
            'upgrade',
            'set-cookie',
            'set-cookie2'
        );

        foreach ($headers as $key => $value) {
            if (in_array($key, $noCache) || strpos($key, 'x-') === 0) {
                $headers->remove($key);
            }
        }

        // Postman client sends a dynamic token to bypass a Chrome bug
        $headers->remove('postman-token');

        return $headers;
    }
}
