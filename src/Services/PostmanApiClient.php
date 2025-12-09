<?php

namespace MohamedAyman\LaravelPostmanGenerator\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class PostmanApiClient
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl = 'https://api.getpostman.com';

    public function __construct()
    {
        $this->apiKey = (string) (config('postman-generator.postman.api_key') ?? '');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Update Postman collection via API
     */
    public function updateCollection(array $collection, array $options = []): bool
    {
        if (empty($this->apiKey)) {
            Log::warning('Postman API key is not configured');
            return false;
        }

        $collectionId = $options['collection_id'] ?? config('postman-generator.postman.collection_id');

        if (empty($collectionId)) {
            Log::warning('Postman collection ID is not configured');
            return false;
        }

        try {
            $response = $this->client->put("/collections/{$collectionId}", [
                'json' => [
                    'collection' => $collection,
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                return true;
            }

            Log::warning("Postman API returned status code: {$statusCode}");
            return false;
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody()->getContents();
                $errorMessage .= ' - Response: ' . $body;
            }
            Log::error('Failed to update Postman collection: ' . $errorMessage);
            return false;
        } catch (GuzzleException $e) {
            Log::error('Failed to update Postman collection: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new Postman collection
     */
    public function createCollection(array $collection, array $options = []): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('Postman API key is not configured');
            return null;
        }

        $workspaceId = $options['workspace_id'] ?? config('postman-generator.postman.workspace_id');

        try {
            $payload = [
                'collection' => $collection,
            ];

            if ($workspaceId) {
                $payload['workspace'] = $workspaceId;
            }

            $response = $this->client->post('/collections', [
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['collection']['uid'] ?? null;
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody()->getContents();
                $errorMessage .= ' - Response: ' . $body;
            }
            Log::error('Failed to create Postman collection: ' . $errorMessage);
            return null;
        } catch (GuzzleException $e) {
            Log::error('Failed to create Postman collection: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get collection information
     */
    public function getCollection(string $collectionId): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->client->get("/collections/{$collectionId}");
            $data = json_decode($response->getBody()->getContents(), true);

            return $data['collection'] ?? null;
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $body = $response->getBody()->getContents();
                $errorMessage .= ' - Response: ' . $body;
            }
            Log::error('Failed to get Postman collection: ' . $errorMessage);
            return null;
        } catch (GuzzleException $e) {
            Log::error('Failed to get Postman collection: ' . $e->getMessage());
            return null;
        }
    }
}
