<?php

namespace Yource\ExactOnlineClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Yource\ExactOnlineClient\Resources\ExactOnlineResource;

class ExactOnlineClient
{
    private ExactOnlineAuthorization $authorization;

    private Client $client;

    private string $baseUri = 'https://start.exactonline.nl';

    private string $apiUrlPath = '/api/v1';

    private string $division;

    private ExactOnlineResource $resource;

    private string $endpoint;

    private array $wheres;

    private array $query;

    private int $limitRequests;

    public function __construct(ExactOnlineResource $resource)
    {
        $this->authorization = (new ExactOnlineAuthorization());

        $this->division = config('exact-online-client-laravel.division');

        $this->setResource($resource);

        $this->client = new Client([
            'base_uri' => $this->baseUri,
        ]);
    }

    public function where(string $field, $value): self
    {
        $this->wheres[$field] = $value;

        return $this;
    }

    public function whereGuid(string $guid): self
    {
        $this->setEndpoint("{$this->endpoint}(guid'{{$guid}}')");

        return $this;
    }

    public function find(string $primaryKey)
    {
        $response = $this
            ->whereGuid($primaryKey)
            ->first60();
//            ->get();

        return $response->first();
    }

    public function limitRequests(int $limit): self
    {
        $this->limitRequests = $limit;

        return $this;
    }

    public function first()
    {
        $this->query['$top'] = 1;

        return $this->request('GET');
    }

    /**
     * Execute the query and return the first 60 result
     *
     * @note This will return a maximum of 60 results since this is the default limit of the Exact Online API
     */
    public function first60(): Collection
    {
        $resource = $this->getResource();

        $response = $this->request('GET');

        $resources = collect();
        if (isset($response->d->results)) {
            foreach ($response->d->results as $item) {
                $resources->add(new $resource((array) $item));
            }
        } else {
            $resources->add(new $resource((array) $response->d));
        }

        return $resources;
    }

    /**
     * Return all the results of the query
     *
     * @note This may results in multiple requests to the Exact Online API since the default limit is 60
     */
    public function get(): Collection
    {
        $resource = $this->getResource();

        $i = 1;
        $resources = collect();

        do {
            $response = $this->request('GET');

            foreach ($response->d->results as $item) {
                $resources->add(new $resource((array) $item));
            }

            $lastResource = $resources->last();
            $this->query['$skiptoken'] = "guid'{$lastResource->getPrimaryKey()}'";
            $i++;
        } while ($this->reachedRequestLimit($i) && !empty($response->d->__next));

        return $resources;
    }

    private function reachedRequestLimit(int $i): bool
    {
        if (!empty($this->limitRequests)) {
            return $i <= $this->limitRequests;
        }

        return true;
    }

    public function request(string $method)
    {
        $options = [];

        // If access token is not set or token has expired, acquire new token
        if (!$this->authorization->hasValidToken()) {
            $this->authorization->acquireAccessToken();
        }

        // Add default json headers to the request
        $options['headers'] = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Prefer'        => 'return=representation',
            'Authorization' => 'Bearer ' . unserialize($this->authorization->getAccessToken()),
        ];

        if (!empty($this->query)) {
            $options['query'] = $this->query;
        }

        if (!empty($this->wheres)) {
            $options['query']['$filter='] = implode(',', $this->wheres);
        }

        try {
            $response = $this->client->request(
                $method,
                "{$this->apiUrlPath}/{$this->division}/{$this->endpoint}",
                $options
            );

            return json_decode($response->getBody()->getContents());
        } catch (GuzzleException $exception) {
            dd($exception);
        }
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint($endpoint): self
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    public function getResource(): ExactOnlineResource
    {
        return $this->resource;
    }

    public function setResource($resource): self
    {
        $this->resource = $resource;
        $this->setEndpoint($resource->getEndpoint());
        return $this;
    }
}
