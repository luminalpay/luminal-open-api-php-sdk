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

final class TransactionWebhook extends JsonModel
{
    public function __construct(
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
