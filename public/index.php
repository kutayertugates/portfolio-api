<?php
require_once __DIR__ . "/../vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

use App\Core\Router;
use App\Services\HeaderService;

$router = new Router();

$cors = new HeaderService();
$cors->addAllowedMethods(["GET", "POST", "PUT", "PATCH", "DELETE"]);
$cors->addAllowedHeaders(["Authorization", "X-Requested-With"]);
$cors->allowed_host[] = "https://app.example.com";
$router->setCors($cors);

// -------------------------------------------------------------------
// Route'lar
// -------------------------------------------------------------------
$router->get(
    "/",
    fn() => App\Helpers\ResponseHelper::success(null, 200, "Ok!"),
);

// -------------------------------------------------------------------
// Dispatch
// -------------------------------------------------------------------
$router->dispatch($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);
