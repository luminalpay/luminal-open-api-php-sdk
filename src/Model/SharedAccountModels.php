<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;


use DateTimeImmutable;

/** Shared-account request and response models. */
final class CreateSharedAccountRequest extends JsonModel
{
    public function __construct(
        public readonly int|string|null $cardBinId = null,
        public readonly int|float|string|null $rechargeAmount = null,
        public readonly ?string $accountName = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->cardBinId, 'cardBinId');
        $this->requirePositiveDecimal($this->rechargeAmount, 'rechargeAmount');
        $this->requireNonBlank($this->accountName, 'accountName');
    }
}

final class SharedAccountIdResponse extends JsonModel
{
    public function __construct(public readonly int|string|null $memberSharedAccountId = null)
    {
    }
}

final class SharedAccountPageRequest extends JsonModel
{
    public function __construct(
        public readonly ?int $pageNo = null,
        public readonly ?int $pageSize = null,
        public readonly int|string|null $memberSharedAccountId = null,
        public readonly ?string $accountName = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
        $this->validateOptionalId($this->memberSharedAccountId, 'memberSharedAccountId');
    }
}

/** Shared-account information and available server operations. */
final class SharedAccountResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberSharedAccountId = null,
        public readonly ?string $accountName = null,
        public readonly ?string $status = null,
        public readonly ?DateTimeImmutable $createTime = null,
        public readonly ?string $cardBin = null,
        public readonly int|string|null $cardBinId = null,
        public readonly ?string $cardOrganization = null,
        public readonly int|float|string|null $balance = null,
        public readonly int|string|null $issuedCardCount = null,
        public readonly int|string|null $remainingApplyCardCount = null,
        public readonly ?int $canRecharge = null,
        public readonly ?int $canApply = null,
        public readonly int|float|string|null $applyHandlingFee = null,
        public readonly ?int $canReduce = null,
        public readonly ?int $canFreeze = null,
        public readonly ?int $canUnfreeze = null,
        public readonly ?int $canCancel = null,
    ) {
    }
}

final class SharedAccountBalanceRequest extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberSharedAccountId = null,
        public readonly int|float|string|null $amount = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->memberSharedAccountId, 'memberSharedAccountId');
        $this->requirePositiveDecimal($this->amount, 'amount');
    }
}

final class SharedAccountTransactionIdResponse extends JsonModel
{
    public function __construct(public readonly ?string $sharedAccountTransactionId = null)
    {
    }
}

final class SharedAccountGetRequest extends JsonModel
{
    public function __construct(public readonly int|string|null $memberSharedAccountId = null)
    {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->memberSharedAccountId, 'memberSharedAccountId');
    }
}

final class SharedAccountTransactionsRequest extends JsonModel
{
    /** @param list<DateTimeImmutable>|null $tradeTime */
    public function __construct(
        public readonly ?int $pageNo = null,
        public readonly ?int $pageSize = null,
        public readonly int|string|null $sharedAccountTransactionId = null,
        public readonly int|string|null $memberSharedAccountId = null,
        public readonly int|string|null $memberCardId = null,
        public readonly ?string $type = null,
        public readonly ?array $tradeTime = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        $data['tradeTime'] = self::dateTimeList($data['tradeTime'] ?? null);
        return parent::fromArray($data);
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
        $this->validateOptionalId($this->sharedAccountTransactionId, 'sharedAccountTransactionId');
        $this->validateOptionalId($this->memberSharedAccountId, 'memberSharedAccountId');
        $this->validateOptionalId($this->memberCardId, 'memberCardId');
        $this->validateDateTimeRange($this->tradeTime, 'tradeTime');
    }
}

/** Shared-account transaction information returned by the API. */
final class SharedAccountTransactionResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $sharedAccountTransactionId = null,
        public readonly int|string|null $memberSharedAccountId = null,
        public readonly int|string|null $memberCardId = null,
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
