<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Webhook;

use Luminal\OpenApiSdk\Model\CardOpenStatusWebhook;
use Luminal\OpenApiSdk\Model\CardStatusWebhook;
use Luminal\OpenApiSdk\Model\JsonModel;
use Luminal\OpenApiSdk\Model\RechargeCardTransferStatusWebhook;
use Luminal\OpenApiSdk\Model\SharedAccountOpenStatusWebhook;
use Luminal\OpenApiSdk\Model\TransactionWebhook;

/** Supported values of the webhook event header. */
final class WebhookEventType
{
    public const CARD_TRANSACTIONS = 'CARD_TRANSACTIONS';
    public const CARD_SETTLE_STATUS = 'CARD_SETTLE_STATUS';
    public const CARD_STATUS = 'CARD_STATUS';
    public const CARD_OPEN_STATUS = 'CARD_OPEN_STATUS';
    public const CARD_RECHARGE_STATUS = 'CARD_RECHARGE_STATUS';
    public const CARD_WITHDRAW_STATUS = 'CARD_WITHDRAW_STATUS';
    public const CARD_LIMIT_STATUS = 'CARD_LIMIT_STATUS';
    public const SHARED_ACCOUNT_OPEN_STATUS = 'SHARED_ACCOUNT_OPEN_STATUS';
    public const SHARE_ACCOUNT_FUND_TRANSACTIONS = 'SHARE_ACCOUNT_FUND_TRANSACTIONS';

    private function __construct()
    {
    }

    public static function all(): array
    {
        return [
            self::CARD_TRANSACTIONS,
            self::CARD_SETTLE_STATUS,
            self::CARD_STATUS,
            self::CARD_OPEN_STATUS,
            self::CARD_RECHARGE_STATUS,
            self::CARD_WITHDRAW_STATUS,
            self::CARD_LIMIT_STATUS,
            self::SHARED_ACCOUNT_OPEN_STATUS,
            self::SHARE_ACCOUNT_FUND_TRANSACTIONS,
        ];
    }

    /** @return class-string<JsonModel> */
    public static function payloadClass(string $event): string
    {
        return match ($event) {
            self::CARD_TRANSACTIONS, self::CARD_SETTLE_STATUS, self::SHARE_ACCOUNT_FUND_TRANSACTIONS => TransactionWebhook::class,
            self::CARD_RECHARGE_STATUS, self::CARD_WITHDRAW_STATUS, self::CARD_LIMIT_STATUS => RechargeCardTransferStatusWebhook::class,
            self::CARD_STATUS => CardStatusWebhook::class,
            self::CARD_OPEN_STATUS => CardOpenStatusWebhook::class,
            self::SHARED_ACCOUNT_OPEN_STATUS => SharedAccountOpenStatusWebhook::class,
            default => throw new \InvalidArgumentException('Unsupported webhook event: ' . $event),
        };
    }
}
