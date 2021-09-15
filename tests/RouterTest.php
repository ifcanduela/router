<?php

use ifcanduela\router\exception\InvalidHttpMethod;
use ifcanduela\router\exception\RouteNotFound;
use ifcanduela\router\Router;
use ifcanduela\router\Route;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testResolveRoute()
    {
        $r = new Router();

        $r->loadFile(__DIR__ . "/fixtures/routes.php", "r");
        $route = $r->resolve("/admin/dashboard", "get");

        $this->assertEquals("admin_dashboard", $route->getHandler());

        $route = $r->resolve("/admin/my-username/profile", "GET");

        $this->assertEquals("user_profile", $route->getHandler());
        $this->assertEquals("my-username", $route->getParam("username"));
    }

    public function testRouteNotFoundNoRoutes()
    {
        $r = new Router();

        $this->expectException(RouteNotFound::class);
        $r->resolve("/", "GET");

        $r->loadFile(__DIR__ . "/fixtures/routes.php", "r");

        $this->expectException(RouteNotFound::class);
        $r->resolve("/does/not/exist", "get");
    }

    public function testRouteNotFound()
    {
        $r = new Router();

        $r->loadFile(__DIR__ . "/fixtures/routes.php", "r");

        $this->expectException(RouteNotFound::class);
        $r->resolve("/does/not/exist", "get");
    }

    public function testMethodNotAllowed()
    {
        $r = new Router();

        $r->loadFile(__DIR__ . "/fixtures/routes.php", "r");

        $this->expectException(InvalidHttpMethod::class);
        $r->resolve("/admin/dashboard", "POST");
    }

    public function testCreateUrl()
    {
        $r = new Router();
        $r->loadFile(__DIR__. "/fixtures/routes.php", "r");

        $url = $r->createUrlFromRoute("user.logout");
        $this->assertEquals("/logout", $url);

        $url = $r->createUrlFromRoute("super.extra", [666]);
        $this->assertEquals("/admin/super/extra/666", $url);

        $url = $r->createUrlFromRoute("super.extra");
        $this->assertEquals("/admin/super/extra", $url);

        $url = $r->createUrlFromRoute("month", ["february"]);
        $this->assertEquals("/admin/month/february", $url);
    }

    public function testCreateUrlMissingRoute()
    {
        $r = new Router();
        $r->loadFile(__DIR__ . "/fixtures/routes.php", "r");

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Named route not found: `missing.route`");
        $r->createUrlFromRoute("missing.route", []);
    }

    public function testCreateUrlNotEnoughParams()
    {
        $r = new Router();
        $r->loadFile(__DIR__ . "/fixtures/routes.php", "r");

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Not enough parameters given");
        $r->createUrlFromRoute("month", []);
    }

    public function testCreateUrlTooManyParams()
    {
        $r = new Router();
        $r->loadFile(__DIR__ . "/fixtures/routes.php", "r");

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Too many parameters given");
        $r->createUrlFromRoute("month", ["jan", "feb"]);
    }

    public function testIsRoute()
    {
        $r = new Router();
        $r->loadFile(__DIR__ . "/fixtures/routes.php", "r");

        $this->assertTrue($r->isRoute("month", "/admin/month/april", "get"));

        $this->assertTrue($r->isRoute("user.logout", "/logout", "poST"));
        $this->assertFalse($r->isRoute("user.logout", "/logout", "get"));

        $this->assertFalse($r->isRoute("missing.route", "/logout", "POST"));
    }

    public function testRestParameters()
    {
        $r = new Router();
        $r->get("/s/{letters:.*}");

        $route = $r->resolve("/s/a/b/c", "GET");
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals("a/b/c", $route->getParam("letters"));

        $r = new Router();
        $r->get("/s/{rest:.*}");

        $route = $r->resolve("/s/a/b/c", "GET");
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals(["a", "b", "c"], $route->getParam("rest"));
    }

    public function testMountRouter()
    {
        $r = new Router();
        $r->get("/{slug}");

        $s = new Router();
        $s->get("/{slug}");

        $r->mount("/admin", $s);

        $route = $r->resolve("/admin/sub-route", "GET");
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals("sub-route", $route->getParam("slug"));

        $route = $r->resolve("/main-route", "GET");
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals("main-route", $route->getParam("slug"));
    }
}
