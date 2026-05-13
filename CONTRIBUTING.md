# Contributing

## Local quality gate

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
vendor/bin/composer-require-checker check
```

All of the above must pass before opening a PR. The CI replays this same set in headless mode via the reusable [`_composer-project.yml`](.github/workflows/_composer-project.yml) workflow, called from [`ci.yml`](.github/workflows/ci.yml).

## Add an event — 3-line workflow

1. Create the DTO at `src/Event/<Family>/<Name>Event.php` and the response (if applicable) at `src/Response/<Family>/<Name>Response.php`.
2. Register the event in `src/Event/HookEventFactory.php` (table dispatch) and add a fixture at `tests/Fixtures/payloads/<Name>.json`.
3. Bless the snapshot with `bin/extract-anthropic-spec --output tests/Fixtures/anthropic-spec/snapshot.json`, run `composer test:conformance`, commit.

## YAML / JSON discipline

- All YAML parsing flows through `Gerard\ClaudeCodeHooks\Support\FrontmatterDecoder::decode()` which calls `Yaml::parse($yaml, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE)` — no `Yaml::PARSE_OBJECT*` flags allowed (SEC-03, enforced via CI grep guard).
- All JSON parsing flows through `Gerard\ClaudeCodeHooks\Support\JsonDecoder::decode()` which uses `JSON_THROW_ON_ERROR` and `depth: 64`. Raw `json_decode` calls in `src/` are forbidden.
- Exceptions raised from these decoders never embed the offending payload — only the source path and offset (SEC-04, SEC-05).

## Fixture HTML capture (doc-drift)

When refreshing the snapshot, capture the live Anthropic HTML pages into `tests/Fixtures/html/` **with secrets stripped first** (CI guard fails on `Bearer `, `eyJ`, `gha_`, `glpat-`, `sk-`, `xoxb-` patterns inside that directory — SEC-15).

## SensitiveParameter convention

This core layer holds no secrets — `withHeader('Authorization', '${TOKEN}')` only accepts `${...}` placeholders. The future Symfony bundle will be the layer that handles real secret resolution; whenever a parameter named `$token`, `$bearer`, `$secret`, `$apiKey`, or `$password` is added, it must carry `#[\SensitiveParameter]`. CI greps for these names without the attribute and fails.

## Coding standards

- `declare(strict_types=1);` on every PHP file.
- `final class` by default; `readonly class` for DTOs and value objects.
- `#[\Override]` on every override.
- `match` over `switch`.
- Custom exceptions implement `Gerard\ClaudeCodeHooks\Exception\HookException`.
- No `// TODO`, no `// FIXME`, no `@phpstan-ignore`, no `@psalm-suppress`. Fix the root cause.
