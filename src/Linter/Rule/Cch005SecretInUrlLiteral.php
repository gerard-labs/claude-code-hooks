<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Linter\Rule;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Linter\ConfigProfile;
use Gerard\ClaudeCodeHooks\Linter\Finding;
use Gerard\ClaudeCodeHooks\Linter\HookConfig;
use Gerard\ClaudeCodeHooks\Linter\LinterRule;
use Gerard\ClaudeCodeHooks\Linter\Severity;

use function is_array;
use function is_string;

use Override;

use function sprintf;

/**
 * CCH005 — secret-shaped literal in URL or `Authorization` header.
 *
 * SEC-08 — the finding message NEVER quotes the suspected secret. It only
 * reports the kind (Bearer/glpat/sk-) and the JSON Pointer; the offender's
 * value is never reflected back into the linter output, the issue body, or
 * a CI artifact.
 *
 * Whitelisted: any value that is exclusively `${VAR}` placeholders.
 */
final class Cch005SecretInUrlLiteral implements LinterRule
{
    /**
     * @return iterable<Finding>
     */
    #[Override]
    public function inspect(HookConfig $config, ConfigProfile $profile): iterable
    {
        $url = is_string($config->handler['url'] ?? null) ? $config->handler['url'] : '';

        if ($url !== '' && ($kind = $this->detectSecretKind($url)) !== null) {
            yield new Finding(
                severity: Severity::Error,
                code: 'CCH005',
                message: sprintf(
                    'Literal %s in %s handler URL — replace with ${VAR} placeholder',
                    $kind,
                    $config->event,
                ),
                jsonPointer: sprintf('$.hooks.%s.handler.url', $config->event),
            );
        }

        if (array_key_exists('headers', $config->handler) && is_array($config->handler['headers'])) {
            foreach ($config->handler['headers'] as $name => $value) {
                if (!is_string($name) || !is_string($value)) {
                    continue;
                }
                $kind = $this->detectSecretKind($value);
                if ($kind === null) {
                    continue;
                }

                yield new Finding(
                    severity: Severity::Error,
                    code: 'CCH005',
                    message: sprintf(
                        'Literal %s in %s handler header %s — replace with ${VAR} placeholder',
                        $kind,
                        $config->event,
                        $name,
                    ),
                    jsonPointer: sprintf('$.hooks.%s.handler.headers.%s', $config->event, $name),
                );
            }
        }
    }

    private function detectSecretKind(string $value): ?string
    {
        if (preg_match('/^\s*(\$\{[A-Z_][A-Z0-9_]*\}\s*)+$/', $value) === 1) {
            return null;
        }
        if (preg_match('/Bearer\s+[A-Za-z0-9._\-]{16,}/', $value) === 1) {
            return 'Bearer token';
        }
        if (preg_match('/glpat-[A-Za-z0-9_-]{8,}/', $value) === 1) {
            return 'GitLab PAT';
        }
        if (preg_match('/sk-[A-Za-z0-9]{16,}/', $value) === 1) {
            return 'sk- API key';
        }
        if (preg_match('/eyJ[A-Za-z0-9._-]{16,}/', $value) === 1) {
            return 'JWT';
        }

        return null;
    }
}
