<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;


/** Wallet-account request and response models. */
final class WalletInfoRequest extends JsonModel
{
    public function __construct(
        public readonly ?int $pageNo = null,
        public readonly ?int $pageSize = null,
        public readonly ?string $currency = null,
    ) {
    }

    protected function validateModel(): void
    {
        $this->validatePage($this->pageNo, $this->pageSize);
    }
}

/** Wallet-account information returned by the API. */
final class WalletInfoResponse extends JsonModel
{
    public function __construct(
        public readonly int|float|string|null $balance = null,
        public readonly ?string $currency = null,
        public readonly int|float|string|null $frozenBalance = null,
        public readonly ?string $memberNo = null,
        public readonly ?string $walletNo = null,
        public readonly ?string $walletStatus = null,
    ) {
    }
}
