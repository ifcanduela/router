<?php declare(strict_types=1);

namespace ifcanduela\router;

use Exception;

/**
 * The Route class specifies the properties of a routable URL.
 */
class Route implements Routable
{
    /** @var string */
    protected $path = "";

    /** @var mixed */
    protected $handler = null;

    /** @var string[] */
    protected $methods = ["*"];

    /** @var array */
    protected $defaults = [];

    /** @var array */
    protected $params = [];

    /** @var array */
    protected $before = [];

    /** @var array */
    protected $after = [];

    /** @var string */
    protected $handlerNamespace = "";

    /** @var string */
    protected $name = "";

    /**
     * Create a Route from an array.
     *
     * @param array $data
     * @return Route
     * @throws Exception
     */
    public static function fromArray(array $data): Route
    {
        $methods = ["*"];

        if (isset($data["methods"])) {
            if (is_string($data["methods"])) {
                $methods = preg_split('/\W+/', strtoupper($data["methods"]));
            } elseif (is_array($data["methods"])) {
                $methods = array_map("strtoupper", $data["methods"]);
            }
        }

        $path = $data["path"] ?? $data["from"];

        $route = new Route();
        $route->path($path);
        $route->methods(...$methods);

        if (isset($data["handler"]) || isset($data["to"])) {
            $route->to($data["handler"] ?? $data["to"]);
        }

        if (isset($data["defaults"])) {
            $route->defaults($data["defaults"]);
        }

        if (isset($data["before"])) {
            $route->before(...$data["before"]);
        }

        if (isset($data["after"])) {
            $route->after(...$data["after"]);
        }

        if (isset($data["namespace"])) {
            $route->namespace($data["namespace"]);
        }

        if (isset($data["name"])) {
            $route->name($data["name"]);
        }

        return $route;
    }

    /**
     * Create a route for a path.
     *
     * @param string $path
     * @return Route
     */
    public static function from(string $path): Route
    {
        $r = new Route;
        $r->path($path);

        return $r;
    }

    /**
     * Build a GET route.
     *
     * @param  string $path
     * @return Route
     */
    public static function get(string $path): Route
    {
        return static::from($path)->methods("get");
    }

    /**
     * Build a POST route.
     *
     * @param  string $path
     * @return Route
     */
    public static function post(string $path): Route
    {
        return static::from($path)->methods("post");
    }

    /**
     * Build a PUT route.
     *
     * @param  string $path
     * @return Route
     */
    public static function put(string $path): Route
    {
        return static::from($path)->methods("put");
    }

    /**
     * Build a DELETE route.
     *
     * @param  string $path
     * @return Route
     */
    public static function delete(string $path): Route
    {
        return static::from($path)->methods("delete");
    }

    /**
     * Get the path matched by the route.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the route path.
     *
     * @param string $path
     * @return self
     */
    public function path(string $path): Route
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the handler associated to the route.
     *
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Set the route handler.
     *
     * @param mixed $handler
     * @return void
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
    }

    /**
     * Set the route handler.
     *
     * This method is an alias for setHandler()
     *
     * @param string|callable $handler
     * @return self
     */
    public function to($handler): Route
    {
        $this->setHandler($handler);

        return $this;
    }

    /**
     * Get the namespace for the handler class.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->handlerNamespace;
    }

    /**
     * Set a namespace for the handler.
     *
     * @param string $namespace
     * @return self
     */
    public function namespace(string $namespace): Route
    {
        $this->handlerNamespace = $namespace;

        return $this;
    }

    /**
     * Get the route name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set a name for the route.
     *
     * @param string $name
     * @return self
     */
    public function name(string $name): Route
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the applicable methods for the route.
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Set the route methods.
     *
     * @param string ...$methods
     * @return self
     */
    public function methods(string ...$methods): Route
    {
        $this->methods = array_unique(array_map("strtoupper", $methods));

        return $this;
    }

    /**
     * Get the default values for path placeholders.
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Set the route placeholder default values.
     *
     * @param array $defaults
     * @return self
     */
    public function defaults(array $defaults): Route
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * Set a default value for a route placeholder.
     *
     * @param string $placeholderName
     * @param mixed $value
     * @return self
     */
    public function default($placeholderName, $value = null): Route
    {
        $this->defaults[$placeholderName] = $value;

        return $this;
    }

    /**
     * Get the 'before' tag list.
     *
     * @return array
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * Set the 'before' tag list.
     *
     * @param string[] $before
     * @return self
     */
    public function before(...$before): Route
    {
        $this->before = $before;

        return $this;
    }

    /**
     * Get the 'after' tag list.
     *
     * @return array
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * Set the 'after' tag list.
     *
     * @param string[] $after
     * @return self
     */
    public function after(...$after): Route
    {
        $this->after = $after;

        return $this;
    }

    /**
     * Get the value of a route parameter.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam(string $key, $default = null)
    {
        $value = $this->params[$key] ?? $default ?? $this->defaults[$key] ?? null;

        return $key === "rest" ? explode("/", $value) : $value;
    }

    /**
     * Get all the parameters in the route.
     *
     * @return array
     */
    public function getParams()
    {
        return array_merge($this->defaults, $this->params);
    }

    /**
     * Set the route params.
     *
     * @param array $params
     * @return void
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * Check if a route placeholder param exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasParam(string $key)
    {
        return isset($this->params[$key]) || isset($this->defaults[$key]);
    }
}
