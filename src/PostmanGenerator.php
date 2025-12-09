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

        // Generate a better, descriptive name for the request
        $name = $this->generateRequestName($route);
        $description = $this->generateRequestDescription($route);

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
                'description' => $description,
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

    /**
     * Generate a descriptive name for the request
     */
    protected function generateRequestName(array $route): string
    {
        $method = $route['method'];
        $uri = $route['uri'];
        $routeName = $route['name'] ?? null;
        $action = $route['action'] ?? null;

        // Parse URI to extract resource and action
        $uriParts = array_filter(explode('/', trim($uri, '/')));
        $uriParts = array_values($uriParts);

        // Remove 'api' prefix if exists
        if (!empty($uriParts) && strtolower($uriParts[0]) === 'api') {
            $uriParts = array_slice($uriParts, 1);
        }

        // Map HTTP methods to action words
        $methodActions = [
            'GET' => 'Get',
            'POST' => 'Create',
            'PUT' => 'Update',
            'PATCH' => 'Update',
            'DELETE' => 'Delete',
        ];

        $actionWord = $methodActions[$method] ?? $method;

        if (empty($uriParts)) {
            return "{$actionWord} Root";
        }

        // Handle different route patterns
        if (count($uriParts) === 1) {
            // Single resource: /users or /user
            $resource = $this->singularize(ucfirst($uriParts[0]));

            // Check if it's singular (like /user) vs plural (like /users)
            $isPlural = str_ends_with(strtolower($uriParts[0]), 's');

            if ($method === 'GET') {
                if ($isPlural) {
                    return "Get All {$resource}s";
                } else {
                    return "Get Current {$resource}";
                }
            } elseif ($method === 'POST') {
                return "Create {$resource}";
            }
            return "{$actionWord} {$resource}";
        } elseif (count($uriParts) >= 2) {
            $lastPart = end($uriParts);
            $firstPart = $uriParts[0];

            // Check if last part is a parameter (e.g., {id})
            if (preg_match('/\{(\w+)\}/', $lastPart, $matches)) {
                // Pattern: /users/{id} or /user/{id}
                $resource = $this->singularize(ucfirst($firstPart));
                $param = ucfirst($matches[1]);

                if ($method === 'GET') {
                    return "Get {$resource} by {$param}";
                } elseif ($method === 'PUT' || $method === 'PATCH') {
                    return "Update {$resource}";
                } elseif ($method === 'DELETE') {
                    return "Delete {$resource}";
                }
            } else {
                // Check if it's a nested resource: /users/{id}/posts
                if (count($uriParts) >= 3 && preg_match('/\{(\w+)\}/', $uriParts[1])) {
                    $parentResource = $this->singularize(ucfirst($firstPart));
                    $resource = $this->singularize(ucfirst($lastPart));

                    if ($method === 'GET') {
                        return "Get {$resource}s for {$parentResource}";
                    } elseif ($method === 'POST') {
                        return "Create {$resource} for {$parentResource}";
                    }
                } else {
                    // Pattern: /users/posts or simple nested
                    $resource = $this->singularize(ucfirst($lastPart));
                    $parentResource = $this->singularize(ucfirst($firstPart));

                    if ($method === 'GET') {
                        return "Get {$resource}s";
                    } elseif ($method === 'POST') {
                        return "Create {$resource}";
                    }
                }
            }
        }

        // Use route name if available and better
        if ($routeName) {
            $name = str_replace(['.', '-', '_'], ' ', $routeName);
            $name = ucwords($name);
            // Only use if it's more descriptive
            if (strlen($name) > 5) {
                return $name;
            }
        }

        // Fallback: use controller method if available
        if ($action && isset($action['method']) && $action['method'] !== '__invoke') {
            $methodName = $action['method'];
            $methodName = str_replace(['_', '-'], ' ', $methodName);
            $methodName = ucwords($methodName);
            return "{$actionWord} {$methodName}";
        }

        // Final fallback
        $resource = $this->singularize(ucfirst(end($uriParts)));
        return "{$actionWord} {$resource}";
    }

    /**
     * Convert plural to singular (simple version)
     */
    protected function singularize(string $word): string
    {
        // Simple plural to singular conversion
        if (str_ends_with($word, 'ies')) {
            return substr($word, 0, -3) . 'y';
        } elseif (str_ends_with($word, 'es') && strlen($word) > 3) {
            return substr($word, 0, -2);
        } elseif (str_ends_with($word, 's') && strlen($word) > 1) {
            return substr($word, 0, -1);
        }
        return $word;
    }

    /**
     * Generate a descriptive description for the request
     */
    protected function generateRequestDescription(array $route): string
    {
        $method = $route['method'];
        $uri = $route['uri'];
        $routeName = $route['name'] ?? null;
        $action = $route['action'] ?? null;

        $description = "**{$method}** `{$uri}`\n\n";

        if ($routeName) {
            $description .= "Route name: `{$routeName}`\n\n";
        }

        // Add method description
        $methodDescriptions = [
            'GET' => 'Retrieves data from the server.',
            'POST' => 'Creates a new resource on the server.',
            'PUT' => 'Updates an existing resource (full update).',
            'PATCH' => 'Updates an existing resource (partial update).',
            'DELETE' => 'Deletes a resource from the server.',
        ];

        if (isset($methodDescriptions[$method])) {
            $description .= $methodDescriptions[$method] . "\n\n";
        }

        // Add controller info if available
        if ($action && isset($action['class']) && isset($action['method'])) {
            $controller = class_basename($action['class']);
            $description .= "Controller: `{$controller}::{$action['method']}()`\n\n";
        }

        // Add middleware info
        if (!empty($route['middleware'])) {
            $middleware = array_filter($route['middleware'], function ($m) {
                return !in_array($m, ['web', 'api']);
            });
            if (!empty($middleware)) {
                $description .= "Middleware: " . implode(', ', $middleware) . "\n";
            }
        }

        return trim($description);
    }
}
