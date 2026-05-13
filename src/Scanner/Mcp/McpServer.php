<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Mcp;

use Override;

use function sprintf;

use Stringable;

use function strlen;

/**
 * SEC-09 — `__debugInfo()` redacts headers.Authorization, `__toString()`
 * returns only `name + transport`. Exceptions raised by the reader carry a
 * JSON Pointer, never a header value.
 */
final readonly class McpServer implements Stringable
{
    /**
     * @param array<string, string> $headers
     * @param list<string>          $envVarRefs
     */
    public function __construct(
        public string $name,
        public McpTransport $transport,
        public ?string $command = null,
        public ?string $url = null,
        public array $headers = [],
        public array $envVarRefs = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $headers = [];
        foreach ($this->headers as $name => $value) {
            $headers[$name] = strcasecmp($name, 'Authorization') === 0
                ? sprintf('***redacted*** (length=%d)', strlen($value))
                : $value;
        }

        return [
            'name' => $this->name,
            'transport' => $this->transport->value,
            'command' => $this->command,
            'url' => $this->url,
            'headers' => $headers,
            'envVarRefs' => $this->envVarRefs,
        ];
    }

    #[Override]
    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->name, $this->transport->value);
    }
}
