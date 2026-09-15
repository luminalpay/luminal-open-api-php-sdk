<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;


use DateTimeImmutable;

/** Payload models for supported webhook events. */
final class CardOpenResult extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberCardId = null,
        public readonly ?string $cardStatus = null,
        public readonly ?string $message = null,
    ) {
    }
}

final class CardOpenStatusWebhook extends JsonModel
{
    /** @param list<CardOpenResult>|null $list */
    public function __construct(
        public readonly int|string|null $cardApplyTaskId = null,
        public readonly int|string|null $memberId = null,
        public readonly ?array $list = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        $list = $data['list'] ?? null;
        if ($list !== null) {
            if (!is_array($list)) {
                throw new \UnexpectedValueException('Card-open webhook list must be an array.');
            }
            $data['list'] = array_map(
                static fn (mixed $item): CardOpenResult => is_array($item)
                    ? CardOpenResult::fromArray($item)
                    : throw new \UnexpectedValueException('Card-open webhook list items must be objects.'),
                $list,
            );
        }
        return parent::fromArray($data);
    }
}

final class CardStatusWebhook extends JsonModel
{
    public function __construct(
        public readonly ?string $memberCardId = null,
        public readonly ?string $cardStatus = null,
        public readonly ?string $memberNo = null,
        public readonly ?string $updateTime = null,
    ) {
    }
}

final class SharedAccountOpenStatusWebhook extends JsonModel
{
    public function __construct(
        public readonly int|string|null $sharedAccountOperationRecordId = null,
        public readonly int|string|null $memberNo = null,
        public readonly int|string|null $memberSharedAccountId = null,
        public readonly ?string $status = null,
    ) {
    }
}

/** Payload for the WALLET_TRANSACTIONS webhook event. */
final class WalletTransactionWebhook extends JsonModel
{
    public function __construct(
        public readonly int|string|null $transactionNo = null,
        public readonly int|string|null $memberNo = null,
        public readonly int|string|null $walletNo = null,
        public readonly ?string $orderNo = null,
        public readonly ?int $type = null,
        public readonly ?int $direction = null,
        public readonly int|float|string|null $amount = null,
        public readonly int|float|string|null $fee = null,
        public readonly ?string $currency = null,
        public readonly int|float|string|null $beforeBalance = null,
        public readonly int|float|string|null $afterBalance = null,
        public readonly ?int $status = null,
        public readonly ?string $remark = null,
        public readonly ?DateTimeImmutable $createTime = null,
        public readonly int|string|null $memberCardId = null,
        public readonly ?string $cardNumber = null,
    ) {
    }
}

/** Payload for recharge-card funding, withdrawal, and limit-operation webhooks. */
final class RechargeCardTransferStatusWebhook extends JsonModel
{
    public readonly int|string|null $memberCardOperationRecordId;
    public readonly int|string|null $memberCardId;
    public readonly ?string $cardType;
    public readonly ?string $operationType;
    public readonly int|float|string|null $amount;
    public readonly ?string $currencyCode;
    public readonly int|float|string|null $balance;
    public readonly int|float|string|null $totalLimit;
    public readonly int|float|string|null $dailyLimit;
    public readonly int|float|string|null $monthLimit;
    public readonly ?string $status;
    public readonly ?string $message;
    public readonly ?DateTimeImmutable $updateTime;

    public function __construct(
        int|string|null $memberCardOperationRecordId = null,
        int|string|null $memberCardId = null,
        ?string $cardType = null,
        ?string $operationType = null,
        int|float|string|null $amount = null,
        ?string $currencyCode = null,
        int|float|string|null $balance = null,
        mixed $totalLimit = null,
        mixed $dailyLimit = null,
        mixed $monthLimit = null,
        mixed $status = null,
        ?string $message = null,
        ?DateTimeImmutable $updateTime = null,
    ) {
        // Preserve the original positional constructor shape without limit fields.
        if ($monthLimit instanceof DateTimeImmutable && $status === null && $message === null && $updateTime === null) {
            $legacyStatus = $totalLimit;
            $legacyMessage = $dailyLimit;
            $legacyUpdateTime = $monthLimit;
            $totalLimit = null;
            $dailyLimit = null;
            $monthLimit = null;
            $status = is_string($legacyStatus) ? $legacyStatus : null;
            $message = is_string($legacyMessage) ? $legacyMessage : null;
            $updateTime = $legacyUpdateTime;
        // Preserve the intermediate positional shape with totalLimit but without the two card-specific limits.
        } elseif ($status instanceof DateTimeImmutable && $message === null && $updateTime === null) {
            $legacyTotalLimit = $totalLimit;
            $legacyStatus = $dailyLimit;
            $legacyMessage = $monthLimit;
            $legacyUpdateTime = $status;
            $totalLimit = $legacyTotalLimit;
            $dailyLimit = null;
            $monthLimit = null;
            $status = is_string($legacyStatus) ? $legacyStatus : null;
            $message = is_string($legacyMessage) ? $legacyMessage : null;
            $updateTime = $legacyUpdateTime;
        }
        $this->memberCardOperationRecordId = $memberCardOperationRecordId;
        $this->memberCardId = $memberCardId;
        $this->cardType = $cardType;
        $this->operationType = $operationType;
        $this->amount = $amount;
        $this->currencyCode = $currencyCode;
        $this->balance = $balance;
        $this->totalLimit = $totalLimit;
        $this->dailyLimit = $dailyLimit;
        $this->monthLimit = $monthLimit;
        $this->status = is_string($status) ? $status : null;
        $this->message = $message;
        $this->updateTime = $updateTime;
    }
}

final class TransactionWebhook extends JsonModel
{
    public function __construct(
        public readonly ?string $memberCardTransactionId = null,
        public readonly ?string $cardType = null,
        public readonly ?string $sharedAccountTransactionId = null,
        public readonly ?string $memberSharedAccountId = null,
        public readonly ?string $memberCardId = null,
        public readonly ?string $maskCardNo = null,
        public readonly ?string $orderNo = null,
        public readonly ?string $originalOrderNo = null,
        public readonly int|float|string|null $accountBalance = null,
        public readonly int|float|string|null $balance = null,
        public readonly int|float|string|null $beforeBalance = null,
        public readonly int|float|string|null $beforeAccountBalance = null,
        public readonly ?string $status = null,
        public readonly ?string $settleStatus = null,
        public readonly ?DateTimeImmutable $settleTime = null,
        public readonly ?string $type = null,
        public readonly ?string $tradeType = null,
        public readonly ?string $direction = null,
        public readonly ?string $description = null,
        public readonly int|float|string|null $tradeActualAmount = null,
        public readonly ?string $currencyCode = null,
        public readonly ?string $tradeCurrencyCode = null,
        public readonly int|float|string|null $tradeAmount = null,
        public readonly ?DateTimeImmutable $tradeTime = null,
        public readonly ?string $merchantName = null,
        public readonly ?string $merchantId = null,
        public readonly ?string $merchantCountry = null,
        public readonly ?string $cardBin = null,
        public readonly ?string $merchantCity = null,
        public readonly ?string $merchantMcc = null,
        public readonly ?string $processStatus = null,
    ) {
    }
}
