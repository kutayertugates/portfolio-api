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
use App\Controllers\BlogController;
use App\Controllers\ContactController;
use App\Controllers\UserController;

$router->get("/", fn() => ResponseHelper::success(null, 200, "Ok!"));

$router->group(["prefix" => "/blogs"], function (Router $r) {
    $r->get("/", [BlogController::class, "get_all_blogs"]);
});

$router->group(["prefix" => "/user"], function (Router $r) {
    $r->get("/{id}", [UserController::class, "get_user_with_id"]);
});

$router->group(["prefix" => "/contacts"], function (Router $r) {
    $r->post("/", [ContactController::class, "create_contact"]);
});

// -------------------------------------------------------------------
// Dispatch
// -------------------------------------------------------------------
try {
    $router->dispatch($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);
} catch (Throwable $e) {
    ResponseHelper::error("Sunucu hatası: " . $e->getMessage(), 500);
}
