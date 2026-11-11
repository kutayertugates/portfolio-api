<?php
require_once __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

use App\Core\Router;
use App\Services\HeaderService;
use App\Helpers\ResponseHelper;

$router = new Router();

$cors = new HeaderService();
$cors->addAllowedMethods(["GET", "POST", "PUT", "PATCH", "DELETE"]);
$cors->addAllowedHeaders(["Authorization", "X-Requested-With"]);
$cors->allowed_host[] = "https://app.example.com";
$router->setCors($cors);

// -------------------------------------------------------------------
// Route'lar
// -------------------------------------------------------------------
use App\Controllers\UserController;
use App\Controllers\SecurityController;

$router->get("/", fn() => ResponseHelper::success(null, 200, "Ok!"));

$router->group(["prefix" => "/security"], function (Router $r) {
    $r->post("/login", [SecurityController::class, "login"]);
    $r->get("/me", [SecurityController::class, "me"]);
});

$router->group(["prefix" => "/users"], function (Router $r) {
    $r->post("/", [UserController::class, "create_user"]);
});

// -------------------------------------------------------------------
// Dispatch
// -------------------------------------------------------------------
try {
    $router->dispatch($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);
} catch (Throwable $e) {
    ResponseHelper::error("Sunucu hatası", 500);
}
