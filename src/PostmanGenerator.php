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

        // Generate a better name for the request
        $name = $route['name'] ?? $route['uri'];
        if (empty($name) || $name === $route['uri']) {
            // Try to create a readable name from URI
            $uriParts = explode('/', trim($route['uri'], '/'));
            $name = ucfirst(end($uriParts));
            if (empty($name)) {
                $name = $route['method'] . ' ' . $route['uri'];
            }
        }

        $item = [
            'name' => $name,
            'request' => [
                'method' => $route['method'],
                'header' => [],
                'url' => [
                    'raw' => '{{base_url}}' . $route['uri'],
                    'host' => ['{{base_url}}'],
                    'path' => array_values(array_filter(explode('/', trim($route['uri'], '/')), function ($segment) {
                        return !empty($segment);
                    })),
                ],
                'body' => [],
            ],
            'response' => [],
        ];

        // Add description if route has name
        if (!empty($route['name'])) {
            $item['request']['description'] = "Route: {$route['name']}";
        }

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

        // Add default headers (avoid duplicates)
        $defaultHeaders = config('postman-generator.default_headers', []);
        $existingHeaderKeys = array_column($item['request']['header'], 'key');

        foreach ($defaultHeaders as $key => $value) {
            if (!in_array($key, $existingHeaderKeys)) {
                $item['request']['header'][] = [
                    'key' => $key,
                    'value' => $value,
                    'type' => 'text',
                ];
            }
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

        if (!is_array($rules)) {
            return '';
        }

        $example = null;
        $isRequired = false;

        foreach ($rules as $rule) {
            $rule = trim($rule);

            if (str_starts_with($rule, 'required')) {
                $isRequired = true;
            } elseif (str_starts_with($rule, 'email')) {
                $example = 'example@email.com';
            } elseif (str_starts_with($rule, 'numeric')) {
                $example = $example ?? 0;
            } elseif (str_starts_with($rule, 'integer')) {
                $example = $example ?? 1;
            } elseif (str_starts_with($rule, 'string')) {
                $example = $example ?? 'string';
            } elseif (str_starts_with($rule, 'boolean')) {
                $example = true;
            } elseif (str_starts_with($rule, 'array')) {
                $example = [];
            } elseif (str_starts_with($rule, 'date')) {
                $example = now()->toDateString();
            } elseif (str_starts_with($rule, 'url')) {
                $example = 'https://example.com';
            } elseif (str_starts_with($rule, 'ip')) {
                $example = '192.168.1.1';
            } elseif (str_starts_with($rule, 'json')) {
                $example = '{"key": "value"}';
            } elseif (preg_match('/min:(\d+)/', $rule, $matches)) {
                if (is_numeric($example)) {
                    $example = (int) $matches[1];
                } elseif (is_string($example)) {
                    $example = str_repeat('a', (int) $matches[1]);
                }
            } elseif (preg_match('/max:(\d+)/', $rule, $matches)) {
                // Keep current example but respect max
                if (is_string($example) && strlen($example) > (int) $matches[1]) {
                    $example = substr($example, 0, (int) $matches[1]);
                }
            } elseif (preg_match('/in:(.+)/', $rule, $matches)) {
                $values = explode(',', $matches[1]);
                $example = trim($values[0] ?? '');
            } elseif (preg_match('/size:(\d+)/', $rule, $matches)) {
                $example = str_repeat('a', (int) $matches[1]);
            }
        }

        // If no example found and field is required, provide a default
        if ($example === null && $isRequired) {
            $example = 'required_value';
        }

        return $example ?? '';
    }

    /**
     * Save collection to file
     */
    public function saveToFile(array $collection, string $path = null): bool
    {
        $path = $path ?? config('postman-generator.output_path');

        if (empty($path)) {
            return false;
        }

        $directory = dirname($path);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                return false;
            }
        }

        $json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return false;
        }

        return file_put_contents($path, $json) !== false;
    }

    /**
     * Update Postman collection via API
     */
    public function updateViaApi(array $collection, array $options = []): bool
    {
        return $this->apiClient->updateCollection($collection, $options);
    }
}
