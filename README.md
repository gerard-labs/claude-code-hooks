<div align="center">

# 🪝 gerard/claude-code-hooks

**Type-safe Claude Code hooks for PHP.**
Every event, every tool input, every response — fully typed, mutation-tested,
and kept in sync with the Anthropic spec by a daily drift job.

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/releases/8.3/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-1d3d4f)](https://phpstan.org/)
[![Psalm](https://img.shields.io/badge/Psalm-errorLevel%201-blueviolet)](https://psalm.dev/)
[![Coverage](https://img.shields.io/badge/Coverage-100%25-success)](#-quality-bar)
[![MSI](https://img.shields.io/badge/Mutation%20MSI-%E2%89%A580%25-orange)](#-quality-bar)
[![Doc-drift](https://img.shields.io/badge/Doc--drift-daily-blue)](#-staying-in-sync-with-the-anthropic-spec)
[![Status](https://img.shields.io/badge/Status-alpha%20%E2%80%A2%20v0.0.1-yellow)](#-installation)
[![PRs welcome](https://img.shields.io/badge/PRs-welcome-brightgreen)](CONTRIBUTING.md)

</div>

---

## 🤔 What is this?

[Claude Code](https://claude.com/product/claude-code) — Anthropic's official CLI for Claude — emits **hook events** at every interesting moment of a coding session: a tool is about to run, the user submitted a prompt, a session is starting, the context just got compacted, and so on. There are **29 documented events** today, each with its own JSON wire shape and decision protocol.

If you want to **observe**, **intercept**, **redirect**, or **veto** what Claude Code does — from a PHP backend, a CLI, or a Symfony bundle — this package gives you a first-class SDK for it:

- 📥 **Decode** any inbound hook payload into an immutable typed event. No more `array_key_exists()` ladders.
- 📤 **Encode** valid response payloads with fluent builders that match the documented decision shapes byte-for-byte.
- 🔍 **Inspect** Claude Code's runtime state: stream transcripts, scan installed plugins / skills / agents / MCP servers, compute Sonnet pricing.
- 🛡️ **Lint** your `settings.json` so misconfigured hooks fail in dev, not in production.
- 📡 **Stay current** — a daily CI job diffs the live Anthropic doc and opens a GitHub issue the moment a new event ships.

It's the layer you'd otherwise have to reinvent in every project that touches Claude Code from PHP.

---

## ✨ Highlights

| | |
|---|---|
| 🎯 **29 typed events** | Every documented hook (`Session`, `Turn`, `Tool`, `Perm`, `Compact`, `Ctx`, `Team`) as an immutable DTO. Forward-compatible: unknown event names surface as `UnknownHookEvent`, never as exceptions. |
| 🧰 **12 typed tool inputs** | `Bash`, `Edit`, `Write`, `Read`, `Glob`, `Grep`, `WebFetch`, `WebSearch`, `Agent`, `AskUserQuestion`, `TodoWrite`, `Skill`. Plus a `RawToolInput` fallback that handles `mcp__<server>__<tool>` invocations and any first-party tool not yet modelled. |
| 📤 **13 response builders** | Immutable, fluent. `with*()` clones, `toArray()` ships. Decision shapes match the docs exactly. |
| 🪜 **Multi-source resolver** | Honours the documented precedence: **Policy → User → Project → Local → Plugin → Runtime**. Filesystem and in-memory loaders included; bring your own by implementing `SettingsLoader`. |
| 📜 **Streaming transcripts** | Generator-based JSONL reader with an 8 MB per-line cap (SEC-02), `compact_boundary` events, and a sidechain grouper. Tail huge transcripts without loading them into memory. |
| 💰 **Cost calculator** | Sonnet 4.5 price table out of the box; swap with your own `PriceTable`. Integer-cent arithmetic via a `Money` value object — no float drift. |
| 🔎 **Linter** | 6 core rules ship today (broad matchers in policy, unknown events, missing matchers, HTTP handlers without timeout, secret literals, broad matchers on prompt/agent hooks). 18 more on the roadmap (see [`CHANGELOG.md`](CHANGELOG.md)). |
| 🛡️ **Security-first** | `realpath`-based path-traversal protection (SEC-01), `Authorization` header redaction (SEC-09), single `JsonDecoder` chokepoint with `JSON_THROW_ON_ERROR`, safe-only YAML parsing (SEC-03), 10 MB body cap on the doc-drift fetch (SEC-06). |
| 🧪 **100 % covered, mutation-tested** | PHPStan max + strict, Psalm errorLevel 1, Deptrac layered architecture, MSI ≥ 80 / Covered MSI ≥ 85 — all enforced in CI. |
| 📡 **Daily doc-drift** | Scheduled CI job re-fetches `https://code.claude.com/docs/en/hooks` and opens a GitHub issue the moment Anthropic ships a new event. SHA-256 sidecar pins captured fixtures against silent edits. |

---

## 🚀 Quick start — 60 seconds

The whole loop is **decode → match → respond**:

```php
use Gerard\ClaudeCodeHooks\Event\HookEventFactory;
use Gerard\ClaudeCodeHooks\Event\Tool\PreToolUseEvent;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\BashInput;
use Gerard\ClaudeCodeHooks\Response\Tool\PreToolUseResponse;
use Gerard\ClaudeCodeHooks\Support\JsonDecoder;

// 1. Decode the inbound payload (always go through JsonDecoder).
$payload = JsonDecoder::decode(file_get_contents('php://input'), 'incoming-hook');

// 2. Resolve to a typed event.
$event = (new HookEventFactory())->fromPayload($payload);

// 3. Pattern-match — read typed properties, decide, respond.
$response = match (true) {
    $event instanceof PreToolUseEvent
        && $event->toolInput instanceof BashInput
        && str_starts_with($event->toolInput->command, 'rm -rf /')
            => PreToolUseResponse::deny('blocked: dangerous command'),
    default => PreToolUseResponse::allow(),
};

echo json_encode($response->toArray(), JSON_THROW_ON_ERROR);
```

That's it. No manual JSON spelunking, no copy-paste from the docs, no hand-rolled response builders.

---

## 📦 Installation

```bash
composer require gerard/claude-code-hooks
```

**Requirements**

- PHP **8.3+**
- Extensions: `ext-json`, `ext-mbstring`

**Runtime dependencies**

- `psr/log` ^3.0
- `symfony/http-client` ^7.0 (used by `AnthropicSpecExtractor`)
- `symfony/yaml` ^7.0 (used by the plugin / skill / agent scanners)

---

## 📚 Cookbook

### 🎯 Intercepting a tool call

`PreToolUseEvent::toolInput` is typed against the `ToolInput` interface — pattern-match on the concrete class to read the typed properties.

```php
use Gerard\ClaudeCodeHooks\Event\Tool\PreToolUseEvent;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\BashInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\WriteInput;
use Gerard\ClaudeCodeHooks\Response\Tool\PreToolUseResponse;

if ($event instanceof PreToolUseEvent) {
    $response = match (true) {
        $event->toolInput instanceof BashInput
            && preg_match('#\bsudo\b#', $event->toolInput->command)
                => PreToolUseResponse::deny('sudo blocked by hook policy'),

        $event->toolInput instanceof WriteInput
            && str_ends_with($event->toolInput->filePath, '.env')
                => PreToolUseResponse::ask('Confirm before overwriting .env'),

        default => PreToolUseResponse::allow(),
    };
}
```

The four documented decisions — `allow`, `deny`, `ask`, `defer` — are static factories on `PreToolUseResponse`. Want to mutate the input before letting it through? `->withUpdatedInput($newInput)`.

### 📜 Streaming a transcript

`TranscriptReader` is a `Generator` — it never loads the file into memory.

```php
use Gerard\ClaudeCodeHooks\Transcript\TranscriptReader;
use Gerard\ClaudeCodeHooks\Transcript\TranscriptCursor;
use Gerard\ClaudeCodeHooks\Transcript\TranscriptLine;
use Gerard\ClaudeCodeHooks\Transcript\BoundaryEvent;

$reader = new TranscriptReader();           // 8 MB per-line cap by default
$cursor = new TranscriptCursor($path, 0);

foreach ($reader->tail($cursor) as $entry) {
    if ($entry instanceof BoundaryEvent) {
        // The session was just compacted — relocate your cursor if needed.
        continue;
    }

    /** @var TranscriptLine $entry */
    if ($entry->truncated) {
        // Line exceeded the 8 MB cap — payload is empty, byte range is preserved.
        continue;
    }

    $entry->type;        // "user" | "assistant" | "system" | …
    $entry->isSidechain; // true when the line belongs to a subagent fork
    $entry->payload;     // the full decoded JSON line
}

// `return $reader->tail(...)` yields a TranscriptCursor advanced past the last
// complete line. Persist it to resume later without re-reading the whole file.
```

### 💰 Computing usage cost

```php
use Gerard\ClaudeCodeHooks\Cost\CostCalculator;
use Gerard\ClaudeCodeHooks\Cost\Sonnet45PriceTable;
use Gerard\ClaudeCodeHooks\Transcript\Usage;

$usage = new Usage(
    inputTokens: 12_000,
    outputTokens: 800,
    cacheReadInputTokens: 9_500,
    cacheCreationInputTokens: 2_000,
    serviceTier: 'standard',
);

$cost = (new CostCalculator())->compute($usage, new Sonnet45PriceTable());

$cost->microDollars;  // int — exact, never a float
$cost->asDollars();   // float — for display only
```

`Money` is closed under addition; sum the cost of every assistant turn in a transcript by chaining `->add()`.

### 🪜 Resolving multi-source `settings.json`

```php
use Gerard\ClaudeCodeHooks\Resolver\HookConfigResolver;
use Gerard\ClaudeCodeHooks\Resolver\FilesystemSettingsLoader;

$resolver = new HookConfigResolver(new FilesystemSettingsLoader([
    'policySettings'  => '/etc/claude-code/settings.json',
    'userSettings'    => $_SERVER['HOME'].'/.claude/settings.json',
    'projectSettings' => getcwd().'/.claude/settings.json',
    'localSettings'   => getcwd().'/.claude/settings.local.json',
]));

$registry = $resolver->resolve();

foreach ($registry->rules as $rule) {
    $rule->event;    // "PreToolUse"
    $rule->matcher;  // "Bash"
    $rule->handler;  // ['type' => 'http', 'url' => '…', …]
    $rule->source;   // HookSource::Project
    $rule->managed;  // bool — set when the policy enforces `allowManagedHooksOnly`
}
```

The resolver honours the full documented precedence chain and applies the `allowManagedHooksOnly` policy filter before merging.

### 🛠️ Building a `settings.json` fragment

```php
use Gerard\ClaudeCodeHooks\Builder\HookConfigBuilder;

$config = HookConfigBuilder::create()
    ->event('PreToolUse')->matcher('Bash')
        ->httpHandler('http://127.0.0.1:42987/?event=PreToolUse')
            ->withHeader('Authorization', '${GERARD_TOKEN}')  // placeholder only
            ->withTimeout(5)
            ->async(true)
    ->build();

file_put_contents(
    '.claude/settings.json',
    json_encode($config, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
);
```

Note: `withHeader('Authorization', …)` only accepts `${VAR}` placeholders — this layer never holds real secrets. Resolution happens in the future Symfony bundle.

### 🔎 Linting a config

```php
use Gerard\ClaudeCodeHooks\Linter\HookLinter;
use Gerard\ClaudeCodeHooks\Linter\HookConfig;
use Gerard\ClaudeCodeHooks\Linter\ConfigProfile;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch001BroadMatcherInPolicy;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch004HttpHandlerWithoutTimeout;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch005SecretInUrlLiteral;

$linter = new HookLinter([
    new Cch001BroadMatcherInPolicy(),
    new Cch004HttpHandlerWithoutTimeout(),
    new Cch005SecretInUrlLiteral(),
    // … or pass every rule under src/Linter/Rule/
]);

$findings = $linter->lint([$hookConfig], new ConfigProfile());

foreach ($findings as $finding) {
    $finding->severity;     // Severity::Error | Warning | Notice
    $finding->code;         // "CCH004"
    $finding->message;      // never embeds a secret value (SEC-08)
    $finding->jsonPointer;  // "/hooks/PreToolUse/0/hooks/0/timeout"
}
```

### 📤 Producing a structured response

Every response builder is immutable: `with*()` returns a clone, `toArray()` ships.

```php
use Gerard\ClaudeCodeHooks\Response\Tool\PreToolUseResponse;

$response = PreToolUseResponse::allow()
    ->withSuppressOutput(false)
    ->withSystemMessage('Audited');

echo json_encode($response->toArray(), JSON_THROW_ON_ERROR);
```

---

## 🗂️ All supported events (29)

| Family | Events |
|---|---|
| **Session** | `SessionStart`, `SessionEnd`, `Setup` |
| **Turn** | `UserPromptSubmit`, `UserPromptExpansion`, `Stop`, `StopFailure`, `SubagentStart`, `SubagentStop` |
| **Tool** | `PreToolUse`, `PostToolUse`, `PostToolUseFailure`, `PostToolBatch` |
| **Perm** | `PermissionRequest`, `PermissionDenied`, `Notification`, `Elicitation`, `ElicitationResult` |
| **Compact** | `PreCompact`, `PostCompact` |
| **Ctx** | `ConfigChange`, `CwdChanged`, `FileChanged`, `InstructionsLoaded`, `WorktreeCreate`, `WorktreeRemove` |
| **Team** | `TaskCreated`, `TaskCompleted`, `TeammateIdle` |

Anything Anthropic ships **after** the pinned snapshot date surfaces as `UnknownHookEvent` — your code keeps running, the daily doc-drift job opens an issue, the next release adds the typed DTO.

## 🛠️ All supported tool inputs (12 + fallback)

| DTO | Wire `tool_name` | Notable properties |
|---|---|---|
| `BashInput` | `Bash` | `command`, `description`, `timeout`, `runInBackground` |
| `ReadInput` | `Read` | `filePath`, `offset`, `limit` |
| `WriteInput` | `Write` | `filePath`, `content` |
| `EditInput` | `Edit` | `filePath`, `oldString`, `newString`, `replaceAll` |
| `GlobInput` | `Glob` | `pattern`, `path` |
| `GrepInput` | `Grep` | `pattern`, `path`, `glob`, `outputMode`, `caseInsensitive`, `multiline` |
| `WebFetchInput` | `WebFetch` | `url`, `prompt` |
| `WebSearchInput` | `WebSearch` | `query`, `allowedDomains`, `blockedDomains` |
| `AgentInput` | `Agent` | `prompt`, `description`, `subagentType`, `model` |
| `AskUserQuestionInput` | `AskUserQuestion` | `questions`, `answers` |
| `TodoWriteInput` | `TodoWrite` | `todos[]` (`content`, `status`, `activeForm`) |
| `SkillInput` | `Skill` | `skill`, `args` (`#[\SensitiveParameter]`) |
| `RawToolInput` | _everything else_ | `name`, `payload` — covers `mcp__*` + first-party tools without dedicated DTOs |

---

## 🔌 Wire shapes — three quick references

The wire field names below match the actual shape Claude Code emits, verified against a real-corpus sample of **4 089 events**.

<details>
<summary><strong><code>PostToolUse</code></strong></summary>

```json
{
  "hook_event_name": "PostToolUse",
  "session_id": "…",
  "transcript_path": "…",
  "cwd": "…",
  "permission_mode": "default",
  "tool_name": "Bash",
  "tool_input":  { "command": "ls", "description": "list" },
  "tool_response": { "stdout": "…", "stderr": "", "interrupted": false },
  "tool_use_id": "toolu_01…",
  "duration_ms": 181
}
```

```php
$event->toolName;     // "Bash"
$event->toolInput;    // BashInput { command: "ls", description: "list", … }
$event->toolResponse; // string|array<string,mixed> — the raw response shape
$event->durationMs;   // int|null — null when the wire omits duration_ms
$event->toolUseId;    // string|null
```

</details>

<details>
<summary><strong><code>SessionEnd</code></strong></summary>

```json
{ "hook_event_name": "SessionEnd", "session_id": "…", "transcript_path": "…", "cwd": "…", "reason": "other" }
```

```php
$event->reason;         // "logout" | "clear" | "other" | …
$event->permissionMode; // PermissionMode::Default when the wire omits it
```

</details>

<details>
<summary><strong><code>Stop</code></strong></summary>

```json
{
  "hook_event_name": "Stop",
  "session_id": "…",
  "transcript_path": "…",
  "cwd": "…",
  "stop_hook_active": false,
  "last_assistant_message": "Goodbye!"
}
```

```php
$event->stopHookActive;       // bool — required by the wire contract
$event->lastAssistantMessage; // string|null
```

</details>

> 💡 **One JSON entry point.** `JsonDecoder::decode()` is the only sanctioned JSON parser in the package. It enforces `JSON_THROW_ON_ERROR` and a depth cap of 64. Raw `json_decode()` calls in `src/` are forbidden by CI.

---

## 📡 Staying in sync with the Anthropic spec

Two CLI binaries ship with the package and back the daily `doc-drift.yml` workflow:

| Binary | What it does | Exit codes |
|---|---|---|
| `bin/extract-anthropic-spec` | Fetches `https://code.claude.com/docs/en/hooks` (or, with `--from-html-fixture <path>`, parses a captured HTML fixture) and emits a canonicalised JSON spec on stdout or `--output <path>`. | **0** ok • **2** parse failure (NOT drift — the workflow surfaces this as "extractor failure" instead of opening phantom "events removed" issues) |
| `bin/check-doc-drift` | Diffs the live spec against `tests/Fixtures/anthropic-spec/snapshot.json`. | **0** no drift • **1** drift detected • **2** parse error |

**Reblessing the snapshot** when Anthropic ships a new event:

```bash
# 1. Capture a fresh page snapshot.
curl -sSL --max-time 15 \
  -H "User-Agent: gerard-doc-drift/1.0" \
  https://code.claude.com/docs/en/hooks \
  > tests/Fixtures/html/code-claude-com-hooks-YYYY-MM-DD.html

# 2. Sidecar SHA-256 (anti-MITM, anti-silent-edit).
( cd tests/Fixtures/html \
  && sha256sum code-claude-com-hooks-YYYY-MM-DD.html \
       > code-claude-com-hooks-YYYY-MM-DD.html.sha256 )

# 3. Bless the snapshot from the captured HTML.
bin/extract-anthropic-spec \
  --from-html-fixture tests/Fixtures/html/code-claude-com-hooks-YYYY-MM-DD.html \
  --output tests/Fixtures/anthropic-spec/snapshot.json
```

Then bump `AnthropicSpecExtractor::SNAPSHOT_VERSION` to the new date. `AnthropicSpecExtractorLiveShapeTest` asserts the SHA-256 sidecar still matches.

---

## 🧪 Quality bar

This is a security- and observability-critical package. The bar is set accordingly:

- **PHPStan level `max`** + `phpstan-strict-rules` + `phpstan-deprecation-rules` + `phpstan-phpunit` + `ergebnis/phpstan-rules` — **zero baseline**.
- **Psalm `errorLevel: 1`** + `findUnusedCode: true`.
- **Deptrac** — strict layered architecture: `Event → Response → Resolver → Transcript → Scanner → Linter → Builder → Conformance → Support`.
- **PHPUnit 11+** with `failOnRisky`, `failOnWarning`, strict coverage metadata.
- **100 % class + method coverage** enforced via `bin/check-coverage`.
- **Infection** — MSI ≥ **80**, Covered MSI ≥ **85**.
- **Composer audit** every CI run.

Run the full local gate:

```bash
composer install
composer cs
composer phpstan
composer psalm
composer deptrac
composer test:coverage
bin/check-coverage var/coverage/clover.xml
composer test:integration
composer test:conformance
composer test:mutation
composer audit
```

Or the curated bundles:

```bash
composer qa       # cs + phpstan + psalm + deptrac + test:unit + audit
composer qa:full  # qa + test:integration + test:conformance + test:mutation
```

---

## 🔐 Security highlights

| ID | Mitigation |
|---|---|
| **SEC-01** | All scanner paths pass through `realpath()`; symlink escapes from `~/.claude/plugins` etc. are rejected. |
| **SEC-02** | `TranscriptReader` caps every line at 8 MB; oversize lines surface as `TranscriptLine{truncated: true}` instead of OOM-ing. |
| **SEC-03** | YAML decoded only via `Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE`. Object instantiation flags are forbidden (CI grep guard). |
| **SEC-04 / 05** | Decoder exceptions never embed the offending payload — only the source path and offset. |
| **SEC-06** | `AnthropicSpecExtractor` HTTP options are pinned: `verify_peer/verify_host` on, host allow-list, 10 MB body cap. |
| **SEC-08** | Linter `Finding::$message` is capped at 256 bytes and may never embed >8 contiguous chars from the offending input — invariant-tested. |
| **SEC-09** | `HookConfig::__debugInfo()` redacts the `Authorization` header. |
| **SEC-15** | Captured HTML fixtures are CI-scanned for `Bearer `, `eyJ`, `gha_`, `glpat-`, `sk-`, `xoxb-` patterns. |

---

## 🤝 Contributing

Contributions are welcome — this is a community package and the API is intentionally narrow so it stays reviewable. Please read [`CONTRIBUTING.md`](CONTRIBUTING.md) for:

- 🪜 the local quality-gate checklist;
- 🆕 the **3-step "add an event" workflow**;
- 📸 the **fixture HTML capture** protocol (with the `tests/Fixtures/html/*.sha256` sidecar);
- 🔐 the **`#[\SensitiveParameter]`** convention enforced by the CI grep guard.

Found a bug or a missing event? **[Open an issue](https://github.com/gerard-labs/claude-code-hooks/issues/new)** — a failing test fixture in `tests/Fixtures/payloads/` is the fastest path to a fix.

See [`CHANGELOG.md`](CHANGELOG.md) for notable changes and the linter-rule roadmap, and [`docs/adr/`](docs/adr) for architectural decision records.

---

## 📄 License

Released under the [MIT License](LICENSE). Copyright © 2026 Sébastien Dieunidou and contributors.

---

<div align="center">

🪝 **Built for the PHP community that ships with Claude Code.**

[Report a bug](https://github.com/gerard-labs/claude-code-hooks/issues) • [Read the changelog](CHANGELOG.md) • [Contribute](CONTRIBUTING.md)

</div>
