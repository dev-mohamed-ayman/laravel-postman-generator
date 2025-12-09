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
     * Group items by path structure
     */
    protected function groupItemsByPath(array $items): array
    {
        $groups = [];
        $flatItems = [];

        foreach ($items as $item) {
            $path = $item['request']['url']['path'] ?? [];
            
            if (count($path) > 1) {
                // Has folder structure
                $folderName = ucfirst($path[0]);
                
                if (!isset($groups[$folderName])) {
                    $groups[$folderName] = [
                        'name' => $folderName,
                        'item' => [],
                    ];
                }
                
                // Update path to remove first segment
                $newPath = array_slice($path, 1);
                $item['request']['url']['path'] = $newPath;
                $pathString = !empty($newPath) ? '/' . implode('/', $newPath) : '';
                $item['request']['url']['raw'] = '{{base_url}}' . $pathString;
                
                $groups[$folderName]['item'][] = $item;
            } else {
                // Root level item
                $flatItems[] = $item;
            }
        }

        // Convert groups to array and merge with flat items
        $result = array_values($groups);
        $result = array_merge($result, $flatItems);

        return $result;
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

