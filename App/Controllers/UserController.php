<?php

namespace App\Controllers;

use PDO;
use App\Core\Database;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;

class UserController extends BaseController
{
    public PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function create_user()
    {
        $body = $this->getRequestBody();

        $validator = new ValidationHelper($body);
        $validator->field("first_name")->string()->minLength(2)->maxLength(100);
        $validator->field("last_name")->string()->minLength(2)->maxLength(100);
        $validator->field("username")->required()->string()->maxLength(32);
        $validator->field("email")->required()->string()->email();
        $validator
            ->field("password")
            ->required()
            ->string()
            ->minLength(8)
            ->maxLength(128);

        if ($validator->fails()) {
            return ResponseHelper::error(
                "Kullanıcı oluşturulamadı.",
                400,
                $validator->getErrors(),
            );
        }

        $validated = $validator->validated();

        $unique_username_stmt = $this->db->prepare(
            "SELECT username FROM users WHERE username=:username",
        );
        $unique_username_stmt->execute([":username" => $validated["username"]]);
        if ($unique_username_stmt->rowCount() > 0) {
            ResponseHelper::error("Bu kullanıcı adı zaten alınmış", 400);
        }

        $query = "
            INSERT INTO
                users (first_name, last_name, username, email, password)
            VALUES
                (:first_name, :last_name, :username, :email, :password);
        ";

        $stmt = $this->db->prepare($query);
        $is_stmt = $stmt->execute([
            ":first_name" => $validated["first_name"],
            ":last_name" => $validated["last_name"],
            ":username" => $validated["username"],
            ":email" => $validated["email"],
            ":password" => password_hash(
                $validated["password"],
                PASSWORD_ARGON2ID,
            ),
        ]);

        if (!$is_stmt) {
            ResponseHelper::error(
                "Kullanıcı eklenirken bir sorun oluştu.",
                500,
            );
        }

        unset($validated["password"]);
        ResponseHelper::success(
            $validated,
            201,
            "Kullanıcı başarıyla eklendi.",
        );
    }
}
