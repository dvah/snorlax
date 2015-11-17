<?php

namespace Snorlax;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Snorlax\Exception\ResourceNotImplemented;

/**
 * The REST client.
 * Works as a know it all class that keeps the client and the resources together.
 */
class RestClient
{
    /**
     * @var GuzzleHttp\ClientInterface
     */
    private $client = null;

    /**
     * @var Illuminate\Support\Collection
     */
    private $resources = null;

    /**
     * @var array
     */
    private $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->setClient($config);
        $this->addResources(
            isset($config['resources']) ? $config['resources'] : []
        );
    }

    public function __get($resource)
    {
        return $this->getResource($resource);
    }

    public function addResources(array $resources)
    {
        if (!($this->resources instanceof Collection)) {
            $this->resources = new Collection();
        }

        foreach ($resources as $resource => $class) {
            $this->resources->put($resource, [
                'instance' => null,
                'class' => $class
            ]);
        }
    }

    public function setClient(array $config)
    {
        if (isset($config['client'])) {
            $config = $config['client'];
        }

        $params = isset($config['params']) ? $config['params'] : [];
        if (isset($config['custom'])) {
            if (is_callable($config['custom'])) {
                $client = $config['custom']($params);
            } else {
                $client = $config['custom'];
            }
        } else {
            $client = new Client($params);
        }

        $this->client = $client;
    }

    public function getResource($resource)
    {
        if (!$this->resources->has($resource)) {
            throw new ResourceNotImplemented($resource);
        }

        $resource = $this->resources->get($resource);
        $instance = $resource['instance'];
        if (is_null($instance)) {
            $class = $resource['class'];

            $instance = new $class($this->client);
        }

        return $instance;
    }

    public function getOriginalClient()
    {
        return $this->client;
    }
}