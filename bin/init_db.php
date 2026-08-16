<?php

declare(strict_types=1);

$dataDir = dirname(__DIR__) . '/data';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$db = new PDO('sqlite:' . $dataDir . '/minirank.sqlite', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$db->exec('PRAGMA foreign_keys = ON');

$db->exec(
    'CREATE TABLE IF NOT EXISTS keywords (
        id         INTEGER PRIMARY KEY,
        phrase     TEXT    NOT NULL,
        created_at TEXT    NOT NULL DEFAULT (datetime(\'now\')),
        UNIQUE (phrase COLLATE NOCASE)
    )'
);

$db->exec(
    'CREATE TABLE IF NOT EXISTS positions (
        id         INTEGER PRIMARY KEY,
        keyword_id INTEGER NOT NULL,
        position   INTEGER NOT NULL CHECK (position BETWEEN 1 AND 100),
        tracked_on TEXT    NOT NULL CHECK (tracked_on = date(tracked_on)),
        created_at TEXT    NOT NULL DEFAULT (datetime(\'now\')),
        UNIQUE (keyword_id, tracked_on),
        FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE
    )'
);

echo "Database initialized.\n";