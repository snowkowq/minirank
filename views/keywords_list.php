<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Keyword positions</title>
</head>
<body>
    <h1>Keyword positions</h1>

    <?php if ($error !== null): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="?page=keywords">
        <input type="hidden" name="action" value="create">
        <label for="phrase">Add keyword</label>
        <input type="text" id="phrase" name="phrase">
        <button type="submit">Add</button>
    </form>

    <form method="get" action="?page=keywords">
        <label for="search">Search</label>
        <input type="search" id="search" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">Search</button>
    </form>

    <button type="button" id="refresh-positions">Refresh positions</button>

    <table>
        <thead>
            <tr>
                <th>Phrase</th>
                <th>Current position</th>
                <th>7-day trend</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($keywords as $keyword): ?>
            <tr id="keyword-<?= htmlspecialchars((string) $keyword['id'], ENT_QUOTES, 'UTF-8') ?>">
                <td>
                    <a href="?page=keyword&amp;id=<?= htmlspecialchars((string) $keyword['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($keyword['phrase'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </td>
                <td class="js-position">
                    <?= $keyword['current_position'] !== null
                        ? htmlspecialchars((string) $keyword['current_position'], ENT_QUOTES, 'UTF-8')
                        : '—' ?>
                </td>
                <td class="js-trend <?= $keyword['trend'] !== null ? 'trend--' . htmlspecialchars($keyword['trend'], ENT_QUOTES, 'UTF-8') : '' ?>">
                    <?= $keyword['trend_label'] !== null
                        ? htmlspecialchars($keyword['trend_label'], ENT_QUOTES, 'UTF-8')
                        : 'No data yet' ?>
                </td>
                <td>
                    <form method="post" action="?page=keywords">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $keyword['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="phrase" value="<?= htmlspecialchars($keyword['phrase'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Save</button>
                    </form>
                </td>
                <td>
                    <form method="post" action="?page=keywords">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $keyword['id'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var button = document.getElementById('refresh-positions');

            if (button === null) {
                return;
            }

            button.addEventListener('click', function () {
                button.disabled = true;

                fetch('?page=refresh', { method: 'POST' })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Request failed');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        (data.keywords || []).forEach(function (keyword) {
                            var row = document.getElementById('keyword-' + keyword.id);

                            if (row === null) {
                                return;
                            }

                            var positionCell = row.querySelector('.js-position');

                            if (positionCell !== null) {
                                positionCell.textContent = keyword.position;
                            }

                            var trendCell = row.querySelector('.js-trend');

                            if (trendCell === null) {
                                return;
                            }

                            if (keyword.position_before === null) {
                                trendCell.textContent = 'No data yet';
                                trendCell.className = 'js-trend';
                                return;
                            }

                            var diff = keyword.position_before - keyword.position;
                            var trendKey;
                            var trendLabel;

                            if (diff >= 3) {
                                trendKey = 'improved';
                                trendLabel = 'Improved';
                            } else if (diff <= -3) {
                                trendKey = 'declined';
                                trendLabel = 'Declined';
                            } else {
                                trendKey = 'stable';
                                trendLabel = 'Stable';
                            }

                            trendCell.textContent = trendLabel;
                            trendCell.className = 'js-trend trend--' + trendKey;
                        });
                    })
                    .catch(function () {
                        alert('Failed to refresh positions.');
                    })
                    .finally(function () {
                        button.disabled = false;
                    });
            });
        });
    </script>
</body>
</html>