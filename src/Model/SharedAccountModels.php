<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;


use DateTimeImmutable;

/** Shared-account request and response models. */
final class CreateSharedAccountRequest extends JsonModel
{
    public readonly int|string|null $cardBinId;
    public readonly int|string|null $cardPoolId;
    public readonly int|float|string|null $rechargeAmount;
    public readonly ?string $accountName;

    public function __construct(
        int|string|null $cardBinId = null,
        mixed $cardPoolId = null,
        int|float|string|null $rechargeAmount = null,
        ?string $accountName = null,
    ) {
        // Preserve the previous positional order: (cardBinId, rechargeAmount, accountName).
        if ($accountName === null && $cardPoolId !== null
            && is_string($rechargeAmount) && !is_numeric($rechargeAmount)) {
            $legacyRechargeAmount = $cardPoolId;
            $legacyAccountName = $rechargeAmount;
            $cardPoolId = null;
            $rechargeAmount = $legacyRechargeAmount;
            $accountName = $legacyAccountName;
        }
        $this->cardBinId = $cardBinId;
        $this->cardPoolId = $cardPoolId;
        $this->rechargeAmount = $rechargeAmount;
        $this->accountName = $accountName;
    }

    protected function validateModel(): void
    {
        if ($this->cardBinId === null && $this->cardPoolId === null) {
            throw new \InvalidArgumentException('At least one of cardBinId or cardPoolId must be provided.');
        }
        $this->validateOptionalId($this->cardBinId, 'cardBinId');
        $this->validateOptionalId($this->cardPoolId, 'cardPoolId');
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
        public readonly int|string|null $cardPoolId = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
        $this->validateOptionalId($this->memberSharedAccountId, 'memberSharedAccountId');
        $this->validateOptionalId($this->cardPoolId, 'cardPoolId');
    }
}

/** Shared-account information and available server operations. */
final class SharedAccountResponse extends JsonModel
{
    public readonly int|string|null $memberSharedAccountId;
    public readonly ?string $accountName;
    public readonly ?string $status;
    public readonly ?DateTimeImmutable $createTime;
    public readonly ?string $cardBin;
    public readonly int|string|null $cardBinId;
    public readonly int|string|null $cardPoolId;
    public readonly ?string $poolName;
    public readonly ?string $cardOrganization;
    public readonly int|float|string|null $balance;
    public readonly int|string|null $issuedCardCount;
    public readonly int|string|null $remainingApplyCardCount;
    public readonly ?int $canRecharge;
    public readonly ?int $canApply;
    public readonly int|float|string|null $applyHandlingFee;
    public readonly ?int $canReduce;
    public readonly ?int $canFreeze;
    public readonly ?int $canUnfreeze;
    public readonly ?int $canCancel;

    public function __construct(
        int|string|null $memberSharedAccountId = null,
        ?string $accountName = null,
        ?string $status = null,
        ?DateTimeImmutable $createTime = null,
        ?string $cardBin = null,
        int|string|null $cardBinId = null,
        mixed $cardPoolId = null,
        mixed $poolName = null,
        mixed $cardOrganization = null,
        mixed $balance = null,
        mixed $issuedCardCount = null,
        mixed $remainingApplyCardCount = null,
        mixed $canRecharge = null,
        mixed $canApply = null,
        mixed $applyHandlingFee = null,
        mixed $canReduce = null,
        mixed $canFreeze = null,
        mixed $canUnfreeze = null,
        mixed $canCancel = null,
    ) {
        // Preserve the previous response constructor shape without pool fields.
        if (func_num_args() === 17
            || (is_string($cardPoolId) && !is_numeric($cardPoolId))) {
            $legacyCardOrganization = $cardPoolId;
            $legacyBalance = $poolName;
            $legacyIssuedCardCount = $cardOrganization;
            $legacyRemainingApplyCardCount = $balance;
            $legacyCanRecharge = $issuedCardCount;
            $legacyCanApply = $remainingApplyCardCount;
            $legacyApplyHandlingFee = $canRecharge;
            $legacyCanReduce = $canApply;
            $legacyCanFreeze = $applyHandlingFee;
            $legacyCanUnfreeze = $canReduce;
            $legacyCanCancel = $canFreeze;
            $cardPoolId = null;
            $poolName = null;
            $cardOrganization = $legacyCardOrganization;
            $balance = $legacyBalance;
            $issuedCardCount = $legacyIssuedCardCount;
            $remainingApplyCardCount = $legacyRemainingApplyCardCount;
            $canRecharge = $legacyCanRecharge;
            $canApply = $legacyCanApply;
            $applyHandlingFee = $legacyApplyHandlingFee;
            $canReduce = $legacyCanReduce;
            $canFreeze = $legacyCanFreeze;
            $canUnfreeze = $legacyCanUnfreeze;
            $canCancel = $legacyCanCancel;
        }
        $this->memberSharedAccountId = $memberSharedAccountId;
        $this->accountName = $accountName;
        $this->status = $status;
        $this->createTime = $createTime;
        $this->cardBin = $cardBin;
        $this->cardBinId = $cardBinId;
        $this->cardPoolId = $cardPoolId;
        $this->poolName = $poolName;
        $this->cardOrganization = $cardOrganization;
        $this->balance = $balance;
        $this->issuedCardCount = $issuedCardCount;
        $this->remainingApplyCardCount = $remainingApplyCardCount;
        $this->canRecharge = $canRecharge;
        $this->canApply = $canApply;
        $this->applyHandlingFee = $applyHandlingFee;
        $this->canReduce = $canReduce;
        $this->canFreeze = $canFreeze;
        $this->canUnfreeze = $canUnfreeze;
        $this->canCancel = $canCancel;
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

/** Parameters for cancelling a shared account after server-side verification. */
final class SharedAccountCancelRequest extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberSharedAccountId = null,
        public readonly ?string $remark = null,
        public readonly ?string $verifyCode = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->memberSharedAccountId, 'memberSharedAccountId');
        $this->requireNonBlank($this->verifyCode, 'verifyCode');
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
        public readonly ?string $settleStatus = null,
        public readonly ?DateTimeImmutable $settleTime = null,
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
