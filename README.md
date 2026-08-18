# MiniRank

MiniRank is a lightweight keyword position tracker. You keep a list of keywords, track their position each day, and watch the 7-day trend to see whether each keyword is improving, declining, or holding steady. All ranking data is simulated — positions are generated locally, not fetched from a real search engine. It's plain PHP 8.4 with a SQLite database — no framework, no build step.

## Requirements

- PHP 8.4+ with the `pdo_sqlite` extension
- git
- No Composer, no database server

## Setup

1. Initialize the database:

   ```
   php bin/init_db.php
   ```

   This creates `data/minirank.sqlite`.

2. Seed the database with 8 demo keywords and 30 days of history:

   ```
   php bin/seed.php
   ```

3. Start the dev server:

   ```
   php -S localhost:8000 -t public
   ```

4. Open <http://localhost:8000>

## Features

- Keyword CRUD: add, edit, and delete keywords
- Seeded history: 8 demo keywords with 30 days of position history
- AJAX refresh: re-check positions without reloading the page
- List page with 7-day trend indicators and keyword search
- Detail page showing a keyword's full position history
- Responsive layout that works on small screens

## Project structure

- `public/` — web root (the only directory exposed by the dev server); contains `index.php` and static assets
- `src/` — core PHP classes (`App\` namespace): database connection, keyword repository, trend calculation
- `views/` — presentation templates for the keyword list and detail pages
- `bin/` — CLI scripts: `init_db.php` and `seed.php`
- `data/` — the SQLite database file (`minirank.sqlite`), kept out of the web root

## Trends

The 7-day trend compares the current position with the position from 7 days earlier (or the oldest available within that window). A change of **3 or more positions** counts as a move. Remember: **lower position numbers are better** — position 1 is the top rank, so a drop from 12 to 9 is an improvement.
