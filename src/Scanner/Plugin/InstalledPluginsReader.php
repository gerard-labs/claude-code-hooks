<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Plugin;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Support\JsonDecoder;

use function is_array;
use function is_string;

final readonly class InstalledPluginsReader
{
    /**
     * Reads `installed_plugins.json` (cf. OBSERVABILITY_SPEC §3.4) and returns
     * a flat list of `(plugin_id, scope, installPath, version, gitCommitSha)`.
     *
     * @return list<array{id: string, scope: string, installPath: string, version: string, gitCommitSha: ?string}>
     */
    public function read(string $jsonPath): array
    {
        if (!is_file($jsonPath)) {
            return [];
        }

        $raw = file_get_contents($jsonPath);

        if ($raw === false || $raw === '') {
            return [];
        }

        /** @var array<string, mixed> $decoded */
        $decoded = JsonDecoder::decode($raw, $jsonPath);

        if (!array_key_exists('plugins', $decoded) || !is_array($decoded['plugins'])) {
            throw InvalidPayloadException::missingField($jsonPath, '$.plugins');
        }

        $out = [];
        foreach ($decoded['plugins'] as $id => $entries) {
            if (!is_string($id) || !is_array($entries)) {
                continue;
            }
            foreach ($entries as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $installPath = is_string($entry['installPath'] ?? null) ? $entry['installPath'] : null;
                $scope = is_string($entry['scope'] ?? null) ? $entry['scope'] : 'project';
                $version = is_string($entry['version'] ?? null) ? $entry['version'] : '0.0.0';
                $gitSha = is_string($entry['gitCommitSha'] ?? null) ? $entry['gitCommitSha'] : null;

                if ($installPath === null) {
                    continue;
                }

                $out[] = [
                    'id' => $id,
                    'scope' => $scope,
                    'installPath' => $installPath,
                    'version' => $version,
                    'gitCommitSha' => $gitSha,
                ];
            }
        }

        return $out;
    }
}
