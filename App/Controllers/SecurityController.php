<?php

namespace App\Controllers;

use PDO;
use App\Core\Database;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;
use App\Helpers\HeaderHelper;
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
        $validator->field("username")->required()->string();
        $validator->field("password")->required()->string();

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

    public function me()
    {
        $headers = HeaderHelper::all();

        if (!array_key_exists("Authorization", $headers)) {
            ResponseHelper::error("Token zorunludur", 401);
        }

        $authorization = $headers["Authorization"];
        $bearer_token = trim(explode("Bearer", $authorization)[1]);

        [$status, $code, $data_or_msg] = JWTService::decode($bearer_token);
        if (!$status) {
            ResponseHelper::error($data_or_msg, $code);
        }

        $query = "
            SELECT
                id, first_name, last_name, username, email, join_at
            FROM
                users
            WHERE
                id = :user_id
        ";
        $stmt_get_user = $this->db->prepare($query);
        $stmt_get_user->execute([
            ":user_id" => $data_or_msg->data->user_id,
        ]);
        $user = $stmt_get_user->fetch();

        ResponseHelper::success($user);
    }
}
