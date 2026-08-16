<?php

declare(strict_types=1);

namespace App;

use PDO;

final class KeywordRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function allWithCurrentPosition(?string $search = null): array
    {
        $sql = <<<'SQL'
            SELECT
                k.id,
                k.phrase,
                cur.position AS current_position,
                past.position AS past_position
            FROM keywords k
            LEFT JOIN positions cur
                ON cur.keyword_id = k.id
                AND cur.tracked_on = (
                    SELECT MAX(tracked_on)
                    FROM positions
                    WHERE keyword_id = k.id
                )
            LEFT JOIN positions past
                ON past.keyword_id = k.id
                AND past.tracked_on = date(cur.tracked_on, '-7 days')
        SQL;

        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= ' WHERE k.phrase LIKE :q COLLATE NOCASE';
            $params[':q'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY k.phrase COLLATE NOCASE';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['current_position'] = $row['current_position'] !== null ? (int) $row['current_position'] : null;
            $row['past_position'] = $row['past_position'] !== null ? (int) $row['past_position'] : null;

            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, phrase, created_at FROM keywords WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $row['id'] = (int) $row['id'];

        return $row;
    }

    public function historyFor(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT tracked_on, position FROM positions WHERE keyword_id = :id ORDER BY tracked_on DESC'
        );
        $stmt->execute([':id' => $id]);

        return array_map(static function (array $row): array {
            $row['position'] = (int) $row['position'];

            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}