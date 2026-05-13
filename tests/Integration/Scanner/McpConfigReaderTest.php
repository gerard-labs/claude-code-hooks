<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Integration\Scanner;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Scanner\Mcp\McpConfigReader;
use Gerard\ClaudeCodeHooks\Scanner\Mcp\McpServer;
use Gerard\ClaudeCodeHooks\Scanner\Mcp\McpTransport;

use function is_array;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(McpConfigReader::class)]
#[CoversClass(McpServer::class)]
#[CoversClass(McpTransport::class)]
final class McpConfigReaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/cch-mcp-' . bin2hex(random_bytes(6));
        mkdir($this->tmpDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tmpDir . '/*');
        foreach ($files === false ? [] : $files as $f) {
            unlink($f);
        }
        rmdir($this->tmpDir);
    }

    public function testItPreservesEnvVarPlaceholders(): void
    {
        $path = $this->tmpDir . '/.mcp.json';
        file_put_contents($path, json_encode([
            'mcpServers' => [
                'gitlab' => [
                    'command' => 'npx',
                    'env' => ['TOKEN' => '${GITLAB_TOKEN}'],
                    'type' => 'stdio',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $servers = (new McpConfigReader())->read([$path]);
        self::assertCount(1, $servers);
        self::assertContains('GITLAB_TOKEN', $servers[0]->envVarRefs);
    }

    public function testItMapsTransportStringToEnum(): void
    {
        $path = $this->tmpDir . '/.mcp.json';
        file_put_contents($path, json_encode([
            'mcpServers' => [
                'sentry' => ['url' => 'https://x', 'type' => 'http', 'headers' => ['Authorization' => '${SENTRY_TOKEN}']],
            ],
        ], JSON_THROW_ON_ERROR));
        $servers = (new McpConfigReader())->read([$path]);
        self::assertSame(McpTransport::Http, $servers[0]->transport);
    }

    public function testItRejectsUnsupportedTransportWithJsonPointer(): void
    {
        $path = $this->tmpDir . '/.mcp.json';
        file_put_contents($path, json_encode([
            'mcpServers' => ['x' => ['type' => 'wat']],
        ], JSON_THROW_ON_ERROR));
        try {
            (new McpConfigReader())->read([$path]);
            self::fail('Expected InvalidPayloadException');
        } catch (InvalidPayloadException $e) {
            self::assertStringContainsString('$.mcpServers.x.type', $e->getMessage());
            self::assertStringNotContainsString('wat', $e->getMessage(), 'Exception must use JSON Pointer, not value');
        }
    }

    public function testItRedactsAuthorizationInDebugInfo(): void
    {
        $server = new McpServer(
            'gitlab',
            McpTransport::Http,
            command: null,
            url: 'https://x',
            headers: ['Authorization' => 'Bearer secret-xyz', 'Accept' => 'application/json'],
        );
        $debug = $server->__debugInfo();
        $headers = $this->asArray($debug['headers']);
        $auth = $headers['Authorization'];
        self::assertIsString($auth);
        self::assertStringContainsString('***redacted***', $auth);
        self::assertSame('application/json', $headers['Accept']);
    }

    public function testToStringOmitsHeaders(): void
    {
        $server = new McpServer('gitlab', McpTransport::Stdio, command: 'npx');
        self::assertSame('gitlab (stdio)', (string) $server);
    }

    public function testItMergesAllSourcesWithProvenance(): void
    {
        $a = $this->tmpDir . '/a.json';
        $b = $this->tmpDir . '/b.json';
        file_put_contents($a, json_encode(['mcpServers' => ['s1' => ['type' => 'stdio']]], JSON_THROW_ON_ERROR));
        file_put_contents($b, json_encode(['mcpServers' => ['s2' => ['type' => 'sse', 'url' => 'https://x']]], JSON_THROW_ON_ERROR));
        $servers = (new McpConfigReader())->read([$a, $b]);
        self::assertCount(2, $servers);
    }

    public function testItIgnoresMissingFilesAndMalformed(): void
    {
        self::assertSame([], (new McpConfigReader())->read(['/nonexistent.json']));
        $empty = $this->tmpDir . '/empty.json';
        file_put_contents($empty, '');
        self::assertSame([], (new McpConfigReader())->read([$empty]));
    }

    public function testItIgnoresFileWithoutMcpServersKey(): void
    {
        $path = $this->tmpDir . '/no.json';
        file_put_contents($path, '{}');
        self::assertSame([], (new McpConfigReader())->read([$path]));
    }
    /**
     * @return array<array-key, mixed>
     */
    private function asArray(mixed $value): array
    {
        if (!is_array($value)) {
            self::fail('expected array, got ' . get_debug_type($value));
        }

        return $value;
    }
}
