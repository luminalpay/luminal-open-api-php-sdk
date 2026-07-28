<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Internal;

/** @internal Minimal host-only cookie jar for the SDK's default HTTP sender. */
final class CookieJar
{
    /** @var array<string, array{name: string, value: string, host: string, path: string, secure: bool, expiresAt: ?int}> */
    private array $cookies = [];

    public function headerFor(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'])) {
            return null;
        }

        $now = time();
        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '/';
        $secure = strtolower($parts['scheme'] ?? '') === 'https';
        $matches = [];
        foreach ($this->cookies as $key => $cookie) {
            if ($cookie['expiresAt'] !== null && $cookie['expiresAt'] <= $now) {
                unset($this->cookies[$key]);
                continue;
            }
            if ($cookie['host'] !== $host
                || ($cookie['secure'] && !$secure)
                || !self::pathMatches($path, $cookie['path'])) {
                continue;
            }
            $matches[] = $cookie;
        }
        usort($matches, static fn (array $left, array $right): int => strlen($right['path']) <=> strlen($left['path']));

        return $matches === []
            ? null
            : implode('; ', array_map(
                static fn (array $cookie): string => $cookie['name'] . '=' . $cookie['value'],
                $matches,
            ));
    }

    /** @param list<string> $headers */
    public function storeFromResponse(string $url, array $headers): void
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['host'])) {
            return;
        }

        $host = strtolower($parts['host']);
        $defaultPath = self::defaultPath($parts['path'] ?? '/');
        foreach ($headers as $header) {
            if (!preg_match('/^Set-Cookie:\s*(.*)$/i', $header, $matches)) {
                continue;
            }
            $this->store($host, $defaultPath, $matches[1]);
        }
    }

    private function store(string $host, string $defaultPath, string $header): void
    {
        $parts = array_map('trim', explode(';', $header));
        $pair = array_shift($parts);
        if ($pair === null || !str_contains($pair, '=')) {
            return;
        }
        [$name, $value] = explode('=', $pair, 2);
        if (!preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D', $name)
            || preg_match('/[\x00-\x20\x7f;]/', $value)) {
            return;
        }

        $path = $defaultPath;
        $secure = false;
        $expiresAt = null;
        $maxAge = null;
        foreach ($parts as $attribute) {
            [$attributeName, $attributeValue] = array_pad(explode('=', $attribute, 2), 2, null);
            switch (strtolower(trim($attributeName))) {
                case 'path':
                    if ($attributeValue !== null && str_starts_with(trim($attributeValue), '/')) {
                        $path = trim($attributeValue);
                    }
                    break;
                case 'secure':
                    $secure = true;
                    break;
                case 'max-age':
                    if ($attributeValue !== null && preg_match('/^-?[0-9]+$/D', trim($attributeValue))) {
                        $maxAge = (int) trim($attributeValue);
                    }
                    break;
                case 'expires':
                    if ($attributeValue !== null && ($timestamp = strtotime(trim($attributeValue))) !== false) {
                        $expiresAt = $timestamp;
                    }
                    break;
            }
        }

        $key = $host . "\0" . $path . "\0" . $name;
        if ($maxAge !== null) {
            if ($maxAge <= 0) {
                unset($this->cookies[$key]);
                return;
            }
            $expiresAt = time() + $maxAge;
        }
        if ($expiresAt !== null && $expiresAt <= time()) {
            unset($this->cookies[$key]);
            return;
        }
        $this->cookies[$key] = compact('name', 'value', 'host', 'path', 'secure', 'expiresAt');
    }

    private static function defaultPath(string $requestPath): string
    {
        if (!str_starts_with($requestPath, '/') || substr_count($requestPath, '/') <= 1) {
            return '/';
        }
        return substr($requestPath, 0, (int) strrpos($requestPath, '/'));
    }

    private static function pathMatches(string $requestPath, string $cookiePath): bool
    {
        return $requestPath === $cookiePath
            || (str_starts_with($requestPath, $cookiePath)
                && (str_ends_with($cookiePath, '/') || ($requestPath[strlen($cookiePath)] ?? '') === '/'));
    }
}
