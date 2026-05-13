<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Resolver;

interface SettingsLoader
{
    /**
     * Returns the parsed settings tree for a given source, or null if no
     * settings exist at that source. The shape mirrors the Claude Code
     * `settings.json` JSON schema (cf. OBSERVABILITY_SPEC §3.3).
     *
     * @return array<string, mixed>|null
     */
    public function load(HookSource $source): ?array;
}
