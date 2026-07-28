<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;


use DateTimeImmutable;

/** Wallet-transaction list filters. */
final class WalletTransactionRequest extends JsonModel
{
    /** @param list<DateTimeImmutable>|null $createTime */
    public function __construct(
        public readonly ?int $pageNo = null,
        public readonly ?int $pageSize = null,
        public readonly ?int $type = null,
        public readonly ?array $createTime = null,
        public readonly int|string|null $memberCardId = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        $data['createTime'] = self::dateTimeList($data['createTime'] ?? null);
        return parent::fromArray($data);
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
        $this->validateOptionalId($this->memberCardId, 'memberCardId');
        $this->validateDateTimeRange($this->createTime, 'createTime');
    }
}

/** Wallet-transaction information returned by the API. */
final class WalletTransactionResponse extends JsonModel
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
