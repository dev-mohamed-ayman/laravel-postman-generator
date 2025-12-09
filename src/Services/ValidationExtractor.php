<?php

namespace MohamedAyman\LaravelPostmanGenerator\Services;

use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;

class ValidationExtractor
{
    protected ControllerAnalyzer $controllerAnalyzer;

    public function __construct()
    {
        $this->controllerAnalyzer = new ControllerAnalyzer();
    }

    /**
     * Extract validation rules from controller and form request
     */
    public function extract(string $controller, array $action, array $controllerInfo): array
    {
        $validationRules = [];

        // Extract from Form Request
        if (isset($controllerInfo['request_class'])) {
            $requestClass = $controllerInfo['request_class'];
            $validationRules = array_merge($validationRules, $this->extractFromFormRequest($requestClass));
        }

        // Extract custom validation from controller
        $method = $action['method'] ?? '__invoke';
        $customValidation = $this->controllerAnalyzer->extractCustomValidation($controller, $method);
        $validationRules = array_merge($validationRules, $customValidation);

        return $validationRules;
    }

    /**
     * Extract validation rules from Form Request class
     */
    protected function extractFromFormRequest(string $requestClass): array
    {
        if (!class_exists($requestClass) || !is_subclass_of($requestClass, FormRequest::class)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($requestClass);
            
            // Try to get rules from rules() method
            if ($reflection->hasMethod('rules')) {
                $rulesMethod = $reflection->getMethod('rules');
                
                // Check if method is static
                if ($rulesMethod->isStatic()) {
                    $rules = $rulesMethod->invoke(null);
                } else {
                    $rulesMethod->setAccessible(true);
                    try {
                        $instance = $reflection->newInstanceWithoutConstructor();
                        $rules = $rulesMethod->invoke($instance);
                    } catch (\Exception $e) {
                        // If we can't create instance, try to get rules from parent class
                        $parent = $reflection->getParentClass();
                        if ($parent && $parent->hasMethod('rules')) {
                            $parentRulesMethod = $parent->getMethod('rules');
                            $parentRulesMethod->setAccessible(true);
                            try {
                                $parentInstance = $parent->newInstanceWithoutConstructor();
                                $rules = $parentRulesMethod->invoke($parentInstance);
                            } catch (\Exception $e2) {
                                return [];
                            }
                        } else {
                            return [];
                        }
                    }
                }
                
                if (is_array($rules)) {
                    return $this->normalizeRules($rules);
                }
            }

            // Try to get rules from messages() or attributes() if available
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Normalize validation rules to consistent format
     */
    protected function normalizeRules(array $rules): array
    {
        $normalized = [];

        foreach ($rules as $field => $rule) {
            if (is_string($rule)) {
                $normalized[$field] = $rule;
            } elseif (is_array($rule)) {
                $normalized[$field] = implode('|', $rule);
            }
        }

        return $normalized;
    }

    /**
     * Extract validation messages (for documentation)
     */
    public function extractMessages(string $requestClass): array
    {
        if (!class_exists($requestClass) || !is_subclass_of($requestClass, FormRequest::class)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($requestClass);
            
            if ($reflection->hasMethod('messages')) {
                $messagesMethod = $reflection->getMethod('messages');
                $messagesMethod->setAccessible(true);
                
                $instance = $reflection->newInstanceWithoutConstructor();
                return $messagesMethod->invoke($instance) ?? [];
            }
        } catch (\Exception $e) {
            return [];
        }

        return [];
    }
}

