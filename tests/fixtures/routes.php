<?php

/** @var Router $r */

use ifcanduela\router\Group;
use ifcanduela\router\Router;

$r->before("global_middleware_1", "global_middleware_2");
$r->after("global_middleware_3");

$r->group("/admin", function (Group $group) {
    $group->after("admin_middleware_after_1");
    $group->before("admin_middleware_before_1");

    $group->get("/dashboard")->to("admin_dashboard");                                         // 0
    $group->get("/login")->to("admin_login")->before("before_login_middleware_1");            // 1
    $group->get("/{username}/profile")->to("user_profile");                                   // 2
    $group->put("/put_url")->to("put_controller");                                            // 3

    $group->group(function (Group $g) {
        $g->get("/extra[/{id}]")->to("super_extra")->default("id", 999)->name("super.extra"); // 4
    })->prefix("/super")->before("super_before");

    $group->get("/month/{month}")->name("month");
});

$r->post("/logout")->to("user_logout")
    ->name("user.logout")
    ->before("logout_middleware_1")
    ->after("logout_middleware_2");                                                           // 5
