<?php

namespace MohamedAyman\LaravelPostmanGenerator\Services;

class PostmanCollectionGenerator
{
    /**
     * Generate Postman collection structure
     */
    public function generate(array $items, array $options = []): array
    {
        $collectionName = $options['collection_name'] ?? config('postman-generator.collection_name', 'Laravel API Collection');
        $collectionDescription = $options['collection_description'] ?? config('postman-generator.collection_description', 'Auto-generated Postman collection');
        $baseUrl = $options['base_url'] ?? config('postman-generator.base_url', 'http://localhost');

        // Group items by folder structure
        $groupedItems = $this->groupItemsByPath($items);

        return [
            'info' => [
                'name' => $collectionName,
                'description' => $collectionDescription,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                '_exporter_id' => 'laravel-postman-generator',
            ],
            'item' => $groupedItems,
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => $baseUrl,
                    'type' => 'string',
                ],
                [
                    'key' => 'token',
                    'value' => '',
                    'type' => 'string',
                ],
            ],
            'auth' => $this->getDefaultAuth(),
        ];
    }

    /**
     * Group items by path structure with better organization
     */
    protected function groupItemsByPath(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {
            $path = $item['request']['url']['path'] ?? [];
            $originalPath = $path;

            // Filter out empty path segments and parameters
            $path = array_filter($path, function ($segment) {
                return !empty($segment) && $segment !== '{' && !str_starts_with($segment, '{');
            });
            $path = array_values($path);

            // Determine folder structure - group by resource
            $folderName = $this->determineFolderName($path, $item);

            if (!isset($groups[$folderName])) {
                $groups[$folderName] = [
                    'name' => $folderName,
                    'description' => $this->getFolderDescription($folderName),
                    'item' => [],
                ];
            }

            // Keep full path for URL
            $fullPath = '/' . implode('/', $originalPath);
            $item['request']['url']['raw'] = '{{base_url}}' . $fullPath;
            $item['request']['url']['path'] = array_values($originalPath);

            // Update host if needed
            if (!isset($item['request']['url']['host']) || empty($item['request']['url']['host'])) {
                $item['request']['url']['host'] = ['{{base_url}}'];
            }

            $groups[$folderName]['item'][] = $item;
        }

        // Sort items within each folder by method (GET, POST, PUT, PATCH, DELETE)
        foreach ($groups as &$group) {
            usort($group['item'], function ($a, $b) {
                $order = ['GET' => 1, 'POST' => 2, 'PUT' => 3, 'PATCH' => 4, 'DELETE' => 5];
                $methodA = $a['request']['method'] ?? '';
                $methodB = $b['request']['method'] ?? '';
                return ($order[$methodA] ?? 99) <=> ($order[$methodB] ?? 99);
            });
        }

        // Sort folders alphabetically
        ksort($groups);

        return array_values($groups);
    }

    /**
     * Determine folder name based on path and item
     */
    protected function determineFolderName(array $path, array $item): string
    {
        if (empty($path)) {
            return 'Root';
        }

        // Remove 'api' prefix if exists
        if (strtolower($path[0]) === 'api' && count($path) > 1) {
            $path = array_slice($path, 1);
        }

        if (empty($path)) {
            return 'API';
        }

        // Get the main resource (first meaningful segment)
        $resource = $path[0];

        // Convert to readable folder name
        $folderName = $this->getFolderName($resource);

        // Handle special cases
        if (in_array(strtolower($resource), ['auth', 'authentication', 'login', 'register', 'logout'])) {
            return 'Authentication';
        }

        if (in_array(strtolower($resource), ['admin', 'administrator'])) {
            return 'Admin';
        }

        return $folderName;
    }

    /**
     * Get a clean folder name from path segment
     */
    protected function getFolderName(string $segment): string
    {
        // Remove common prefixes
        $segment = str_replace('api/', '', $segment);

        // Convert to readable format
        $name = str_replace(['-', '_'], ' ', $segment);
        $name = ucwords($name);

        // Handle common resources
        $resourceMap = [
            'Api' => 'API',
            'User' => 'Users',
            'Auth' => 'Authentication',
            'Admin' => 'Admin',
        ];

        return $resourceMap[$name] ?? $name;
    }

    /**
     * Get folder description
     */
    protected function getFolderDescription(string $folderName): string
    {
        $descriptions = [
            'Users' => 'User management endpoints',
            'Authentication' => 'Authentication and authorization endpoints',
            'API' => 'API endpoints',
            'Admin' => 'Administrative endpoints',
        ];

        return $descriptions[$folderName] ?? "Endpoints for {$folderName}";
    }

    /**
     * Get default authentication configuration
     */
    protected function getDefaultAuth(): array
    {
        $enableAuth = config('postman-generator.enable_auth', true);

        if (!$enableAuth) {
            return [
                'type' => 'noauth',
            ];
        }

        return [
            'type' => 'bearer',
            'bearer' => [
                [
                    'key' => 'token',
                    'value' => '{{token}}',
                    'type' => 'string',
                ],
            ],
        ];
    }
}
