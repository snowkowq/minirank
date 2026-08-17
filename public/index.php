<?php

declare(strict_types=1);

use App\Database;
use App\KeywordRepository;
use App\TrendCalculator;

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

function trendLabel(?string $trend): ?string
{
    return match ($trend) {
        TrendCalculator::IMPROVED => 'Improved',
        TrendCalculator::DECLINED => 'Declined',
        TrendCalculator::STABLE => 'Stable',
        default => null,
    };
}

$error = null;

if (($_GET['page'] ?? '') === 'refresh') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Method not allowed.']);
        exit;
    }

    $db = Database::connection();
    $today = date('Y-m-d');

    $keywordsStmt = $db->prepare(
        'SELECT k.id,
                (SELECT position
                 FROM positions
                 WHERE keyword_id = k.id
                 ORDER BY tracked_on DESC
                 LIMIT 1) AS last_position
         FROM keywords k
         ORDER BY k.id'
    );
    $keywordsStmt->execute();

    $upsertStmt = $db->prepare(
        'INSERT INTO positions (keyword_id, position, tracked_on)
         VALUES (:keyword_id, :position, :tracked_on)
         ON CONFLICT (keyword_id, tracked_on)
         DO UPDATE SET position = excluded.position'
    );
    $positionBeforeStmt = $db->prepare(
        'SELECT position
         FROM positions
         WHERE keyword_id = :keyword_id
           AND tracked_on <= date(:today, \'-7 days\')
         ORDER BY tracked_on DESC
         LIMIT 1'
    );

    $refreshed = [];

    $db->beginTransaction();

    try {
        foreach ($keywordsStmt->fetchAll(PDO::FETCH_ASSOC) as $keywordRow) {
            $keywordId = (int) $keywordRow['id'];
            $lastPosition = $keywordRow['last_position'] !== null ? (int) $keywordRow['last_position'] : null;

            if ($lastPosition !== null) {
                $newPosition = min(100, max(1, $lastPosition + random_int(-2, 2)));
            } else {
                $newPosition = random_int(1, 100);
            }

            $upsertStmt->execute([
                ':keyword_id' => $keywordId,
                ':position'   => $newPosition,
                ':tracked_on' => $today,
            ]);

            $positionBeforeStmt->execute([':keyword_id' => $keywordId, ':today' => $today]);
            $positionBefore = $positionBeforeStmt->fetchColumn();
            $positionBefore = $positionBefore === false ? null : (int) $positionBefore;

            $refreshed[] = [
                'id'              => $keywordId,
                'position'        => $newPosition,
                'position_before' => $positionBefore,
            ];
        }

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Refresh failed.']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode(['keywords' => $refreshed]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $repository = new KeywordRepository(Database::connection());

    $phrase = isset($_POST['phrase']) && is_string($_POST['phrase']) ? trim($_POST['phrase']) : '';
    $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : false;
    $phraseLength = function_exists('mb_strlen') ? mb_strlen($phrase) : strlen($phrase);

    $needsPhrase = $action === 'create' || $action === 'update';
    $needsId = $action === 'update' || $action === 'delete';

    if (!in_array($action, ['create', 'update', 'delete'], true)) {
        $error = 'Unknown action.';
    } elseif ($needsPhrase && ($phrase === '' || $phraseLength > 200)) {
        $error = 'The keyword must be between 1 and 200 characters.';
    } elseif ($needsId && ($id === false || $id < 1)) {
        $error = 'Invalid keyword selected.';
    } else {
        try {
            switch ($action) {
                case 'create':
                    $repository->create($phrase);
                    break;

                case 'update':
                    $repository->update($id, $phrase);
                    break;

                case 'delete':
                    $repository->delete($id);
                    break;
            }

            header('Location: ?page=keywords');
            exit;
        } catch (PDOException $e) {
            $error = 'This keyword already exists.';
        }
    }
}

$page = $_GET['page'] ?? 'keywords';

switch ($page) {
    case 'keywords':
    default:
        $search = isset($_GET['q']) && is_string($_GET['q'])
            ? trim($_GET['q'])
            : null;

        $repository = new KeywordRepository(Database::connection());
        $rows = $repository->allWithCurrentPosition($search !== '' ? $search : null);

        $keywords = array_map(static function (array $row) use ($repository): array {
            $trend = null;

            if ($row['current_position'] !== null && $row['current_date'] !== null) {
                $past_position = $repository->positionBefore($row['id'], $row['current_date']);
                $trend = TrendCalculator::fromPositions($row['current_position'], $past_position);
            }

            return [
                'id' => $row['id'],
                'phrase' => $row['phrase'],
                'current_position' => $row['current_position'],
                'trend' => $trend,
                'trend_label' => trendLabel($trend),
            ];
        }, $rows);

        require dirname(__DIR__) . '/views/keywords_list.php';
        break;

    case 'keyword':
        $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : false;

        $repository = new KeywordRepository(Database::connection());

        if ($id === false || $id < 1) {
            $keyword = null;
            $history = [];
        } else {
            $keyword = $repository->find($id);
            $history = $keyword !== null ? $repository->historyFor($id) : [];
        }

        $current_position = null;
        $trend = null;
        $trend_label = null;

        if ($keyword !== null && $history !== []) {
            $current_position = $history[0]['position'];

            $past_position = $repository->positionBefore($id, $history[0]['tracked_on']);

            $trend = TrendCalculator::fromPositions($current_position, $past_position);
            $trend_label = trendLabel($trend);
        }

        require dirname(__DIR__) . '/views/keyword_detail.php';
        break;
}