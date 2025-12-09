<?php

namespace MohamedAyman\LaravelPostmanGenerator\Services;

use Illuminate\Routing\Router;
use Illuminate\Support\Str;

class RouteScanner
{
    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Scan all routes and extract information
     */
    public function scan(array $options = []): array
    {
        $routes = [];
        $includeRoutes = $options['include_routes'] ?? config('postman-generator.include_routes', ['api']);
        $excludeRoutes = $options['exclude_routes'] ?? config('postman-generator.exclude_routes', []);

        foreach ($this->router->getRoutes() as $route) {
            $uri = $route->uri();
            $methods = $route->methods();

            // Check if route should be excluded
            if ($this->shouldExcludeRoute($uri, $excludeRoutes)) {
                continue;
            }

            // Check if route should be included
            if (!$this->shouldIncludeRoute($route, $includeRoutes)) {
                continue;
            }

            foreach ($methods as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'])) {
                    continue;
                }

                $action = $route->getAction();
                $controller = $action['controller'] ?? null;

                $routes[] = [
                    'uri' => '/' . ltrim($uri, '/'),
                    'method' => $method,
                    'name' => $route->getName(),
                    'controller' => $controller,
                    'action' => $this->extractAction($controller),
                    'middleware' => $route->middleware(),
                    'parameters' => $this->extractRouteParameters($uri),
                ];
            }
        }

        return $routes;
    }

    /**
     * Check if route should be excluded
     */
    protected function shouldExcludeRoute(string $uri, array $excludePatterns): bool
    {
        foreach ($excludePatterns as $pattern) {
            if (Str::contains($uri, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if route should be included
     */
    protected function shouldIncludeRoute($route, array $includeRoutes): bool
    {
        if (empty($includeRoutes) || in_array('all', $includeRoutes)) {
            return true;
        }

        $middleware = $route->middleware();
        $uri = $route->uri();
        
        // Check for API routes
        if (in_array('api', $includeRoutes)) {
            // Check if route has 'api' middleware or starts with 'api/'
            if (in_array('api', $middleware) || str_starts_with($uri, 'api/')) {
                return true;
            }
        }

        // Check for web routes
        if (in_array('web', $includeRoutes)) {
            // Web routes typically don't have 'api' middleware and don't start with 'api/'
            if (!in_array('api', $middleware) && !str_starts_with($uri, 'api/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract action from controller string
     */
    protected function extractAction(?string $controller): ?array
    {
        if (!$controller) {
            return null;
        }

        if (is_string($controller) && str_contains($controller, '@')) {
            [$class, $method] = explode('@', $controller);
            return [
                'class' => $class,
                'method' => $method,
            ];
        }

        if (is_string($controller) && str_contains($controller, '::')) {
            [$class, $method] = explode('::', $controller);
            return [
                'class' => $class,
                'method' => $method,
            ];
        }

        if (is_callable($controller)) {
            return [
                'class' => get_class($controller[0] ?? $controller),
                'method' => $controller[1] ?? '__invoke',
            ];
        }

        return null;
    }

    /**
     * Extract route parameters from URI
     */
    protected function extractRouteParameters(string $uri): array
    {
        $parameters = [];
        
        if (preg_match_all('/\{(\w+)\}/', $uri, $matches)) {
            $parameters = $matches[1];
        }

        return $parameters;
    }
}

