<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk;

use RuntimeException;

/** Represents an HTTP, transport, envelope, or business error returned by the API. */
final class ApiException extends RuntimeException
{
    /** Prevents response bodies from being dumped with exception diagnostics. */
    public function __debugInfo(): array
    {
        return [
            'message' => $this->getMessage(),
            'httpStatus' => $this->httpStatus,
            'apiCode' => $this->apiCode,
            'responseBody' => $this->responseBody === null ? null : '<redacted>',
        ];
    }

    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly ?int $apiCode = null,
        public readonly ?string $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
