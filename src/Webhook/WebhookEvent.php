<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Webhook;

use Luminal\OpenApiSdk\Model\JsonModel;

/** Verified webhook metadata and decoded payload. */
final class WebhookEvent
{
    /** Prevents signed body and signature data from being dumped accidentally. */
    public function __debugInfo(): array
    {
        return [
            'type' => $this->type,
            'eventId' => $this->eventId,
            'rawBody' => '<redacted>',
            'signature' => '<redacted>',
            'payload' => $this->payload,
        ];
    }

    public function __construct(
        public readonly string $type,
        public readonly string $eventId,
        public readonly string $rawBody,
        public readonly string $signature,
        public readonly JsonModel $payload,
    ) {
    }
}
