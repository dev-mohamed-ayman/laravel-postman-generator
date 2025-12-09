<?php

namespace MohamedAyman\LaravelPostmanGenerator;

use Illuminate\Routing\Router;
use Illuminate\Http\Request;
use MohamedAyman\LaravelPostmanGenerator\Services\RouteScanner;
use MohamedAyman\LaravelPostmanGenerator\Services\ControllerAnalyzer;
use MohamedAyman\LaravelPostmanGenerator\Services\ValidationExtractor;
use MohamedAyman\LaravelPostmanGenerator\Services\MiddlewareAnalyzer;
use MohamedAyman\LaravelPostmanGenerator\Services\PostmanCollectionGenerator;
use MohamedAyman\LaravelPostmanGenerator\Services\PostmanApiClient;

class PostmanGenerator
{
    protected Router $router;
    protected Request $request;
    protected RouteScanner $routeScanner;
    protected ControllerAnalyzer $controllerAnalyzer;
    protected ValidationExtractor $validationExtractor;
    protected MiddlewareAnalyzer $middlewareAnalyzer;
    protected PostmanCollectionGenerator $collectionGenerator;
    protected PostmanApiClient $apiClient;

    public function __construct(Router $router, Request $request)
    {
        $this->router = $router;
        $this->request = $request;
        $this->routeScanner = new RouteScanner($router);
        $this->controllerAnalyzer = new ControllerAnalyzer();
        $this->validationExtractor = new ValidationExtractor();
        $this->middlewareAnalyzer = new MiddlewareAnalyzer();
        $this->collectionGenerator = new PostmanCollectionGenerator();
        $this->apiClient = new PostmanApiClient();
    }

    /**
     * Generate Postman collection from Laravel routes
     */
    public function generate(array $options = []): array
    {
        // Scan routes
        $routes = $this->routeScanner->scan($options);

        // Process each route
        $items = [];
        foreach ($routes as $route) {
            $item = $this->processRoute($route);
            if ($item) {
                $items[] = $item;
            }
        }

        // Generate collection
        return $this->collectionGenerator->generate($items, $options);
    }

    /**
     * Process a single route and extract all information
     */
    protected function processRoute(array $route): ?array
    {
        $controller = $route['controller'] ?? null;
        $action = $route['action'] ?? null;

        $item = [
            'name' => $route['name'] ?? $route['uri'],
            'request' => [
                'method' => $route['method'],
                'header' => [],
                'url' => [
                    'raw' => '{{base_url}}' . $route['uri'],
                    'host' => ['{{base_url}}'],
                    'path' => array_filter(explode('/', trim($route['uri'], '/'))),
                ],
                'body' => [],
            ],
            'response' => [],
        ];

        // Extract controller information
        $controllerClass = null;
        if ($controller && is_string($controller)) {
            // Controller is a string like "App\Http\Controllers\UserController@index"
            if (str_contains($controller, '@')) {
                [$controllerClass] = explode('@', $controller);
            } elseif (str_contains($controller, '::')) {
                [$controllerClass] = explode('::', $controller);
            } else {
                $controllerClass = $controller;
            }
        } elseif ($action && isset($action['class'])) {
            $controllerClass = $action['class'];
        }

        if ($controllerClass && $action) {
            $controllerInfo = $this->controllerAnalyzer->analyze($controllerClass, $action);

            // Extract validation rules
            $validationRules = $this->validationExtractor->extract($controllerClass, $action, $controllerInfo);

            // Add body parameters from validation
            if (!empty($validationRules)) {
                $item['request']['body'] = [
                    'mode' => 'raw',
                    'raw' => json_encode($this->formatValidationForPostman($validationRules), JSON_PRETTY_PRINT),
                    'options' => [
                        'raw' => [
                            'language' => 'json',
                        ],
                    ],
                ];
            }
        }

        // Extract middleware data
        $middlewareData = $this->middlewareAnalyzer->analyze($route['middleware'] ?? []);
        if (!empty($middlewareData['headers'])) {
            $item['request']['header'] = array_merge($item['request']['header'], $middlewareData['headers']);
        }

        // Add default headers
        $defaultHeaders = config('postman-generator.default_headers', []);
        foreach ($defaultHeaders as $key => $value) {
            $item['request']['header'][] = [
                'key' => $key,
                'value' => $value,
                'type' => 'text',
            ];
        }

        // Handle route parameters
        if (preg_match_all('/\{(\w+)\}/', $route['uri'], $matches)) {
            if (!isset($item['request']['url']['variable'])) {
                $item['request']['url']['variable'] = [];
            }
            foreach ($matches[1] as $param) {
                $item['request']['url']['variable'][] = [
                    'key' => $param,
                    'value' => ':' . $param,
                    'description' => 'Route parameter: ' . $param,
                ];
            }
        }

        return $item;
    }

    /**
     * Format validation rules for Postman body
     */
    protected function formatValidationForPostman(array $validationRules): array
    {
        $body = [];

        foreach ($validationRules as $field => $rules) {
            $body[$field] = $this->getExampleValue($rules);
        }

        return $body;
    }

    /**
     * Get example value based on validation rules
     */
    protected function getExampleValue($rules): mixed
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $example = null;

        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'required')) {
                // Field is required
            } elseif (str_starts_with($rule, 'email')) {
                $example = 'example@email.com';
            } elseif (str_starts_with($rule, 'numeric')) {
                $example = 0;
            } elseif (str_starts_with($rule, 'integer')) {
                $example = 1;
            } elseif (str_starts_with($rule, 'string')) {
                $example = 'string';
            } elseif (str_starts_with($rule, 'boolean')) {
                $example = true;
            } elseif (str_starts_with($rule, 'array')) {
                $example = [];
            } elseif (str_starts_with($rule, 'date')) {
                $example = now()->toDateString();
            } elseif (preg_match('/min:(\d+)/', $rule, $matches)) {
                if (is_numeric($example)) {
                    $example = (int) $matches[1];
                }
            } elseif (preg_match('/max:(\d+)/', $rule, $matches)) {
                // Keep current example
            }
        }

        return $example ?? '';
    }

    /**
     * Save collection to file
     */
    public function saveToFile(array $collection, string $path = null): bool
    {
        $path = $path ?? config('postman-generator.output_path');

        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($path, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
    }

    /**
     * Update Postman collection via API
     */
    public function updateViaApi(array $collection, array $options = []): bool
    {
        return $this->apiClient->updateCollection($collection, $options);
    }
}
