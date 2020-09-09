# Router

PHP router wrapping `nikic/fast-route`, with support for nested route 
groups, default parameters and a few other extra features.

## Installation

Use [Composer](https://getcomposer.org):

```sh
composer require ifcanduela/router
```

## Usage

Create an `ifcanduela\router\Router` instance and define routes:

```php
$router = new ifcanduela\router\Router();
$router->get("/")->to("my_controller");
```

## Loading routes from a file

Loading routes from a file is the preferred method to initialise a router. Create a 
file like this, for example `routes.php`:

```php
<?php

/** @var ifcanduela\router\Router $r */

$r->get("/")->to("home");

$r->group("/admin", function (ifcanduela\router\Group $g) {
    $g->get("/dashboard")->to("admin@dashboard");
});
```

And load the file like this:

```php
$router = new ifcanduela\router\Router();
$router->loadFile("routes.php", "r");
```

Files can be loaded from nested groups also:

```php
$router->group("/admin", function (ifcanduela\router\Group $g) {
    $g->loadFile("admin-routes.php");
    
    $g->group("/settings", function (ifcanduela\router\Group $g) {
        $g->loadFile("admin-settings-routes.php");    
    });
});
```

## Route definitions

Routes are mostly created using Router or Group methods:

```php
$group = new Group();
$group->from("/home")->to("home_controller");
$group->get("/login")->to("login_form");
$group->post("/login")->to("login_submit");
```

Behind the scenes, those methods call the static constructors in the Route class:

```php
Route::from("/home")->to("home_controller");
Route::get("/login")->to("login_form");
Route::post("/login")->to("login_submit");
```

The `get`, `post`, `put` and `delete` methods create routes that only match requests
with the same HTTP method. The `from` method allows `GET` and `POST` by default, but 
the `Group::from` method accepts more string arguments with the HTTP methods to
allow.

Additionally, it's possible to call the `methods` method on a route to override its 
allowed methods:

```php
Route::from("/update-user/{id}", "get", "post")->to("api@updateUser");
Route::from("/create-user")->to("api@createUser")->methods(["PATCH"]);
```

Routes follow the syntax defined by [`nikic/fast-route`](https://github.com/nikic/FastRoute),
so you can add parameters using curly braces and optional parameters using square brackets. 
It's also possible to add default values for optional parameters:

```php
Route::from("/projects/{id}[/{version}]")
    ->to("project_dashboard")
    ->default("version", "latest");
```

## Route groups

When more than one route share a prefix or metadata, you can put them together inside a group.
There are several ways to define groups, but all of them have the same result. First, the 
generic way, in which the group is treated as a router:

```php
$adminGroup = $router->group();
$adminGroup->prefix("/admin");
$adminGroup->get("/dashboard")->to([AdminController::class, "dashboard"]);
$adminGroup->get("/users")->to([UsersController::class, "index"]);
```

A more clear way is to use a callback to define the grouped routes:

```php
$router->group("/admin", function ($adminGroup) {
    $adminGroup->prefix("/admin");
    $adminGroup->get("/dashboard")->to([AdminController::class, "dashboard"]);
    $adminGroup->get("/users")->to([UsersController::class, "index"]);
});
```

## Resolving the route

The router will need a path and a HTTP method to resolve a route. If no matching route is found,
an exception is thrown. 

```php
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$pathInfo = $request->getPathInfo();
$method = $request->getMethod();

try {
    $route = $router->resolve($pathInfo, $method);
} catch (\ifcanduela\router\InvalidHttpMethod $e) {

} catch (\ifcanduela\router\RouteNotFound $e) {

}
```

## Route metadata

It's possible to attach extra information to routes to better identify them and facilitate
their use in the request/response cycle.

## Route names

Routes can be identified by names, which can help later when creating URLs to them:

```php
$router->get("/example/view/{id}")->to("example@view")->name("example.view");

$url = $router->createUrlFromRoute("example.view", [123]);
//=> "/example/view/123"
```

You can check if a path and method combination matches a route name:

```php
$pathInfo = $request->getPathInfo();
$method = $request->getMethod();

$router->isRoute("example.view", $pathInfo, $method);
//=> true/false
```

## Tagging routes (before/after)

Tagging routes is useful to run middleware before or after matching them.

```php
Route::from("/path")->to("handler")
    ->before(MyMiddleware::class, "some_other_thing");
```

A different way of tagging routes is by using namespaces:

```php
Route::from("/admin/index")->to("admin_index")
    ->namespace("admin");
```

If you are using tags for middleware, you can use global middleware (applied to all routes)
by attaching it to the router itself:

```php
$router->before(StartSession::class);
$router->after(ConvertToResponse::class, SendResponse::class);
```

Once the route is resolved, all applicable tags can be accessed using `$route->getBefore()` 
and `$route->getAfter()`.

## License

[MIT](LICENSE).
