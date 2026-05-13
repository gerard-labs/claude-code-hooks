<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Mcp;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Support\JsonDecoder;

use function in_array;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Reads MCP server declarations from one or more config files
 * (settings.json `mcpServers` section + project `.mcp.json`).
 *
 * SEC-09 — exceptions raised here use JSON Pointers, never header values.
 */
final readonly class McpConfigReader
{
    /**
     * @param list<string> $configPaths
     *
     * @return list<McpServer>
     */
    public function read(array $configPaths): array
    {
        $out = [];

        foreach ($configPaths as $path) {
            if (!is_file($path)) {
                continue;
            }

            $raw = file_get_contents($path);
            if ($raw === false || $raw === '') {
                continue;
            }

            /** @var array<string, mixed> $decoded */
            $decoded = JsonDecoder::decode($raw, $path);
            if (!array_key_exists('mcpServers', $decoded) || !is_array($decoded['mcpServers'])) {
                continue;
            }

            foreach ($decoded['mcpServers'] as $name => $entry) {
                if (!is_string($name) || !is_array($entry)) {
                    continue;
                }
                $out[] = $this->parseServer($name, $entry);
            }
        }

        return $out;
    }

    /**
     * @param array<array-key, mixed> $entry
     */
    private function parseServer(string $name, array $entry): McpServer
    {
        $transportRaw = is_string($entry['type'] ?? null) ? $entry['type'] : 'stdio';
        $transport = McpTransport::tryFrom($transportRaw)
            ?? throw InvalidPayloadException::unsupportedValue(
                'mcp config',
                sprintf('$.mcpServers.%s.type', $name),
                'stdio|http|sse',
            );

        $command = is_string($entry['command'] ?? null) ? $entry['command'] : null;
        $url = is_string($entry['url'] ?? null) ? $entry['url'] : null;

        /** @var array<string, string> $headers */
        $headers = [];
        if (array_key_exists('headers', $entry) && is_array($entry['headers'])) {
            foreach ($entry['headers'] as $hName => $hValue) {
                if (is_string($hName) && is_string($hValue)) {
                    $headers[$hName] = $hValue;
                }
            }
        }

        $envVarRefs = $this->collectEnvVarRefs($entry);

        return new McpServer(
            name: $name,
            transport: $transport,
            command: $command,
            url: $url,
            headers: $headers,
            envVarRefs: $envVarRefs,
        );
    }

    /**
     * @param array<array-key, mixed> $entry
     *
     * @return list<string>
     */
    private function collectEnvVarRefs(array $entry): array
    {
        $refs = [];
        $candidates = [];

        if (array_key_exists('env', $entry) && is_array($entry['env'])) {
            foreach ($entry['env'] as $value) {
                if (is_string($value)) {
                    $candidates[] = $value;
                }
            }
        }
        if (array_key_exists('headers', $entry) && is_array($entry['headers'])) {
            foreach ($entry['headers'] as $value) {
                if (is_string($value)) {
                    $candidates[] = $value;
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (preg_match_all('/\$\{([A-Z_][A-Z0-9_]*)\}/', $candidate, $matches) > 0) {
                foreach ($matches[1] as $varName) {
                    if (!in_array($varName, $refs, true)) {
                        $refs[] = $varName;
                    }
                }
            }
        }

        return $refs;
    }
}
