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
                cur.tracked_on AS current_date
            FROM keywords k
            LEFT JOIN positions cur
                ON cur.keyword_id = k.id
                AND cur.tracked_on = (
                    SELECT MAX(tracked_on)
                    FROM positions
                    WHERE keyword_id = k.id
                )
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

            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function positionBefore(int $keywordId, string $referenceDate): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT position
             FROM positions
             WHERE keyword_id = :keyword_id
               AND tracked_on <= date(:reference_date, \'-7 days\')
             ORDER BY tracked_on DESC
             LIMIT 1'
        );
        $stmt->execute([':keyword_id' => $keywordId, ':reference_date' => $referenceDate]);

        $position = $stmt->fetchColumn();

        return $position === false ? null : (int) $position;
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