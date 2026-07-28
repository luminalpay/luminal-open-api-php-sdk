<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;


use DateTimeImmutable;

/** Card-group request and response models. */
final class CardGroupRequest extends JsonModel
{
    public function __construct(
        public readonly ?int $pageNo = null,
        public readonly ?int $pageSize = null,
        public readonly ?string $cardType = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
    }
}

final class CardGroupResponse extends JsonModel
{
    public function __construct(
        public readonly int|string|null $cardGroupId = null,
        public readonly ?string $cardGroupName = null,
        public readonly ?string $cardType = null,
        public readonly ?DateTimeImmutable $createTime = null,
    ) {
    }
}

final class CardGroupCreateRequest extends JsonModel
{
    public function __construct(
        public readonly ?string $cardGroupName = null,
        public readonly ?string $cardType = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->requireNonBlank($this->cardGroupName, 'cardGroupName');
        $this->requireNonBlank($this->cardType, 'cardType');
    }
}

final class CardGroupUpdateRequest extends JsonModel
{
    public function __construct(
        public readonly int|string|null $cardGroupId = null,
        public readonly ?string $cardGroupName = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->cardGroupId, 'cardGroupId');
        $this->requireNonBlank($this->cardGroupName, 'cardGroupName');
    }
}

final class CardGroupDeleteRequest extends JsonModel
{
    public function __construct(public readonly int|string|null $cardGroupId = null)
    {
    }

    protected function validateModel(): void
    {
        $this->requirePositiveId($this->cardGroupId, 'cardGroupId');
    }
}
