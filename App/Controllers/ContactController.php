<?php

namespace App\Controllers;

use PDO;
use App\Core\Database;
use App\Helpers\ResponseHelper;
use App\Helpers\ValidationHelper;

class ContactController extends BaseController
{
    public PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function create_contact()
    {
        $body = $this->getRequestBody();

        $validator = new ValidationHelper($body);
        $validator
            ->field("fullname")
            ->required()
            ->string()
            ->minLength(2)
            ->maxLength(200);
        $validator->field("email")->required()->string()->email();
        $validator->field("subject")->required()->string()->maxLength(255);
        $validator->field("message")->required()->string()->maxLength(65535);

        if ($validator->fails()) {
            ResponseHelper::error(
                "İletişim talebi oluşturulamadı.",
                400,
                $validator->getErrors(),
            );
        }

        $validated = $validator->validated();

        $query = "
            INSERT INTO
                contacts (fullname, email, subject, message)
            VALUES
                (:fullname, :email, :subject, :message);
        ";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ":fullname" => $validated["fullname"],
            ":email" => $validated["email"],
            ":subject" => $validated["subject"],
            ":message" => $validated["message"],
        ]);

        ResponseHelper::success(
            $validated,
            null,
            201,
            "İletişim talebi oluşturuldu.",
        );
    }
}
