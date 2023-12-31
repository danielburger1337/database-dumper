<?php declare(strict_types=1);

namespace App\Util;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class Dsn implements \Stringable
{
    /**
     * @param array<string|int, mixed> $options
     */
    public function __construct(
        public string $scheme,
        public string $host,
        public ?string $path,
        public ?string $user = null,
        #[\SensitiveParameter]
        public ?string $password = null,
        public ?int $port = null,
        public array $options = []
    ) {
    }

    /**
     * Create a DSN from its string representation.
     *
     * @param string $dsn the DSN to parse
     *
     * @throws \InvalidArgumentException if the DSN is malformed
     */
    public static function fromString(string $dsn): self
    {
        if (false === ($parsedDsn = \parse_url($dsn))) {
            throw new \InvalidArgumentException(\sprintf('The "%s" DSN is not a valid URL.', $dsn));
        }

        if (!isset($parsedDsn['scheme'])) {
            throw new \InvalidArgumentException(\sprintf('The "%s" DSN must contain a scheme.', $dsn));
        }

        if (!isset($parsedDsn['host'])) {
            throw new \InvalidArgumentException(\sprintf('The "%s" DSN must contain a host.', $dsn));
        }

        $user = '' !== ($parsedDsn['user'] ?? '') ? \urldecode($parsedDsn['user']) : null;
        $password = '' !== ($parsedDsn['pass'] ?? '') ? \urldecode($parsedDsn['pass']) : null;
        $port = $parsedDsn['port'] ?? null;
        $path = $parsedDsn['path'] ?? null;
        \parse_str($parsedDsn['query'] ?? '', $query);

        return new self($parsedDsn['scheme'], $parsedDsn['host'], $path, $user, $password, $port, $query);
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPort(int $default = null): ?int
    {
        return $this->port ?? $default;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @return array<string|int, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function hasOption(string $key): bool
    {
        return \array_key_exists($key, $this->options);
    }

    public function getRequiredOption(string $key): mixed
    {
        if (!\array_key_exists($key, $this->options)) {
            throw new \InvalidArgumentException(\sprintf('The "%s" DSN does not have the required "%s" option.', $this->toString(), $key));
        }

        return $this->options[$key];
    }

    public function toString(bool $hidePassword = true): string
    {
        $url = $this->scheme.'://';

        if (null !== $this->user) {
            $url .= $this->user;

            if (null !== $this->password) {
                $url .= ':'.(!$hidePassword ? $this->password : '****');
            }

            $url .= '@';
        }

        $url .= $this->host.$this->path;

        if (\count($this->options) > 0) {
            $url .= '?'.\http_build_query($this->options);
        }

        return $url;
    }

    public function toDisplayString(): string
    {
        return $this->scheme.'://'.$this->host.$this->path;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
