<?php

namespace App\Controllers;

use PDO;
use App\Core\Database;
use App\Helpers\ResponseHelper;

class BlogController extends BaseController
{
    public PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function get_all_blogs()
    {
        $page = intval($_GET["page"] ?? 1);
        $limit = intval($_GET["limit"] ?? 10);

        if ($page <= 0) {
            $page = 1;
        }

        if ($limit <= 0) {
            $limit = 10;
        }

        $limit = min($limit, 100);
        $offset = ($page - 1) * $limit;

        $query = "
            SELECT
                author_id, slug, cover_image, title, seo_title,
                description, seo_description, content, published_at
            FROM
                blogpost
            WHERE
                status=:status
            LIMIT
                :offset, :limit;";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":status", "published");
        $stmt->execute();

        ResponseHelper::success($stmt->fetchAll());
    }
}
