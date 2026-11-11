<?php

namespace App\Controllers;

use PDO;
use App\Core\Database;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Services\JWTService;

class SecurityController extends BaseController
{
    public PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function login()
    {
        $body = $this->getRequestBody();

        $validator = new ValidationHelper($body);
        $validator->field("username")->required()->string()->maxLength(32);
        $validator
            ->field("password")
            ->required()
            ->string()
            ->minLength(8)
            ->maxLength(128);

        if ($validator->fails()) {
            return ResponseHelper::error(
                "Giriş başarısız.",
                400,
                $validator->getErrors(),
            );
        }

        $validated = $validator->validated();

        $query = "
            SELECT
                *
            FROM
                users
            WHERE
                username = :username
        ";
        $stmt_get_user = $this->db->prepare($query);
        $stmt_get_user->execute([
            ":username" => $validated["username"],
        ]);

        if ($stmt_get_user->rowCount() <= 0) {
            ResponseHelper::error("Kullanıcı adı veya şifre yanlış", 401);
        }

        $user = $stmt_get_user->fetch();
        if (!password_verify($validated["password"], $user["password"])) {
            ResponseHelper::error("Kullanıcı adı veya şifre yanlış", 401);
        }

        [$jwt_token, $exp] = JWTService::generate(["user_id" => $user["id"]]);

        ResponseHelper::success(
            [
                "user_id" => $user["id"],
                "access_token" => $jwt_token,
                "expiration" => $exp,
            ],
            200,
            "Giriş başarılı.",
        );
    }
}
