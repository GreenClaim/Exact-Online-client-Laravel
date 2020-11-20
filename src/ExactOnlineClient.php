<?php

namespace Yource\ExactOnlineClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Yource\ExactOnlineClient\Exceptions\ExactOnlineApiException;
use Yource\ExactOnlineClient\Resources\Resource;

class ExactOnlineClient
{
    private ExactOnlineAuthorization $authorization;

    private Client $client;

    private string $baseUri = 'https://start.exactonline.nl';

    private string $apiUrlPath = '/api/v1';

    private string $division;

    private Resource $resource;

    private string $endpoint;

    private array $wheres;

    private array $query;

    /**
     * The body of the request in JSON
     */
    private string $body;

    private int $limitRequests;

    public function __construct(Resource $resource)
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
        $response = $this->first60();

        return $response->first();
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

            if (empty($response->d->results)) {
                return $resources;
            }

            foreach ($response->d->results as $item) {
                $resources->add(new $resource((array) $item));
            }

            $lastResource = $resources->last();
            $this->query['$skiptoken'] = "guid'{$lastResource->getPrimaryKey()}'";
            $i++;
        } while ($this->reachedRequestLimit($i) && !empty($response->d->__next));

        return $resources;
    }

    public function create()
    {
        $resource = $this->getResource();

        if (!$this->resource->hasAttributes()) {
            throw new InvalidArgumentException('Attributes can\'t be empty');
        }

        // Convert the resource's attributes and set it in the body to be sent
        $this->body = $this->resource->toJson();
        $response = $this->request('POST');

        if (!empty($response->d)) {
            return new $resource((array) $response->d);
        }

        throw new ExactOnlineApiException('The response is empty');
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

        if (!empty($this->body)) {
            $options['body'] = $this->body;
        }

        if (!empty($this->query)) {
            $options['query'] = $this->query;
        }

        if (!empty($this->wheres)) {
            $options['query']['$filter'] = $this->getFilterQuery();
        }

        try {
            $response = $this->client->request(
                $method,
                "{$this->apiUrlPath}/{$this->division}/{$this->endpoint}",
                $options
            );

            return json_decode($response->getBody()->getContents());
        } catch (GuzzleException $exception) {
            throw new ExactOnlineApiException('Exact Online API error: ' . $exception->getMessage());
            dd($exception);
        }
    }

    private function reachedRequestLimit(int $i): bool
    {
        if (!empty($this->limitRequests)) {
            return $i <= $this->limitRequests;
        }

        return true;
    }

    public function getFilterQuery(): string
    {
        $filters = $this->wheres;
        array_walk($filters, function (&$a, $b) {
            $a = "$b eq '$a'";
        });

        return implode('&', $filters);
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

    public function getResource(): Resource
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
