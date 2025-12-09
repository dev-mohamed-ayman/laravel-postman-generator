<?php

namespace MohamedAyman\LaravelPostmanGenerator\Services;

use ReflectionClass;
use Illuminate\Support\Str;

class MiddlewareAnalyzer
{
    /**
     * Analyze middleware and extract data requirements
     */
    public function analyze(array $middleware): array
    {
        $data = [
            'headers' => [],
            'parameters' => [],
            'auth' => false,
            'auth_type' => null,
        ];

        foreach ($middleware as $middlewareItem) {
            $middlewareData = $this->analyzeSingleMiddleware($middlewareItem);
            $data = array_merge_recursive($data, $middlewareData);
        }

        return $data;
    }

    /**
     * Analyze a single middleware
     */
    protected function analyzeSingleMiddleware(string $middleware): array
    {
        $data = [
            'headers' => [],
            'parameters' => [],
            'auth' => false,
            'auth_type' => null,
        ];

        // Check for authentication middleware
        if (Str::contains($middleware, ['auth', 'sanctum', 'passport'])) {
            $data['auth'] = true;
            
            if (Str::contains($middleware, 'sanctum')) {
                $data['auth_type'] = 'bearer';
                $data['headers'][] = [
                    'key' => 'Authorization',
                    'value' => 'Bearer {{token}}',
                    'type' => 'text',
                    'description' => 'Sanctum authentication token',
                ];
            } elseif (Str::contains($middleware, 'passport')) {
                $data['auth_type'] = 'bearer';
                $data['headers'][] = [
                    'key' => 'Authorization',
                    'value' => 'Bearer {{token}}',
                    'type' => 'text',
                    'description' => 'Passport OAuth token',
                ];
            } else {
                $data['auth_type'] = 'bearer';
                $data['headers'][] = [
                    'key' => 'Authorization',
                    'value' => 'Bearer {{token}}',
                    'type' => 'text',
                    'description' => 'Authentication token',
                ];
            }
        }

        // Check for CSRF protection
        if (Str::contains($middleware, 'csrf')) {
            $data['headers'][] = [
                'key' => 'X-CSRF-TOKEN',
                'value' => '{{csrf_token}}',
                'type' => 'text',
                'description' => 'CSRF protection token',
            ];
        }

        // Check for rate limiting
        if (Str::contains($middleware, 'throttle')) {
            // Rate limiting doesn't add headers, but we can document it
        }

        // Try to analyze custom middleware
        if (class_exists($middleware)) {
            $customData = $this->analyzeCustomMiddleware($middleware);
            $data = array_merge_recursive($data, $customData);
        }

        return $data;
    }

    /**
     * Analyze custom middleware class
     */
    protected function analyzeCustomMiddleware(string $middlewareClass): array
    {
        $data = [
            'headers' => [],
            'parameters' => [],
        ];

        try {
            $reflection = new ReflectionClass($middlewareClass);
            
            // Look for handle method
            if ($reflection->hasMethod('handle')) {
                $handleMethod = $reflection->getMethod('handle');
                $sourceCode = $this->getMethodSourceCode($handleMethod);
                
                // Look for header requirements
                if (preg_match_all('/\$request->header\([\'"](\w+)[\'"]\)/', $sourceCode, $matches)) {
                    foreach ($matches[1] as $header) {
                        $data['headers'][] = [
                            'key' => $header,
                            'value' => '',
                            'type' => 'text',
                            'description' => 'Required by ' . class_basename($middlewareClass),
                        ];
                    }
                }

                // Look for input requirements
                if (preg_match_all('/\$request->input\([\'"](\w+)[\'"]\)/', $sourceCode, $matches)) {
                    foreach ($matches[1] as $input) {
                        $data['parameters'][] = [
                            'key' => $input,
                            'value' => '',
                            'description' => 'Required by ' . class_basename($middlewareClass),
                        ];
                    }
                }
            }
        } catch (\ReflectionException $e) {
            // Ignore reflection errors
        }

        return $data;
    }

    /**
     * Get method source code
     */
    protected function getMethodSourceCode(\ReflectionMethod $method): string
    {
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if (!$filename || !file_exists($filename)) {
            return '';
        }

        $lines = file($filename);
        $sourceCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        return $sourceCode;
    }
}

