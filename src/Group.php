<?php declare(strict_types=1);

namespace ifcanduela\router;

use Exception;
use InvalidArgumentException;

/**
 * The Group class represents a grouping of routes with common properties.
 */
class Group implements Routable
{
    const METHOD_GET = "GET";

    const METHOD_POST = "POST";

    const METHOD_PUT = "PUT";

    const METHOD_DELETE = "DELETE";

    /** @var string */
    protected $prefix = "";

    /** @var string[] */
    protected $before = [];

    /** @var string[] */
    protected $after = [];

    /** @var array */
    protected $routes = [];

    /** @var string[] */
    protected $methods = ["*"];

    /** @var mixed */
    protected $handler;

    /**
     * Read routes from a file.
     *
     * The router is available in the file as `$router`.
     *
     * @param string $filename
     * @param string $routerAlias
     * @return void
     */
    public function loadFile(string $filename, string $routerAlias = "router")
    {
        if (is_readable($filename)) {
            $$routerAlias = $this;

            require $filename;
        } else {
            throw new InvalidArgumentException("Invalid route definition file: `$filename`");
        }
    }

    /**
     * Set the routes in the group.
     *
     * @param Routable[] $routes
     * @return self
     */
    public function routes(array $routes): Group
    {
        foreach ($routes as $route) {
            if (!($route instanceof Routable)) {
                throw new InvalidArgumentException("Routes must be instances of `" . Route::class . "` or `" . Group::class . "`");
            }

            $this->routes[] = $route;
        }

        return $this;
    }

    /**
     * Set a prefix for all routes in the group.
     *
     * @param string $prefix
     * @return self
     */
    public function prefix(string $prefix): Group
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Add a route to the group.
     *
     * @param Route $route
     * @return void
     */
    public function addRoute(Route $route)
    {
        $this->routes[] = $route;
    }

    /**
     * Add a group.
     *
     * @param string|callable $prefixOrCallback
     * @param callable|null $callback
     * @return Group
     */
    public function group($prefixOrCallback, callable $callback = null): Group
    {
        $g = new Group();

        if (is_callable($prefixOrCallback)) {
            $callback = $prefixOrCallback;
        } else {
            $g->prefix($prefixOrCallback);
        }

        $this->routes[] = $g;

        if (isset($callback)) {
            $callback($g);
        }

        return $g;
    }

    /**
     * Add routes from a group or router in a sub-route.
     *
     * @param string $prefix
     * @param Group $router
     * @return Group
     */
    public function mount(string $prefix, Group $router): Group
    {
        $router->prefix($prefix);
        $this->routes[] = $router;

        return $this;
    }

    /**
     * Set a handler for routes without one.
     *
     * @param string|callable $handler
     * @return self
     */
    public function handler($handler): Group
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Add a GET/POST route.
     *
     * @param string $path
     * @param string ...$method
     * @return Route
     */
    public function from(string $path, string ...$method): Route
    {
        $methods = count($method) ? $method : [static::METHOD_GET, static::METHOD_POST];
        $route = Route::from($path)->methods(...$methods);

        $this->addRoute($route);

        return $route;
    }

    /**
     * Add a GET route.
     *
     * @param string $path
     * @return Route
     */
    public function get(string $path): Route
    {
        return $this->from($path, static::METHOD_GET);
    }

    /**
     * Add a POST route.
     *
     * @param string $path
     * @return Route
     */
    public function post(string $path): Route
    {
        return $this->from($path, static::METHOD_POST);
    }

    /**
     * Add a PUT route.
     *
     * @param string $path
     * @return Route
     */
    public function put(string $path): Route
    {
        return $this->from($path, static::METHOD_PUT);
    }

    /**
     * Add a DELETE route.
     *
     * @param string $path
     * @return Route
     */
    public function delete(string $path): Route
    {
        return $this->from($path, static::METHOD_DELETE);
    }

    /**
     * Assigns "before" tags to the group.
     *
     * @param string ...$tags
     * @return self
     */
    public function before(...$tags): Group
    {
        $this->before += $tags;

        return $this;
    }

    /**
     * Assigns "after" tags to the group.
     *
     * @param string ...$tags
     * @return self
     */
    public function after(...$tags): Group
    {
        $this->after += $tags;

        return $this;
    }

    /**
     * Get all defined routes.
     *
     * @return Route[]
     * @throws Exception
     */
    public function getRoutes(): array
    {
        $routeList = [];

        /** @var Route|Group $route */
        foreach ($this->routes as $route) {
            $routes = ($route instanceof Group)
                ? $route->getRoutes()
                : [$route];

            foreach ($routes as $r) {
                $r = $this->mergeRoute($r);
                $routeList[] = $r;
            }
        }

        return $routeList;
    }

    /**
     * Merge group properties into a route.
     *
     * @param Route $route
     * @return Route
     */
    protected function mergeRoute(Route $route): Route
    {
        $route = clone $route;

        if ($this->prefix) {
            $route->path(preg_replace("~/+~", "/", "{$this->prefix}{$route->getPath()}"));
        }

        if ($this->before) {
            $route->before(...array_unique(array_merge($this->before, $route->getBefore())));
        }

        if ($this->after) {
            $route->after(...array_unique(array_merge($this->after, $route->getAfter())));
        }

        if ($this->handler && !$route->getHandler()) {
            $route->setHandler($this->handler);
        }

        if ($this->methods && $route->getMethods() === ["*"]) {
            $route->methods(...$this->methods);
        }

        return $route;
    }
}
