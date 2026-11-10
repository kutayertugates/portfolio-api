<?php

namespace App\Controllers;

use PDO;
use App\Core\Database;
use App\Helpers\ResponseHelper;

/**
 * Tüm controller'ların ortak tabanı.
 *
 * PDO bağlantısını ve istek gövdesi okuma gibi tekrarlayan mekanikleri sağlar.
 * İş mantığı içermez; iş kuralları alt sınıflarda (örn. MemberController) yaşar.
 */
class BaseController
{
    public PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    protected function getRequestBody(): array
    {
        $raw_body = file_get_contents("php://input");
        $decoded = json_decode($raw_body === false ? "" : $raw_body, true);

        if (!is_array($decoded)) {
            ResponseHelper::error(
                "İstek gövdesi geçerli bir JSON olmalıdır.",
                400,
            );
        }

        return $decoded;
    }
}
