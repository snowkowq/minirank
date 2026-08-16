<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $keyword !== null
        ? htmlspecialchars($keyword['phrase'], ENT_QUOTES, 'UTF-8') . ' · Keyword positions'
        : 'Not found · Keyword positions' ?></title>
</head>
<body>
    <p><a href="?page=keywords">&larr; Back to list</a></p>

    <?php if ($keyword === null): ?>
        <h1>Not found</h1>
        <p>The requested keyword does not exist.</p>
    <?php else: ?>
        <h1><?= htmlspecialchars($keyword['phrase'], ENT_QUOTES, 'UTF-8') ?></h1>

        <dl>
            <dt>Current position</dt>
            <dd><?= $current_position !== null
                ? htmlspecialchars((string) $current_position, ENT_QUOTES, 'UTF-8')
                : '—' ?></dd>
            <dt>7-day trend</dt>
            <dd class="<?= $trend !== null ? 'trend--' . htmlspecialchars($trend, ENT_QUOTES, 'UTF-8') : '' ?>">
                <?= $trend_label !== null
                    ? htmlspecialchars($trend_label, ENT_QUOTES, 'UTF-8')
                    : 'No data yet' ?>
            </dd>
        </dl>

        <h2>Position history</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Position</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $entry): ?>
                <tr>
                    <td><?= htmlspecialchars($entry['tracked_on'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $entry['position'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>