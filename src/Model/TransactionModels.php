<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;


use DateTimeImmutable;

/** Wallet-transaction list filters. */
final class WalletTransactionRequest extends JsonModel
{
    public readonly ?int $pageNo;
    public readonly ?int $pageSize;
    public readonly ?int $type;
    public readonly ?string $orderNo;
    /** @var list<DateTimeImmutable>|null */
    public readonly ?array $createTime;
    public readonly int|string|null $memberCardId;

    public function __construct(
        ?int $pageNo = null,
        ?int $pageSize = null,
        ?int $type = null,
        string|array|null $orderNo = null,
        array|int|string|null $createTime = null,
        int|string|null $memberCardId = null,
    ) {
        // Preserve the original positional order: (pageNo, pageSize, type, createTime, memberCardId).
        if (func_num_args() < 6 && is_array($orderNo)) {
            $legacyCreateTime = $orderNo;
            $legacyMemberCardId = $createTime;
            $orderNo = null;
            $createTime = $legacyCreateTime;
            $memberCardId = $legacyMemberCardId;
        } elseif (func_num_args() < 6 && $orderNo === null && $createTime !== null && !is_array($createTime)) {
            // Also preserve calls that passed a null createTime before a member-card ID.
            $memberCardId = $createTime;
            $createTime = null;
        }
        $this->pageNo = $pageNo;
        $this->pageSize = $pageSize;
        $this->type = $type;
        $this->orderNo = is_string($orderNo) ? $orderNo : null;
        $this->createTime = is_array($createTime) ? $createTime : null;
        $this->memberCardId = $memberCardId;
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
