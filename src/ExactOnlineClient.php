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

    private array $fields;

    private array $wheres = [];

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

    public function select($fields = ['*']): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function where(string $field, $value): self
    {
        $this->wheres[$field] = $value;

        return $this;
    }

    public function whereGuid(string $guid): self
    {
        $primaryKeyFieldName = $this->getResource()->getPrimaryKeyFieldName();
        $this->wheres[$primaryKeyFieldName] = "guid'{$guid}'";

        return $this;
    }

    /**
     * Find by the uniquely assigned code
     * The code is unique and always 18 characters long prefixed by spaces
     *
     * @param string $value
     */
    public function whereCode($value): self
    {
        $this->wheres['Code'] = (string) str_pad($value, 18, ' ', STR_PAD_LEFT);

        return $this;
    }

    public function find(string $primaryKey): ?Resource
    {
        $this->setEndpoint("{$this->endpoint}(guid'{{$primaryKey}}')");
        $response = $this->get();

        return optional($response)->first();
    }

    public function limitRequests(int $limit): self
    {
        $this->limitRequests = $limit;

        return $this;
    }

    public function first(): ?Resource
    {
        $this->query['$top'] = 1;
        $response = $this->get();

        return optional($response)->first();
    }

    /**
     * Execute the query and return the first 60 result
     *
     * @note This will return a maximum of 60 results since this is the default limit of the Exact Online API
     */
    public function get(): ?Collection
    {
        $resource = $this->getResource();

        $response = $this->request('GET');

        if (empty($response) || empty($response->d)) {
            return null;
        }

        $response = $response->d;

        $resources = collect();
        if (isset($response->results)) {
            foreach ($response->results as $item) {
                $resources->add(new $resource((array) $item));
            }
        } else {
            $response = is_array($response) ? $response[0] : $response;
            $resources->add(new $resource((array) $response));
        }

        return $resources;
    }

    /**
     * Execute callback for all the results from each page
     */
    public function eachPage(callable $callback): bool
    {
        $resource = $this->getResource();

        $page = 1;
        do {
            $resources = collect();
            $response = $this->request('GET');

            if (empty($response->d->results)) {
                return true;
            }

            $results = $response->d->results;

            foreach ($response->d->results as $item) {
                $resources->add(new $resource((array) $item));
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // iterate over all the pages with max 60 results
            if ($callback($resources, $page) === false) {
                return false;
            }

            unset($results);

            $lastResource = $resources->last();
            $this->query['$skiptoken'] = "guid'{$lastResource->getPrimaryKey()}'";
            $page++;
        } while ($this->reachedRequestLimit($page) && !empty($response->d->__next));

        return true;
    }

    /**
     * Return all the results of the query
     *
     * @note This may results in multiple requests to the Exact Online API since the default limit is 60
     */
    public function all(): Collection
    {
        $resource = $this->getResource();

        $page = 1;
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
            $page++;
        } while ($this->reachedRequestLimit($page) && !empty($response->d->__next));

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

        if (!empty($this->fields)) {
            $options['query']['$select'] = $this->getFieldsQuery();
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
        }
    }

    private function reachedRequestLimit(int $i): bool
    {
        if (!empty($this->limitRequests)) {
            return $i <= $this->limitRequests;
        }

        return true;
    }

    public function getFieldsQuery(): string
    {
        $fields = $this->fields;

        return implode(',', $fields);
    }

    public function getFilterQuery(): string
    {
        $filters = $this->wheres;
        array_walk($filters, function (&$a, $b) {
            if (!is_int($a)) {
                $a = "'{$a}'";
            }
            $a = "{$b} eq {$a}";
        });

        return implode(' and ', $filters);
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
