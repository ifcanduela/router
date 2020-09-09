<?php declare(strict_types=1);

namespace ifcanduela\router;

use Exception;
use ifcanduela\router\exception\InvalidHttpMethod;
use ifcanduela\router\exception\RouteNotFound;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use LogicException;
use RuntimeException;
use function FastRoute\simpleDispatcher;

/**
 * The Router class wraps the `nikic/FastRoute` library for slightly
 * taylor-made functionality.
 */
class Router extends Group
{
    /** @var Route[] */
    protected $processedRoutes;

    /**
     * Get a route matching the provided request information.
     *
     * @param string $pathInfo
     * @param string $httpMethod
     * @return Route
     * @throws RuntimeException
     * @throws Exception
     */
    public function resolve(string $pathInfo, string $httpMethod): Route
    {
        $dispatcher = $this->getDispatcher();

        # Find the matched route
        /** @var array{int, Route, array} */
        $matchedRoute = $dispatcher->dispatch(strtoupper($httpMethod), $pathInfo);

        if ($matchedRoute[0] === Dispatcher::NOT_FOUND) {
            throw new RouteNotFound("Route not found");
        }

        if ($matchedRoute[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new InvalidHttpMethod("Method not allowed");
        }

        /** @var Route $route */
        $route = clone $matchedRoute[1];
        $route->setParams($matchedRoute[2]);

        # If the handler is a string, replace placeholders with path params
        if (is_string($route->getHandler())) {
            $replacements = [];

            foreach ($route->getParams() as $key => $value) {
                $replacements["{{$key}}"] = $value;
            }

            $handler = strtr($route->getHandler(), $replacements);
            $route->setHandler($handler);
        }

        return $route;
    }

    /**
     * Build a route dispatcher.
     *
     * @return Dispatcher
     * @throws Exception
     */
    private function getDispatcher(): Dispatcher
    {
        $routes = $this->getRoutes();

        return simpleDispatcher(
            function (RouteCollector $r) use ($routes) {
                foreach ($routes as $data) {
                    $r->addRoute($data->getMethods(), $data->getPath(), $data);
                }
            }
        );
    }

    /**
     * Create a URL from a route name and a list of parameters.
     *
     * For example, `createUrlFromRoute("user.view", [$userId])`
     *
     * @param string $routeName
     * @param array $routeParams
     * @return string A URL, path only
     * @throws Exception
     */
    public function createUrlFromRoute(string $routeName, array $routeParams = []): string
    {
        if (!$this->processedRoutes) {
            $this->processedRoutes = $this->getRoutes();
        }

        $route = null;

        foreach ($this->processedRoutes as $r) {
            if ($r->getName() == $routeName) {
                $route = $r;
                break;
            }
        }

        if (!$route) {
            throw new LogicException("Named route not found: `{$routeName}`");
        }

        $routeParser = new StdRouteParser();
        $routes = $routeParser->parse($route->getPath());

        foreach ($routes as $route) {
            $url = "";
            $paramIndex = 0;

            foreach ($route as $segment) {
                if (is_string($segment)) {
                    $url .= $segment;
                    continue;
                }

                if ($paramIndex === count($routeParams)) {
                    throw new LogicException("Not enough parameters given");
                }

                $url .= $routeParams[$paramIndex++];
            }

            if ($paramIndex === count($routeParams)) {
                return $url;
            }
        }

        throw new LogicException("Too many parameters given");
    }

    /**
     * Check if a path matches the given named route.
     *
     * @param string $routeName
     * @param string $path
     * @param string $method
     * @return bool
     */
    public function isRoute(string $routeName, string $path , string $method): bool
    {
        try {
            $route = $this->resolve($path, strtoupper($method));

            return $route->getName() === $routeName;
        } catch (Exception $e) {
        }

        return false;
    }
}
