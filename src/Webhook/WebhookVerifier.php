<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Webhook;

use JsonException;
use Luminal\OpenApiSdk\CanonicalJson;
use Luminal\OpenApiSdk\Model\JsonModel;
use Luminal\OpenApiSdk\RsaSignatures;

/** Verifies exact webhook bytes before decoding supported event payloads. */
final class WebhookVerifier
{
    private function __construct()
    {
    }

    /** Returns true when the Base64 SHA256withRSA signature matches the exact raw body. */
    public static function verify(string $rawBody, string $signature, string $publicKeyPem): bool
    {
        return RsaSignatures::verify($rawBody, $signature, $publicKeyPem);
    }

    /**
     * Verifies headers and exact raw bytes, then decodes the event-specific payload.
     *
     * The body must be the original request bytes. Parse and re-encode only after verification.
     */
    public static function parse(
        string $event,
        string $eventId,
        string $rawBody,
        string $signature,
        string $publicKeyPem,
    ): WebhookEvent {
        self::requireHeader($event, 'event');
        self::requireHeader($eventId, 'event_id');
        self::requireHeader($signature, 'sign');
        if (!self::verify($rawBody, $signature, $publicKeyPem)) {
            throw new WebhookVerificationException('Webhook signature is invalid.');
        }
        if (!in_array($event, WebhookEventType::all(), true)) {
            throw new WebhookVerificationException('Unsupported webhook event: ' . $event);
        }
        try {
            $payload = json_decode(
                $rawBody,
                true,
                512,
                JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING,
            );
            $payload = CanonicalJson::normalizeDecoded($payload);
        } catch (JsonException $exception) {
            throw new WebhookVerificationException('Webhook payload is invalid JSON.', 0, $exception);
        }
        if (!is_array($payload) || array_is_list($payload)) {
            throw new WebhookVerificationException('Webhook payload must be a JSON object.');
        }
        $payloadClass = WebhookEventType::payloadClass($event);
        try {
            $typedPayload = $payloadClass::fromArray($payload);
        } catch (\Throwable $exception) {
            throw new WebhookVerificationException('Webhook payload has an invalid shape.', 0, $exception);
        }
        return new WebhookEvent($event, $eventId, $rawBody, $signature, $typedPayload);
    }

    private static function requireHeader(string $value, string $name): void
    {
        if (trim($value) === '') {
            throw new WebhookVerificationException("Webhook {$name} header must not be blank.");
        }
    }
}
