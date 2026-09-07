<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Webhook;

use Luminal\OpenApiSdk\RsaSignatures;
use Luminal\OpenApiSdk\Model\CardOpenStatusWebhook;
use Luminal\OpenApiSdk\Model\CardStatusWebhook;
use Luminal\OpenApiSdk\Model\RechargeCardTransferStatusWebhook;
use Luminal\OpenApiSdk\Model\SharedAccountOpenStatusWebhook;
use Luminal\OpenApiSdk\Model\TransactionWebhook;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;
use Luminal\OpenApiSdk\Webhook\WebhookEventType;
use Luminal\OpenApiSdk\Webhook\WebhookVerificationException;
use Luminal\OpenApiSdk\Webhook\WebhookVerifier;

final class WebhookVerifierTest extends EndpointTestCase
{
    private const SANDBOX_WEBHOOK_PUBLIC_KEY_PEM = <<<'KEY'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxD4ii20YBI2JwPIfXaXy
PHA1aL1p545F9zvQRPUXvvepkkgI4Pxh1eS4qTx17OVstFGamh5mjeI+3Lu2du7
DNIwM1p2Dl12OkEH2RtGi4H4jcRf9wNJCXCMt6b3bwEW3osYqW8HDusDMYYjBvA
aWw9Vv2/uvVNDz39ntVca7T54Hjml5tsP/fpYERO3vvB9yAlmfIISOD5Ggf335i
dL6m/ALAs1bAdiGxKIoHb6sMb76Iw18clbDlLDaPqyeUXNR9YKZuZvts+AOlp9f
IMSJAuW8HmSm03fxBMyfKPsFu8AoeS74yM1QdEkOrgFB+dNoREyV0p0YXPZ3vDl
dZnUIXQIDAQAB
-----END PUBLIC KEY-----
KEY;

    private string $privateKeyPem;
    private string $publicKeyPem;

    protected function setUp(): void
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        self::assertNotFalse($key);
        $privateKeyPem = null;
        self::assertTrue(openssl_pkey_export($key, $privateKeyPem));
        self::assertIsString($privateKeyPem);
        $this->privateKeyPem = $privateKeyPem;
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);
        self::assertIsString($details['key'] ?? null);
        $this->publicKeyPem = $details['key'];
    }

    public function testLoadsSandboxWebhookPublicKey(): void
    {
        $key = openssl_pkey_get_public(self::SANDBOX_WEBHOOK_PUBLIC_KEY_PEM);
        self::assertNotFalse($key);
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);
        self::assertSame(2048, $details['bits'] ?? null);
    }

    /** @dataProvider supportedEvents */
    public function testParsesEverySupportedEvent(string $eventType): void
    {
        $rawBody = '{"z":1,"message":"旅行","nested":{"b":2,"a":1}}';
        $signature = RsaSignatures::sign($rawBody, $this->privateKeyPem);

        $event = WebhookVerifier::parse($eventType, 'event-1', $rawBody, $signature, $this->publicKeyPem);

        self::assertSame($eventType, $event->type);
        self::assertSame('event-1', $event->eventId);
        self::assertSame($rawBody, $event->rawBody);
        self::assertInstanceOf(match ($eventType) {
            WebhookEventType::CARD_TRANSACTIONS,
            WebhookEventType::CARD_SETTLE_STATUS,
            WebhookEventType::SHARE_ACCOUNT_FUND_TRANSACTIONS => TransactionWebhook::class,
            WebhookEventType::CARD_RECHARGE_STATUS,
            WebhookEventType::CARD_WITHDRAW_STATUS,
            WebhookEventType::CARD_LIMIT_STATUS => RechargeCardTransferStatusWebhook::class,
            WebhookEventType::CARD_STATUS => CardStatusWebhook::class,
            WebhookEventType::CARD_OPEN_STATUS => CardOpenStatusWebhook::class,
            WebhookEventType::SHARED_ACCOUNT_OPEN_STATUS => SharedAccountOpenStatusWebhook::class,
        }, $event->payload);
    }

    public static function supportedEvents(): array
    {
        return array_map(static fn (string $event): array => [$event], WebhookEventType::all());
    }

    public function testVerifiesExactRawBody(): void
    {
        $rawBody = '{"memberCardId":5,"cardStatus":"ACTIVE"}';
        $signature = RsaSignatures::sign($rawBody, $this->privateKeyPem);

        self::assertTrue(WebhookVerifier::verify($rawBody, $signature, $this->publicKeyPem));
        self::assertFalse(WebhookVerifier::verify($rawBody . ' ', $signature, $this->publicKeyPem));
    }

    public function testRejectsTamperedBody(): void
    {
        $rawBody = '{"amount":100}';
        $signature = RsaSignatures::sign($rawBody, $this->privateKeyPem);

        $this->expectException(WebhookVerificationException::class);
        WebhookVerifier::parse(
            WebhookEventType::CARD_TRANSACTIONS,
            'event-2',
            '{"amount":101}',
            $signature,
            $this->publicKeyPem,
        );
    }

    public function testRejectsInvalidSignature(): void
    {
        $this->expectException(WebhookVerificationException::class);
        WebhookVerifier::parse(
            WebhookEventType::CARD_STATUS,
            'event-3',
            '{}',
            'not-base64',
            $this->publicKeyPem,
        );
    }

    public function testRejectsUnknownEvent(): void
    {
        $rawBody = '{}';
        $signature = RsaSignatures::sign($rawBody, $this->privateKeyPem);

        $this->expectException(WebhookVerificationException::class);
        WebhookVerifier::parse('UNKNOWN_EVENT', 'event-4', $rawBody, $signature, $this->publicKeyPem);
    }

    public function testRejectsInvalidJsonAfterSignatureVerification(): void
    {
        $rawBody = '{invalid';
        $signature = RsaSignatures::sign($rawBody, $this->privateKeyPem);

        $this->expectException(WebhookVerificationException::class);
        WebhookVerifier::parse(
            WebhookEventType::CARD_OPEN_STATUS,
            'event-5',
            $rawBody,
            $signature,
            $this->publicKeyPem,
        );
    }

    public function testHydratesLongAndDateTimeFields(): void
    {
        $rawBody = '{"sharedAccountTransactionId":"T1","tradeAmount":100,"tradeTime":"2026-01-02T03:04:05"}';
        $signature = RsaSignatures::sign($rawBody, $this->privateKeyPem);

        $event = WebhookVerifier::parse(
            WebhookEventType::CARD_TRANSACTIONS,
            'event-6',
            $rawBody,
            $signature,
            $this->publicKeyPem,
        );

        self::assertInstanceOf(TransactionWebhook::class, $event->payload);
        self::assertSame('T1', $event->payload->sharedAccountTransactionId);
        self::assertSame(100, $event->payload->tradeAmount);
        self::assertSame('2026-01-02T03:04:05', $event->payload->tradeTime?->format('Y-m-d\\TH:i:s'));
    }
    public function testHydratesNumericJsonIntoStringFields(): void
    {
        $payload = CardStatusWebhook::fromArray([
            'memberCardId' => 5,
            'cardStatus' => 'ACTIVE',
            'memberNo' => 6,
        ]);

        self::assertSame('5', $payload->memberCardId);
        self::assertSame('6', $payload->memberNo);
    }

    public function testHydratesRechargeOperationWebhook(): void
    {
        $rawBody = '{"memberCardOperationRecordId":601,"memberCardId":5,"cardType":"RECHARGE","operationType":"RECHARGE","amount":25,"status":"SUCCESS","updateTime":1788171390000}';
        $signature = RsaSignatures::sign($rawBody, $this->privateKeyPem);

        $event = WebhookVerifier::parse(
            WebhookEventType::CARD_RECHARGE_STATUS,
            'event-7',
            $rawBody,
            $signature,
            $this->publicKeyPem,
        );

        self::assertInstanceOf(RechargeCardTransferStatusWebhook::class, $event->payload);
        self::assertSame(601, $event->payload->memberCardOperationRecordId);
        self::assertSame('RECHARGE', $event->payload->operationType);
        self::assertSame('2026', $event->payload->updateTime?->format('Y'));
    }

    public function testHydratesCardSettlementFields(): void
    {
        $rawBody = '{"memberCardTransactionId":"T1","cardType":"RECHARGE","settleStatus":"SETTLED","settleTime":1788171390000}';
        $signature = RsaSignatures::sign($rawBody, $this->privateKeyPem);

        $event = WebhookVerifier::parse(
            WebhookEventType::CARD_SETTLE_STATUS,
            'event-8',
            $rawBody,
            $signature,
            $this->publicKeyPem,
        );

        self::assertInstanceOf(TransactionWebhook::class, $event->payload);
        self::assertSame('T1', $event->payload->memberCardTransactionId);
        self::assertSame('SETTLED', $event->payload->settleStatus);
        self::assertSame('2026', $event->payload->settleTime?->format('Y'));
    }

}
