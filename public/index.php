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

$page = $_GET['page'] ?? 'keywords';

switch ($page) {
    case 'keywords':
    default:
        $search = isset($_GET['q']) && is_string($_GET['q'])
            ? trim($_GET['q'])
            : null;

        $repository = new KeywordRepository(Database::connection());
        $rows = $repository->allWithCurrentPosition($search !== '' ? $search : null);

        $keywords = array_map(static function (array $row): array {
            $trend = $row['current_position'] !== null
                ? TrendCalculator::fromPositions($row['current_position'], $row['past_position'])
                : null;

            return [
                'id' => $row['id'],
                'phrase' => $row['phrase'],
                'current_position' => $row['current_position'],
                'trend' => $trend,
                'trend_label' => match ($trend) {
                    TrendCalculator::IMPROVED => 'Improved',
                    TrendCalculator::DECLINED => 'Declined',
                    TrendCalculator::STABLE => 'Stable',
                    default => null,
                },
            ];
        }, $rows);

        require dirname(__DIR__) . '/views/keywords_list.php';
        break;
}