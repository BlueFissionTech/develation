## DevElation Examples Overview

This directory contains small, self-contained example apps that are meant to double as tutorials for “thinking in DevElation”. Each example leans on core library concepts instead of ad‑hoc PHP.

Run `composer install` first. The examples share `examples/support.php`, which loads Composer, keeps local runtime files under `.localappdata/examples`, and provides tiny helpers for input normalization, safe HTML output, IDs, and logging.

### 1. Todo List – `examples/todo/index.php`

- **Purpose**: Minimal todo app showing how to track tasks with owners and due dates.
- **Key DevElation concepts**:
  - `Date`: parsing user input, computing timestamps, formatting dates, and detecting “overdue” tasks.
  - `Arr`: treating the todo set as a value object with helpers (`hasKey`, `get`, `set`, `delete`, `count`) instead of raw arrays.
  - `Data\Storage\Session`: using DevElation’s storage pipeline (`activate()`, `read()`, `assign()`, `write()`) instead of working directly with `$_SESSION`.
  - `HTML\Template` + Vibe templates: binding an associative array (`$viewData`) into `todo.vibe` and `layout.vibe` for declarative rendering.
- **How to run**:
  - Point a PHP‑capable web server at the project root (or use the Laravel/Opus harness) and open `examples/todo/index.php` through the web server.

### 2. Comment Thread – `examples/comments/index.php`

- **Purpose**: Lightweight Reddit‑style thread with voting and simple ordering.
- **Key DevElation concepts**:
  - `Data\Storage\Session` again as the backing store for the in‑memory “threads” structure.
  - `Arr`: managing the `threads['main']` map, mutating comments via `Arr` methods before persisting.
  - `Str::pluralize('comment')`: example of the static helper mapping (`_pluralize()` → `Str::pluralize()`) provided by `Val::__callStatic`.
  - `HTML\Template` + Vibe: rendering comments, votes, and counts via `thread.vibe` and `layout.vibe`.
- **How to run**:
  - Serve the project and load `examples/comments/index.php` via your web server.

### 3. CLI Territory Game – `examples/game/gangs.php`

- **Purpose**: Text‑based, turn‑driven game where you and an NPC compete for territory.
- **Key DevElation concepts**:
  - `Behavioral\Behaves`: `GangNpc` uses the behavioral engine to register and perform states like `State::PROCESSING`, `State::CREATING`, `State::DELETING`, and `State::IDLE` around NPC decisions.
  - Behavioral dispatch: NPC actions are framed as behaviors/state transitions rather than bare conditionals, mirroring how richer game or app logic can be modeled.
  - `Arr`: `GangGame` keeps an `Arr` log of NPC narration and prints a recap at the end of the session.
- **How to run**:
  - From the project root: `php examples/game/gangs.php` in a terminal.

### 4. CLI Status Report – `examples/cli/report.php`

- **Purpose**: Compact CLI demo for argument parsing, output formatting, and progress updates.
- **Key DevElation concepts**:
  - `Cli\Args` + `OptionDefinition`: parse short/long flags and generate usage text.
  - `Cli\Console`: colorized output, tables, progress bars, and prompts.
  - `Cli\Util\StatusBar`: quick status line composed from labeled values.
- **How to run**:
  - From the project root: `php examples/cli/report.php --limit 3 --delay 50`
  - Add `--ask` to prompt for a custom title.

### 5. Helper Workflow – `examples/helpers/workflow.php`

- **Purpose**: Non-interactive walkthrough of the current helper surface for package users and contributors.
- **Key DevElation concepts**:
  - `Arr`: filtering, mapping, values, reverse ordering, and static `Arr::count()`.
  - `Str`: repeat helpers, case-insensitive `match()`, trimming, and static helper dispatch.
  - `Num`: degree/radian conversion plus sine and cosine.
  - `Flag`: normalized boolean parsing.
  - `Date`: timestamp formatting.
  - `Data\File`, `Data\FileSystem`: safe existence checks, directory entries, and file lines.
  - `Net\HTTP`: URL path segment, host, and status-line helpers.
  - `Security\Hash`: deterministic content IDs for structured values.
- **How to run**:
  - From the project root: `php examples/helpers/workflow.php`
  - The example prints a JSON report and does not require a web server or external service.

### 6. HTTP/API Packet – `examples/http/api_packet.php`

- **Purpose**: Build a deterministic API request/response packet without calling an external service.
- **Key DevElation concepts**:
  - `Obj`: request-shaped structured data.
  - `HTTP`: path segment encoding, query strings, header lines, URL parts, status lines, and JSON encoding.
  - `Arr`: header list handling.
  - `Flag`, `Date`, `Str`, and `Hash`: common request normalization and traceability helpers.
- **How to run**:
  - From the project root: `php examples/http/api_packet.php`
  - The example prints JSON and is safe for clean package checkouts because it performs no network request.

### Additional Ideas to Explore

These examples intentionally stay small, but there are several DevElation features that could be layered in when you want to go deeper:

- **Shared datasources** (`Data\Data`, `Data\Storage\*`, `Data\Datasource\Datasource`): the todo and comment examples currently use `Data\Storage\Session`, but any `IData` implementation (file-backed storage, DB-backed datasource, spreadsheet adaptor) can be injected in its place. Controllers only call `read`/`write`/`assign`/`field`, so swapping storage doesn’t require changing application logic.
- **HTML builders** (`HTML\Form`, `HTML\Table`): render the todo or comment UIs directly from DevElation’s HTML helpers instead of hand‑written tags, especially for forms and tabular lists.
- **Logging** (`Data\Log`): capture important events (task created, comment posted, territory shift) into a log file for audit or debugging.
- **Service mappings** (`Services\Mapping`, `Services\Request`/`Response`): wrap the todo or comments example as small services with typed requests/responses, showing how DevElation maps between external APIs and internal objects.
- **Async/queues** (`Async`, `Data\Queues\Queue`): move expensive or periodic work (e.g., nightly purge of stale todos, background scoring) onto a DevElation queue.
- **DevElation hooks** (`DevElation::apply`, `DevElation::do`): add filters/actions around critical points (before saving a todo, after posting a comment, when the NPC changes state) to demonstrate pluggable behavior without touching the core example logic.
- **Example smoke tests** (`tests/Examples/ExamplesSmokeTest.php`): keep non-interactive examples executable as the helper surface evolves.

Each of these modules already exists in `src/` and can be layered into the examples as you grow them into more full‑featured reference apps.
