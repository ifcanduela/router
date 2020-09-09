<?php

use ifcanduela\router\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testCreateFromArray()
    {
        $route = Route::fromArray([
            "path" => "/",
            "to" => "test_controller",
            "before" => [SomeClass::class, AnotherClass::class],
            "after" => [OneMore::class],
            "defaults" => ["input" => "output"],
        ]);

        $this->assertEquals("/", $route->getPath());
        $this->assertEquals("test_controller", $route->getHandler());
        $this->assertEquals(["*"], $route->getMethods());

        $this->assertEquals([SomeClass::class, AnotherClass::class], $route->getBefore());
        $this->assertEquals([OneMore::class], $route->getAfter());

        $route = Route::fromArray([
            "path" => "/",
            "to" => "test_controller",
            "methods" => ["POST", "PUT"],
            "name" => "route.name",
        ]);

        $this->assertEquals(["POST", "PUT"], $route->getMethods());
        $this->assertEquals("route.name", $route->getName());

        $route = Route::fromArray([
            "path" => "/",
            "to" => "test_controller",
            "methods" => "POST PUT",
            "namespace" => "ns",
        ]);

        $this->assertEquals(["POST", "PUT"], $route->getMethods());
        $this->assertEquals("ns", $route->getNamespace());
    }

    public function testStaticConstructors()
    {
        $route = Route::from("/this-path")
            ->to("some_controller")
            ->before(SomeClass::class, AnotherClass::class)
            ->after(OneMore::class);

        $this->assertEquals("/this-path", $route->getPath());
        $this->assertEquals("some_controller", $route->getHandler());
        $this->assertEquals(["*"], $route->getMethods());

        $this->assertEquals([SomeClass::class, AnotherClass::class], $route->getBefore());
        $this->assertEquals([OneMore::class], $route->getAfter());

        $route = Route::get("/this-path")
            ->to("some_controller");

        $this->assertEquals(["GET"], $route->getMethods());

        $route = Route::post("/this-path")
            ->to("some_controller");

        $this->assertEquals(["POST"], $route->getMethods());

        $route = Route::put("/this-path")
            ->to("some_controller");

        $this->assertEquals(["PUT"], $route->getMethods());

        $route = Route::delete("/this-path")
            ->to("some_controller");

        $this->assertEquals(["DELETE"], $route->getMethods());
    }

    public function testDefaultParams()
    {
        $route = Route::from("/this-path[/{id}]")
            ->to("some_controller")
            ->default("id", 123);

        $this->assertEquals(["id" => 123], $route->getDefaults());

        $this->assertEquals(123, $route->getParam("id"));
        $this->assertEquals(234, $route->getParam("id", 234));
        $this->assertNull($route->getParam("nothing"));
    }

    public function testParams()
    {
        $route = Route::from("/this-path[/{id}]")
            ->to("some_controller")
            ->default("id", 123);

        $route->setParams(["a" => 1, "b" => 2]);
        $this->assertTrue($route->hasParam("a"));
        $this->assertEquals(["a" => 1, "b" => 2, "id" => 123], $route->getParams());
    }
}
