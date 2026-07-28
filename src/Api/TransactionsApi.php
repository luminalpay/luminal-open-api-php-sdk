<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Api;

use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\TransportInterface;
use Luminal\OpenApiSdk\Model\JsonModel;
use Luminal\OpenApiSdk\Model\PageResult;
use Luminal\OpenApiSdk\Model\WalletTransactionRequest;
use Luminal\OpenApiSdk\Model\WalletTransactionResponse;

/** Wallet-transaction endpoints. */
final class TransactionsApi
{
    public function __construct(private readonly TransportInterface $transport)
    {
    }

    /** Lists wallet transactions for the current member. */
    public function list(WalletTransactionRequest $request): ?PageResult
    {
        $data = $this->transport->postAuthorized('/open-api/v1/transactions/list', $request);
        if ($data === null) {
            return null;
        }
        if (!is_array($data)) {
            throw new ApiException('Transactions response data must be a JSON object.');
        }
        return PageResult::hydrate($data, WalletTransactionResponse::class);
    }
}

