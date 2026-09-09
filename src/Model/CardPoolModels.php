<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;

/** Card-pool request and response models. */
final class CardPoolRequest extends JsonModel
{
    public function __construct(
        public readonly int|string|null $cardPoolId = null,
        public readonly ?string $poolName = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->validateOptionalId($this->cardPoolId, 'cardPoolId');
    }
}

/** Card-pool information returned by the API. */
final class CardPoolResponse extends JsonModel
{
    /** @param list<string>|null $cardBins */
    public function __construct(
        public readonly int|string|null $cardPoolId = null,
        public readonly ?string $poolName = null,
        public readonly ?int $availableCount = null,
        public readonly ?int $sharedAccountCount = null,
        public readonly ?array $cardBins = null,
        public readonly ?int $canApplyAccount = null,
        public readonly ?int $canApply = null,
    ) {
    }
}
