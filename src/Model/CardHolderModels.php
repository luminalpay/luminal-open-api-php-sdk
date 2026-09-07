<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;

use DateTimeImmutable;
use DateTimeInterface;

/** Cardholder request and response models. */
final class CardHolderCreateRequest extends JsonModel
{
    public function __construct(
        public readonly ?string $lastName = null,
        public readonly ?string $firstName = null,
        public readonly DateTimeImmutable|string|null $birthDate = null,
        public readonly ?string $mail = null,
        public readonly ?string $phone = null,
        public readonly ?string $areaCode = null,
        public readonly int|string|null $countryId = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $state = null,
        public readonly ?string $city = null,
        public readonly ?string $addressLine1 = null,
        public readonly ?string $addressLine2 = null,
    ) {
    }

    protected function validateModel(): void
    {
        foreach ([
            'lastName' => $this->lastName,
            'firstName' => $this->firstName,
            'mail' => $this->mail,
            'phone' => $this->phone,
            'areaCode' => $this->areaCode,
            'postalCode' => $this->postalCode,
            'state' => $this->state,
            'city' => $this->city,
            'addressLine1' => $this->addressLine1,
        ] as $name => $value) {
            $this->requireNonBlank($value, $name);
        }
        $this->requirePositiveId($this->countryId, 'countryId');
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        if ($this->birthDate instanceof DateTimeInterface) {
            $data['birthDate'] = $this->birthDate->format('Y-m-d');
        }
        return $data;
    }
}

/** Parameters for updating all editable cardholder fields. */
final class CardHolderModifyRequest extends JsonModel
{
    public function __construct(
        public readonly int|string|null $cardHolderId = null,
        public readonly ?string $lastName = null,
        public readonly ?string $firstName = null,
        public readonly DateTimeImmutable|string|null $birthDate = null,
        public readonly ?string $mail = null,
        public readonly ?string $phone = null,
        public readonly ?string $areaCode = null,
        public readonly int|string|null $countryId = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $state = null,
        public readonly ?string $city = null,
        public readonly ?string $addressLine1 = null,
        public readonly ?string $addressLine2 = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->cardHolderId, 'cardHolderId');
        foreach ([
            'lastName' => $this->lastName,
            'firstName' => $this->firstName,
            'mail' => $this->mail,
            'phone' => $this->phone,
            'areaCode' => $this->areaCode,
            'postalCode' => $this->postalCode,
            'state' => $this->state,
            'city' => $this->city,
            'addressLine1' => $this->addressLine1,
        ] as $name => $value) {
            $this->requireNonBlank($value, $name);
        }
        $this->requirePositiveId($this->countryId, 'countryId');
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        if ($this->birthDate instanceof DateTimeInterface) {
            $data['birthDate'] = $this->birthDate->format('Y-m-d');
        }
        return $data;
    }
}

/** Filters the cardholder page endpoint. */
final class CardHolderPageRequest extends JsonModel
{
    /** @param list<int|string>|null $createTime */
    public function __construct(
        public readonly ?int $pageNo = null,
        public readonly ?int $pageSize = null,
        public readonly int|string|null $cardHolderId = null,
        public readonly ?string $name = null,
        public readonly ?string $phone = null,
        public readonly ?string $mail = null,
        public readonly ?array $createTime = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
        $this->validateOptionalId($this->cardHolderId, 'cardHolderId');
        $this->validateInt64Range($this->createTime, 'createTime');
    }

    public function numericJsonFieldTypes(): array
    {
        return [...parent::numericJsonFieldTypes(), 'createTime' => 'long'];
    }
}

/** Filters cards associated with a cardholder. */
final class CardHolderCardPageRequest extends JsonModel
{
    public function __construct(
        public readonly ?int $pageNo = null,
        public readonly ?int $pageSize = null,
        public readonly int|string|null $cardHolderId = null,
        public readonly int|string|null $memberCardId = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
        $this->validateOptionalId($this->cardHolderId, 'cardHolderId');
        $this->validateOptionalId($this->memberCardId, 'memberCardId');
    }
}

/** Complete cardholder profile returned by the details endpoint. */
final class CardHolderDetailResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $cardHolderId = null,
        public readonly ?string $lastName = null,
        public readonly ?string $firstName = null,
        public readonly ?DateTimeImmutable $birthDate = null,
        public readonly ?string $mail = null,
        public readonly ?string $phone = null,
        public readonly ?string $areaCode = null,
        public readonly int|string|null $countryId = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $state = null,
        public readonly ?string $city = null,
        public readonly ?string $addressLine1 = null,
        public readonly ?string $addressLine2 = null,
        public readonly ?DateTimeImmutable $createTime = null,
    ) {
    }
}

/** One cardholder item returned by the page endpoint. */
final class CardHolderPageResponse extends JsonModel
{
    public function __construct(
        public readonly ?DateTimeImmutable $createTime = null,
        public readonly int|string|null $cardHolderId = null,
        public readonly ?string $fullName = null,
        public readonly ?string $fullPhone = null,
        public readonly ?string $mail = null,
    ) {
    }
}

/** One card associated with a cardholder. */
final class CardHolderCardResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $memberCardId = null,
        public readonly ?string $maskCardNo = null,
        public readonly ?DateTimeImmutable $openTime = null,
    ) {
    }
}

/** Country or region available for cardholder creation and updates. */
final class CardHolderCountryResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $countryId = null,
        public readonly ?string $countryName = null,
        public readonly ?string $countryCode = null,
        public readonly ?string $areaCode = null,
        public readonly ?int $phoneMaxLength = null,
    ) {
    }
}
