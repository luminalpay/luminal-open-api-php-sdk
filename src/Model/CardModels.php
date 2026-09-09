<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;


use DateTimeImmutable;

/** Card request and response models. */
final class CardBinsRequest extends JsonModel
{
    public readonly ?int $pageNo;
    public readonly ?int $pageSize;
    public readonly int|string|null $cardPoolId;
    public readonly ?string $cardType;
    public readonly ?string $cardOrganization;
    public readonly ?string $cardBin;
    public readonly ?string $areaCode;

    public function __construct(
        ?int $pageNo = null,
        ?int $pageSize = null,
        int|string|null $cardPoolId = null,
        ?string $cardType = null,
        ?string $cardOrganization = null,
        ?string $cardBin = null,
        ?string $areaCode = null,
    ) {
        // Preserve the previous positional order (..., cardType, cardOrganization, cardBin, areaCode).
        if (is_string($cardPoolId) && !is_numeric($cardPoolId)) {
            $legacyCardType = $cardPoolId;
            $legacyCardOrganization = $cardType;
            $legacyCardBin = $cardOrganization;
            $legacyAreaCode = $cardBin;
            $cardPoolId = null;
            $cardType = $legacyCardType;
            $cardOrganization = $legacyCardOrganization;
            $cardBin = $legacyCardBin;
            $areaCode = $legacyAreaCode;
        }
        $this->pageNo = $pageNo;
        $this->pageSize = $pageSize;
        $this->cardPoolId = $cardPoolId;
        $this->cardType = $cardType;
        $this->cardOrganization = $cardOrganization;
        $this->cardBin = $cardBin;
        $this->areaCode = $areaCode;
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
        $this->validateOptionalId($this->cardPoolId, 'cardPoolId');
    }
}

final class CardBinResponse extends JsonModel
{
    public readonly int|string|null $cardBinId;
    public readonly int|string|null $cardPoolId;
    public readonly ?string $poolName;
    public readonly ?string $cardType;
    public readonly ?string $currencyCode;
    public readonly ?string $areaCode;
    public readonly ?string $cardBin;
    public readonly ?string $cardOrganization;
    public readonly ?string $applicableScenarios;
    public readonly ?int $customCardholder;
    public readonly ?int $canLimit;

    public function __construct(
        int|string|null $cardBinId = null,
        mixed $cardPoolId = null,
        mixed $poolName = null,
        mixed $cardType = null,
        mixed $currencyCode = null,
        mixed $areaCode = null,
        mixed $cardBin = null,
        mixed $cardOrganization = null,
        mixed $applicableScenarios = null,
        mixed $customCardholder = null,
        mixed $canLimit = null,
    ) {
        // Preserve the previous response constructor shape without pool fields.
        if (is_string($cardPoolId) && !is_numeric($cardPoolId)) {
            $legacyCardType = $cardPoolId;
            $legacyCurrencyCode = $poolName;
            $legacyAreaCode = $cardType;
            $legacyCardBin = $currencyCode;
            $legacyCardOrganization = $areaCode;
            $legacyApplicableScenarios = $cardBin;
            $legacyCustomCardholder = $cardOrganization;
            $legacyCanLimit = $applicableScenarios;
            $cardPoolId = null;
            $poolName = null;
            $cardType = $legacyCardType;
            $currencyCode = $legacyCurrencyCode;
            $areaCode = $legacyAreaCode;
            $cardBin = $legacyCardBin;
            $cardOrganization = $legacyCardOrganization;
            $applicableScenarios = $legacyApplicableScenarios;
            $customCardholder = $legacyCustomCardholder;
            $canLimit = $legacyCanLimit;
        }
        $this->cardBinId = $cardBinId;
        $this->cardPoolId = $cardPoolId;
        $this->poolName = $poolName;
        $this->cardType = $cardType;
        $this->currencyCode = $currencyCode;
        $this->areaCode = $areaCode;
        $this->cardBin = $cardBin;
        $this->cardOrganization = $cardOrganization;
        $this->applicableScenarios = $applicableScenarios;
        $this->customCardholder = $customCardholder;
        $this->canLimit = $canLimit;
    }
}

/** Card-issuance request. The exact serialized body is signed before submission. */
final class IssueCardRequest extends JsonModel
{
    public readonly ?int $applyCount;
    public readonly int|string|null $cardBinId;
    public readonly int|string|null $cardGroupId;
    public readonly ?string $cardName;
    public readonly ?string $cardType;
    public readonly int|string|null $memberSharedAccountId;
    public readonly int|float|string|null $dailyLimit;
    public readonly int|float|string|null $monthLimit;
    public readonly int|float|string|null $rechargeAmount;
    public readonly int|string|null $cardHolderId;

    public function __construct(
        ?int $applyCount = null,
        int|string|null $cardBinId = null,
        int|string|null $cardGroupId = null,
        ?string $cardName = null,
        ?string $cardType = null,
        int|string|null $memberSharedAccountId = null,
        int|float|string|null $dailyLimit = null,
        int|float|string|null $monthLimit = null,
        int|float|string|null $rechargeAmount = null,
        int|string|null $cardHolderId = null,
    ) {
        // Preserve the original eight-argument PHP constructor order:
        // (..., memberSharedAccountId, monthLimit, rechargeAmount).
        if (func_num_args() === 8 && $rechargeAmount === null && $cardHolderId === null) {
            $rechargeAmount = $monthLimit;
            $monthLimit = $dailyLimit;
            $dailyLimit = null;
        }
        $this->applyCount = $applyCount;
        $this->cardBinId = $cardBinId;
        $this->cardGroupId = $cardGroupId;
        $this->cardName = $cardName;
        $this->cardType = $cardType;
        $this->memberSharedAccountId = $memberSharedAccountId;
        $this->dailyLimit = $dailyLimit;
        $this->monthLimit = $monthLimit;
        $this->rechargeAmount = $rechargeAmount;
        $this->cardHolderId = $cardHolderId;
    }

    protected function validateModel(): void
    {
        if ($this->applyCount !== null && $this->applyCount < 1) {
            throw new \InvalidArgumentException('applyCount must be greater than zero.');
        }
        $this->requirePositiveId($this->cardBinId, 'cardBinId');
        $this->validateOptionalId($this->cardGroupId, 'cardGroupId');
        if ($this->cardType !== 'RECHARGE') {
            $this->requirePositiveId($this->memberSharedAccountId, 'memberSharedAccountId');
        } else {
            $this->validateOptionalId($this->memberSharedAccountId, 'memberSharedAccountId');
        }
        $this->validateOptionalPositiveDecimal($this->dailyLimit, 'dailyLimit');
        if ($this->monthLimit !== null) {
            $this->requirePositiveDecimal($this->monthLimit, 'monthLimit');
        }
        $this->requirePositiveDecimal($this->rechargeAmount, 'rechargeAmount');
        $this->validateOptionalId($this->cardHolderId, 'cardHolderId');
    }
}

/** Parameters for funding a recharge card. */
final class MemberCardRechargeRequest extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberCardId = null,
        public readonly int|float|string|null $amount = null,
        public readonly ?string $remark = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->memberCardId, 'memberCardId');
        $this->requirePositiveDecimal($this->amount, 'amount');
    }
}

/** Parameters for withdrawing funds from a recharge card. */
final class MemberCardWithdrawRequest extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberCardId = null,
        public readonly int|float|string|null $amount = null,
        public readonly ?string $remark = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->memberCardId, 'memberCardId');
        $this->requirePositiveDecimal($this->amount, 'amount');
    }
}

/** Filters recharge-card operation records. */
final class RechargeCardOperationRecordRequest extends JsonModel
{
    public readonly ?int $pageNo;
    public readonly ?int $pageSize;
    public readonly int|string|null $memberCardOperationRecordId;
    public readonly int|string|null $memberCardId;

    public function __construct(
        int|string|null $pageNo = null,
        ?int $pageSize = null,
        int|string|null $memberCardOperationRecordId = null,
        int|string|null $memberCardId = null,
    ) {
        // Java exposes a one-argument convenience constructor for an operation ID.
        if (func_num_args() === 1 && $pageNo !== null) {
            $memberCardOperationRecordId = $pageNo;
            $pageNo = 1;
            $pageSize = 10;
        }
        if ($pageNo !== null && !is_int($pageNo)) {
            throw new \InvalidArgumentException('pageNo must be an integer.');
        }
        $this->pageNo = $pageNo;
        $this->pageSize = $pageSize;
        $this->memberCardOperationRecordId = $memberCardOperationRecordId;
        $this->memberCardId = $memberCardId;
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
        $this->validateOptionalId($this->memberCardOperationRecordId, 'memberCardOperationRecordId');
        $this->validateOptionalId($this->memberCardId, 'memberCardId');
        if ($this->memberCardOperationRecordId === null && $this->memberCardId === null) {
            throw new \InvalidArgumentException(
                'memberCardOperationRecordId and memberCardId cannot both be null.',
            );
        }
    }
}

/** Result of a recharge-card funding, withdrawal, or limit-modification operation. */
final class RechargeCardOperationRecordResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberCardOperationRecordId = null,
        public readonly int|string|null $memberCardId = null,
        public readonly ?string $cardType = null,
        public readonly ?string $operationType = null,
        public readonly int|float|string|null $amount = null,
        public readonly ?string $currencyCode = null,
        public readonly int|float|string|null $balance = null,
        public readonly ?string $status = null,
        public readonly ?string $message = null,
        public readonly int|string|null $createTime = null,
        public readonly int|string|null $updateTime = null,
    ) {
    }
}

final class MemberCardPageRequest extends JsonModel
{
    /** @param list<int|string>|null $cardGroups */
    public function __construct(
        public readonly ?int $pageNo = null,
        public readonly ?int $pageSize = null,
        public readonly int|string|null $memberCardId = null,
        public readonly ?string $status = null,
        public readonly ?string $cardBin = null,
        public readonly ?string $cardType = null,
        public readonly ?string $cardKeyWords = null,
        public readonly ?array $cardGroups = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
        $this->validateOptionalId($this->memberCardId, 'memberCardId');
        $this->validateIdentifierList($this->cardGroups, 'cardGroups');
    }

    public function numericJsonFieldTypes(): array
    {
        return [...parent::numericJsonFieldTypes(), 'cardGroups' => 'long'];
    }
}

/** Issued-card information returned by the API. */
final class MemberCardResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberCardId = null,
        public readonly int|string|null $memberSharedAccountId = null,
        public readonly int|string|null $cardBinId = null,
        public readonly ?string $cardBin = null,
        public readonly ?string $cardType = null,
        public readonly ?string $cardNo = null,
        public readonly ?string $cardTailNo = null,
        public readonly ?string $currencyCode = null,
        public readonly int|float|string|null $balance = null,
        public readonly int|float|string|null $totalLimit = null,
        public readonly int|float|string|null $dailyLimit = null,
        public readonly int|float|string|null $monthLimit = null,
        public readonly ?int $canLimit = null,
        public readonly ?string $status = null,
        public readonly ?string $cardholder = null,
        public readonly ?DateTimeImmutable $freezeTime = null,
        public readonly ?DateTimeImmutable $cancelTime = null,
        public readonly ?string $remark = null,
        public readonly int|string|null $cardGroupId = null,
        public readonly ?string $cardGroupName = null,
        public readonly int|string|null $createTime = null,
    ) {
    }
}

final class CardIdRequest extends JsonModel
{
    public function __construct(public readonly int|string|null $memberCardId = null)
    {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->memberCardId, 'memberCardId');
    }
}

/** Sensitive card details. String fields must never be logged. */
final class CardCvvResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberCardId = null,
        public readonly ?string $cardNo = null,
        public readonly ?string $cvv = null,
        public readonly ?string $expiryDate = null,
    ) {
    }

    public function __toString(): string
    {
        return 'CardCvvResponse[memberCardId=' . $this->memberCardId
            . ', cardNo=<redacted>, cvv=<redacted>, expiryDate=<redacted>]';
    }
}

final class CardTransactionsRequest extends JsonModel
{
    /** @param list<int|string>|null $tradeTime */
    public function __construct(
        public readonly ?int $pageNo = null,
        public readonly ?int $pageSize = null,
        public readonly ?string $cardType = null,
        public readonly int|string|null $memberCardId = null,
        public readonly ?array $tradeTime = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
        $this->validateOptionalId($this->memberCardId, 'memberCardId');
        $this->validateInt64Range($this->tradeTime, 'tradeTime');
    }

    public function numericJsonFieldTypes(): array
    {
        return [...parent::numericJsonFieldTypes(), 'tradeTime' => 'long'];
    }
}

final class CardTransactionResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberCardTransactionId = null,
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
        public readonly ?int $direction = null,
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

final class CardLimitResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberCardId = null,
        public readonly int|float|string|null $totalLimit = null,
        public readonly int|float|string|null $balance = null,
    ) {
    }
}

final class CardLimitUpdateRequest extends JsonModel
{
    public readonly int|string|null $memberCardId;
    public readonly ?string $cardType;
    public readonly int|float|string|null $dailyLimit;
    public readonly int|float|string|null $monthLimit;
    public readonly int|float|string|null $totalLimit;

    public function __construct(
        int|string|null $memberCardId = null,
        int|float|string|null $cardType = null,
        int|float|string|null $dailyLimit = null,
        int|float|string|null $monthLimit = null,
        int|float|string|null $totalLimit = null,
    ) {
        $argumentCount = func_num_args();
        $looksLikeLegacyCardType = is_string($cardType) && !is_numeric($cardType);
        if ($argumentCount === 2 && $cardType !== null && $dailyLimit === null && $monthLimit === null && $totalLimit === null) {
            $totalLimit = $cardType;
            $cardType = null;
        } elseif ($argumentCount === 4 && $cardType !== null && !$looksLikeLegacyCardType) {
            // Java's current four-argument constructor omits the legacy cardType field.
            $totalLimit = $monthLimit;
            $monthLimit = $dailyLimit;
            $dailyLimit = $cardType;
            $cardType = null;
        }
        $this->memberCardId = $memberCardId;
        $this->cardType = is_string($cardType) ? $cardType : null;
        $this->dailyLimit = $dailyLimit;
        $this->monthLimit = $monthLimit;
        $this->totalLimit = $totalLimit;
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->memberCardId, 'memberCardId');
        $this->validateOptionalPositiveDecimal($this->dailyLimit, 'dailyLimit');
        $this->validateOptionalPositiveDecimal($this->monthLimit, 'monthLimit');
        $this->validateOptionalPositiveDecimal($this->totalLimit, 'totalLimit');
        if ($this->dailyLimit === null && $this->monthLimit === null && $this->totalLimit === null) {
            throw new \InvalidArgumentException('At least one card limit must be provided.');
        }
    }

    /** The legacy cardType property remains readable but is not part of the API wire body. */
    public function toArray(): array
    {
        $data = parent::toArray();
        unset($data['cardType']);
        return $data;
    }
}

final class IssueCardDetailsRequest extends JsonModel
{
    public function __construct(public readonly int|string|null $taskId = null)
    {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->taskId, 'taskId');
    }
}

final class IssueCardDetailsResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberCardId = null,
        public readonly ?string $cardStatus = null,
        public readonly ?string $message = null,
    ) {
    }
}
