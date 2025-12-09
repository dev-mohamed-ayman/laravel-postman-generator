<?php

namespace MohamedAyman\LaravelPostmanGenerator\Services;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Str;

class ControllerAnalyzer
{
    /**
     * Analyze controller and extract information
     */
    public function analyze(string $controller, array $action): array
    {
        $class = $action['class'] ?? null;
        $method = $action['method'] ?? '__invoke';

        if (!$class || !class_exists($class)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($class);
            $methodReflection = $reflection->getMethod($method);

            return [
                'class' => $class,
                'method' => $method,
                'parameters' => $this->extractMethodParameters($methodReflection),
                'docblock' => $methodReflection->getDocComment(),
                'request_class' => $this->findRequestClass($methodReflection),
            ];
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    /**
     * Extract method parameters
     */
    protected function extractMethodParameters(ReflectionMethod $method): array
    {
        $parameters = [];

        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            $typeName = $type ? ($type instanceof \ReflectionNamedType ? $type->getName() : (string) $type) : null;

            $parameters[] = [
                'name' => $param->getName(),
                'type' => $typeName,
                'required' => !$param->isOptional(),
                'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
            ];
        }

        return $parameters;
    }

    /**
     * Find Form Request class from method parameters
     */
    protected function findRequestClass(ReflectionMethod $method): ?string
    {
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            
            if ($type && $type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                
                if (class_exists($typeName) && is_subclass_of($typeName, \Illuminate\Foundation\Http\FormRequest::class)) {
                    return $typeName;
                }
            }
        }

        return null;
    }

    /**
     * Extract custom validation from controller method
     */
    public function extractCustomValidation(string $controller, string $method): array
    {
        if (!class_exists($controller)) {
            return [];
        }
        
        $class = $controller;

        try {
            $reflection = new ReflectionClass($class);
            $methodReflection = $reflection->getMethod($method);
            $sourceCode = $this->getMethodSourceCode($methodReflection);

            // Look for validation patterns
            $validationRules = [];

            // Pattern: $request->validate([...])
            if (preg_match_all('/\$request->validate\s*\(\s*\[(.*?)\]\s*\)/s', $sourceCode, $matches)) {
                foreach ($matches[1] as $rulesString) {
                    $rules = $this->parseValidationRules($rulesString);
                    $validationRules = array_merge($validationRules, $rules);
                }
            }

            // Pattern: Validator::make(...)
            if (preg_match_all('/Validator::make\s*\([^,]+,\s*\[(.*?)\]\s*\)/s', $sourceCode, $matches)) {
                foreach ($matches[1] as $rulesString) {
                    $rules = $this->parseValidationRules($rulesString);
                    $validationRules = array_merge($validationRules, $rules);
                }
            }

            return $validationRules;
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    /**
     * Get method source code
     */
    protected function getMethodSourceCode(ReflectionMethod $method): string
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

    /**
     * Parse validation rules from string
     */
    protected function parseValidationRules(string $rulesString): array
    {
        $rules = [];
        
        // Simple parsing - extract field => rules pairs
        if (preg_match_all('/[\'"](\w+)[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $rulesString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $rules[$match[1]] = $match[2];
            }
        }

        return $rules;
    }
}

