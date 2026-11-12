<?php

namespace App\Controllers;

use PDO;
use App\Core\Database;
use App\Helpers\ResponseHelper;

class UserController extends BaseController
{
    public PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function get_user_with_id($id)
    {
        $query = "SELECT id, first_name, last_name, username FROM users;";
        $stmt = $this->db->prepare($query);
        $stmt->execute();

        ResponseHelper::success($stmt->fetch());
    }
}
