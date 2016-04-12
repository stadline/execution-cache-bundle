<?php

namespace Stadline\ExecutionCacheBundle\CacheItem;

use Symfony\Component\HttpFoundation\Response;

class MetadataResponse
{
    /** @var array */
    private $metadata;

    /** @var Response */
    private $response;

    /**
     * MetadataResponse constructor.
     * @param Response $response
     * @param array $metadata
     */
    public function __construct(Response $response, array $metadata = array())
    {
        $this->response = $response;
        $this->metadata = $metadata;
    }

    /**
     * Returns metadata value or null if not exists
     *
     * @param string $metadataName
     * @return mixed|null
     */
    public function getMetaData($metadataName)
    {
        if (isset($this->metadata[$metadataName])) {
            return $this->metadata[$metadataName];
        }
        return null;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}