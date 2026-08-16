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