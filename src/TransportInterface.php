<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk;

/**
 * Minimal transport contract used by the generated API groups.
 *
 * Implementations may provide a project-specific HTTP client, tracing, or test double
 * without coupling the SDK to a third-party HTTP package.
 */
interface TransportInterface
{
    public function withBearerToken(string $token): static;

    public function serialize(mixed $body): string;

    public function postPublic(string $path, mixed $body, array $headers = []): mixed;

    public function postAuthorized(string $path, mixed $body, array $headers = []): mixed;

    public function postSerializedAuthorized(string $path, string $body, array $headers = []): mixed;

    public function postAuthorizedBoolean(string $path, mixed $body): bool;
}
