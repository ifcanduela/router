<?php

use ifcanduela\router\Group;
use ifcanduela\router\Route;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    public function testCreateGroupAndAddRoutes()
    {
        $g = new Group();
        $this->assertInstanceOf(Group::class, $g);

        $g->addRoute(Route::from("here")->to("there"));
        $routes = $g->getRoutes();
        $this->assertCount(1, $routes);

        $g->addRoute(Route::from("this")->to("that"));
        $routes = $g->getRoutes();
        $this->assertCount(2, $routes);
    }

    public function testSetRoutes()
    {
        $g = new Group();

        $g->routes([
            Route::from("here")->to("there"),
            Route::from("this")->to("that"),
        ]);

        $this->assertCount(2, $g->getRoutes());

        $g->routes([
            Route::from("here")->to("there"),
            (new Group())->post("grouped-route"),
        ]);

        $this->assertCount(4, $g->getRoutes());
    }

    public function testSetRoutesInvalidRoute()
    {
        $g = new Group();

        $this->expectException(\InvalidArgumentException::class);

        $g->routes([
            Route::from("here")->to("there"),
            new \DateTime(),
        ]);
    }

    public function testPrefix()
    {
        $g = new Group();
        $g->prefix("/admin");

        $g->routes([Route::from("/dashboard")->to("handler")]);

        $route = $g->getRoutes()[0];

        $this->assertEquals("/admin/dashboard", $route->getPath());
    }

    public function testBeforeAndAfter()
    {
        $g = new Group();
        $g->before(SomeMiddleware::class, AnotherMiddleware::class);
        $g->after(FinalMiddleware::class);

        $g->routes([Route::from("/")->to("handler")]);

        $route = $g->getRoutes()[0];

        $this->assertEquals([SomeMiddleware::class, AnotherMiddleware::class], $route->getBefore());
        $this->assertEquals([FinalMiddleware::class], $route->getAfter());
    }

    public function testCreateRoutesWithMethods()
    {
        $g = new Group();

        $r = $g->from("/any-path", "GET", "post", "Other");
        $this->assertEquals("/any-path", $r->getPath());
        $this->assertEquals(["GET", "POST", "OTHER"], $r->getMethods());

        $r = $g->get("/get-path");
        $this->assertEquals("/get-path", $r->getPath());
        $this->assertEquals(["GET"], $r->getMethods());

        $r = $g->post("/post-path");
        $this->assertEquals("/post-path", $r->getPath());
        $this->assertEquals(["POST"], $r->getMethods());

        $r = $g->put("/put-path");
        $this->assertEquals("/put-path", $r->getPath());
        $this->assertEquals(["PUT"], $r->getMethods());

        $r = $g->delete("/delete-path");
        $this->assertEquals("/delete-path", $r->getPath());
        $this->assertEquals(["DELETE"], $r->getMethods());
    }

    public function testSetHandler()
    {
        $g = new Group();
        $g->handler("group_handler");
        $g->get("/test-path");

        $routes = $g->getRoutes();

        $this->assertEquals("group_handler", $routes[0]->getHandler());
    }

    public function testAddingSubrouter()
    {
        $g = new Group();
        $g->group("/some-prefix", function ($prefixed) {
            $prefixed->get("/get-path");
            $prefixed->post("/post-path");
        })->handler("group_handler");

        $routes = $g->getRoutes();
        $this->assertEquals("group_handler", $routes[0]->getHandler());
        $this->assertEquals("group_handler", $routes[1]->getHandler());

        $this->assertEquals("/some-prefix/get-path", $routes[0]->getPath());
        $this->assertEquals("/some-prefix/post-path", $routes[1]->getPath());

        $g = new Group();
        $g->group(function ($prefixed) {
            $prefixed->get("/get-path");
            $prefixed->post("/post-path");
        })->handler("group_handler");

        $routes = $g->getRoutes();
        $this->assertEquals("group_handler", $routes[0]->getHandler());
        $this->assertEquals("group_handler", $routes[1]->getHandler());

        $this->assertEquals("/get-path", $routes[0]->getPath());
        $this->assertEquals("/post-path", $routes[1]->getPath());
    }

    public function testLoadRoutesFromFile()
    {
        $g = new Group();
        $g->loadFile(__DIR__ . "/fixtures/routes.php", "r");

        $routes = $g->getRoutes();

        $this->assertEquals("/admin/dashboard", $routes[0]->getPath());
    }

    public function testLoadRoutesFromMissingFile()
    {
        $g = new Group();

        $this->expectException(\InvalidArgumentException::class);
        $g->loadFile("this_does_not_exist.php");
    }
}
