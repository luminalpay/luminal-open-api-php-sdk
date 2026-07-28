<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Api;

use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\TransportInterface;
use Luminal\OpenApiSdk\Model\JsonModel;
use Luminal\OpenApiSdk\Model\PageResult;
use Luminal\OpenApiSdk\Model\WalletInfoRequest;
use Luminal\OpenApiSdk\Model\WalletInfoResponse;

/** Wallet-account endpoints. */
final class AccountsApi
{
    public function __construct(private readonly TransportInterface $transport)
    {
    }

    /** Lists wallet accounts for the current member. */
    public function list(WalletInfoRequest $request): ?PageResult
    {
        $data = $this->transport->postAuthorized('/open-api/v1/accounts', $request);
        return self::page($data, WalletInfoResponse::class);
    }

    /** @param class-string<JsonModel> $itemClass */
    private static function page(mixed $data, string $itemClass): ?PageResult
    {
        if ($data === null) {
            return null;
        }
        if (!is_array($data)) {
            throw new ApiException('Accounts response data must be a JSON object.');
        }
        return PageResult::hydrate($data, $itemClass);
    }
}

