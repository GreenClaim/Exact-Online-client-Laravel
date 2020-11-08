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

    private array $where;

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
        $this->where[$field] = $value;

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
            ->get();

        return $response->first();
    }

    public function first()
    {
        $this->where['$top'] = 1;

        return $this->request('GET');
    }

    public function get(): Collection
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

        if (!empty($this->where)) {
            $options['query']['$filter='] = implode(',', $this->where);
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
