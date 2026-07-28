<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;


use DateTimeImmutable;

/** Card request and response models. */
final class CardBinsRequest extends JsonModel
{
    public function __construct(
        public readonly ?int $pageNo = null,
        public readonly ?int $pageSize = null,
        public readonly ?string $cardType = null,
        public readonly ?string $cardOrganization = null,
        public readonly ?string $cardBin = null,
        public readonly ?string $areaCode = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
    }
}

final class CardBinResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $cardBinId = null,
        public readonly ?string $cardType = null,
        public readonly ?string $currencyCode = null,
        public readonly ?string $areaCode = null,
        public readonly ?string $cardBin = null,
        public readonly ?string $cardOrganization = null,
        public readonly ?string $applicableScenarios = null,
    ) {
    }
}

/** Card-issuance request. The exact serialized body is signed before submission. */
final class IssueCardRequest extends JsonModel
{
    public function __construct(
        public readonly ?int $applyCount = null,
        public readonly int|string|null $cardBinId = null,
        public readonly int|string|null $cardGroupId = null,
        public readonly ?string $cardName = null,
        public readonly ?string $cardType = null,
        public readonly int|string|null $memberSharedAccountId = null,
        public readonly int|float|string|null $monthLimit = null,
        public readonly int|float|string|null $rechargeAmount = null,
    ) {
    }

    protected function validateModel(): void
    {
        if ($this->applyCount !== null && $this->applyCount < 1) {
            throw new \InvalidArgumentException('applyCount must be greater than zero.');
        }
        $this->requirePositiveId($this->cardBinId, 'cardBinId');
        $this->validateOptionalId($this->cardGroupId, 'cardGroupId');
        $this->requirePositiveId($this->memberSharedAccountId, 'memberSharedAccountId');
        if ($this->monthLimit !== null) {
            $this->requirePositiveDecimal($this->monthLimit, 'monthLimit');
        }
        $this->requirePositiveDecimal($this->rechargeAmount, 'rechargeAmount');
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
}

final class CardTransactionResponse extends JsonModel
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
    public function __construct(
        public readonly int|string|null $memberCardId = null,
        public readonly int|float|string|null $totalLimit = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->memberCardId, 'memberCardId');
        $this->requirePositiveDecimal($this->totalLimit, 'totalLimit');
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
