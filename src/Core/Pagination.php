<?php
namespace App\Core;

class Pagination
{
    public static function getParams(): array
    {
        $limit = $_GET['limit'] ?? 30;
        if (!Validator::validateInt($limit) || (int)$limit <= 0) {
            $limit = 30;
        } else {
            $limit = min((int)$limit, 30);
        }

        $cursor = $_GET['cursor'] ?? null;
        if ($cursor !== null && !Validator::validateInt($cursor)) {
            $cursor = null;
        } elseif ($cursor !== null) {
            $cursor = (int)$cursor;
        }

        return [$limit, $cursor];
    }
}
