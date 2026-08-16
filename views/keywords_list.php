<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Keyword positions</title>
</head>
<body>
    <h1>Keyword positions</h1>

    <form method="get" action="?page=keywords">
        <label for="search">Search</label>
        <input type="search" id="search" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">Search</button>
    </form>

    <button type="button">Refresh positions</button>

    <table>
        <thead>
            <tr>
                <th>Phrase</th>
                <th>Current position</th>
                <th>7-day trend</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($keywords as $keyword): ?>
            <tr>
                <td>
                    <a href="?page=keyword&amp;id=<?= htmlspecialchars((string) $keyword['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($keyword['phrase'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </td>
                <td>
                    <?= $keyword['current_position'] !== null
                        ? htmlspecialchars((string) $keyword['current_position'], ENT_QUOTES, 'UTF-8')
                        : '—' ?>
                </td>
                <td class="<?= $keyword['trend'] !== null ? 'trend--' . htmlspecialchars($keyword['trend'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    <?= $keyword['trend_label'] !== null
                        ? htmlspecialchars($keyword['trend_label'], ENT_QUOTES, 'UTF-8')
                        : 'No data yet' ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>