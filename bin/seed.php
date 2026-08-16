<?php

declare(strict_types=1);

$dataDir = dirname(__DIR__) . '/data';
$dbFile = $dataDir . '/minirank.sqlite';

if (!is_file($dbFile)) {
    echo "Database not found. Run `php bin/init_db.php` first.\n";
    exit(1);
}

$db = new PDO('sqlite:' . $dbFile, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$db->beginTransaction();

$keywords = [
    'best running shoes',
    'how to tie a tie',
    'cheap flights to tokyo',
    'home espresso machine',
    'wireless earbuds review',
    'vegan meal prep ideas',
    'macbook air m4 price',
    'small garden ideas',
];

$days = 30;
$today = new DateTimeImmutable('today');

$insertKeyword = $db->prepare(
    'INSERT OR IGNORE INTO keywords (phrase) VALUES (:phrase)'
);
$selectKeywordId = $db->prepare('SELECT id FROM keywords WHERE phrase = :phrase COLLATE NOCASE');
$insertPosition = $db->prepare(
    'INSERT OR IGNORE INTO positions (keyword_id, position, tracked_on) VALUES (:keyword_id, :position, :tracked_on)'
);

$keywordCount = 0;
$positionInserted = 0;
$positionSkipped = 0;

try {
    foreach ($keywords as $phrase) {
        $insertKeyword->execute([':phrase' => $phrase]);

        $selectKeywordId->execute([':phrase' => $phrase]);
        $keywordId = (int) $selectKeywordId->fetchColumn();
        if ($keywordId === 0) {
            continue;
        }
        $keywordCount++;

        $position = random_int(30, 70);

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $trackedOn = $today->sub(new DateInterval('P' . $offset . 'D'))->format('Y-m-d');

            $step = random_int(-2, 2);
            if (random_int(1, 10) === 1) {
                $step += random_int(-2, 2);
            }
            $position = min(100, max(1, $position + $step));

            $insertPosition->execute([
                ':keyword_id' => $keywordId,
                ':position'   => $position,
                ':tracked_on' => $trackedOn,
            ]);

            $affected = $insertPosition->rowCount();
            if ($affected === 1) {
                $positionInserted++;
            } else {
                $positionSkipped++;
            }
        }
    }

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $e;
}

echo sprintf(
    "Seeded %d keyword(s) with positions for the last %d days: %d inserted, %d skipped (already present).\n",
    $keywordCount,
    $days,
    $positionInserted,
    $positionSkipped
);