# AGENTS.md

Fresh scaffold — only `.gitignore` + initial commit exist. Stack is decided; code hasn't landed yet.

## Stack (decided, not yet in repo)

- PHP 8.4, no framework. SQLite via PDO.
- Frontend: plain HTML/CSS/JS, no build step.
- No Composer dependencies — do not introduce one without being asked.

## Dev workflow

- No test/lint/build tooling exists yet. Don't invent commands or config files the repo doesn't have.
- Dev server: `php -S localhost:8000 -t public`

## Repo conventions (from `.gitignore`)

- Never commit: `data/` (DB files), `*.sqlite*`, `*.db`, `.env` (secrets), `vendor/`, `notes.md` (local scratch).
- Put the SQLite database under `data/`; keep `notes.md` out of version control.
- `.env` holds local secrets; there is no env-loading code yet.

## Git

- Single branch `main`; commit in plain short messages (see existing commit style).

## Security & Data Handling

### Escaping Output (XSS Prevention)
* **Escape all HTML variables:** Any text originating from the user or the database MUST be escaped before being rendered in HTML. Always use `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.
* **Mind the attributes:** Do not limit escaping to HTML body text. You must strictly escape data placed inside HTML attributes (e.g., `value="..."`, `title="..."`).
* **JSON Responses:** API/JSON responses must contain raw, unmodified data generated via `json_encode()`. Escaping must happen strictly on the client-side (JavaScript) when inserting data into the DOM. Use `textContent` for this. NEVER use `innerHTML` with concatenated strings.

### Database & SQL
* **Prepared Statements Only:** Always use PDO prepared statements for any SQL query containing variables. NEVER concatenate user input or variables directly into SQL query strings.
  * **Wrong:** `$db->query("SELECT * FROM keywords WHERE id = $id");`
  * **Right:**
```php
    $stmt = $db->prepare("SELECT * FROM keywords WHERE id = :id");
    $stmt->execute([':id' => $id]);
```
  * **LIKE searches:** The wildcards go in the bound value, not in the SQL:
```php
    $stmt = $db->prepare("SELECT * FROM keywords WHERE name LIKE :q");
    $stmt->execute([':q' => '%' . $search . '%']);
```

## Folder Structure
Adhere strictly to the following architectural boundaries. Do not dump files in the project root.
* `public/`: The ONLY web-exposed directory (Document Root). The local server MUST be started using `php -S localhost:8000 -t public`. This is critical for security; if the server runs from the root, anyone can download `data/minirank.sqlite` directly from the browser.
* `src/`: Contains core application logic (PHP classes). Strictly one class per file, using the `App\` namespace. Keep it plain PHP: no framework-like abstractions, no base controllers, and no unnecessary inheritance.
* `views/`: Contains ONLY presentation templates. **Strict Rule:** Views are strictly for displaying data. They must receive fully prepared variables. NEVER write SQL queries, database calls, or complex business logic inside a view.
* `data/`: Contains the SQLite database file (`minirank.sqlite`). This must remain hidden from the web root.
* `bin/`: Contains CLI scripts and executables (e.g., database setup or seeding scripts).

## Domain Rules (Business Logic)
* **Positions & Ranking:** Keyword positions are integers between 1 and 100. **Smaller numbers are better** (position 1 is the top rank). Keep in mind that a mathematical *decrease* in the position number means an *improvement* in the trend.
* **Data Uniqueness:** There must be strictly **one position entry per keyword per day**. Prevent duplicate entries if a refresh/update action is triggered multiple times on the same day.

## Agent Interaction Rules (How We Work)
* **Modify ONLY requested files:** Restrict your code changes strictly to the files I explicitly mention in the prompt. If you believe other files need to be modified to complete the task, **ASK for permission first**. Do not touch unrequested files.
* **No unauthorized dependencies:** Do NOT add Composer packages, libraries, or external dependencies unless I explicitly ask you to.
* **Scope strictly to the prompt:** Implement exactly one feature per request. Absolutely NO unsolicited refactoring of existing, working code.
* **Strict typing:** Include `declare(strict_types=1);` at the very top of every PHP file. Additionally, always use explicit type declarations for all function/method parameters and return types.