<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Api;

use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\TransportInterface;
use Luminal\OpenApiSdk\Model\JsonModel;
use Luminal\OpenApiSdk\Model\CreateSharedAccountRequest;
use Luminal\OpenApiSdk\Model\PageResult;
use Luminal\OpenApiSdk\Model\SharedAccountBalanceRequest;
use Luminal\OpenApiSdk\Model\SharedAccountGetRequest;
use Luminal\OpenApiSdk\Model\SharedAccountIdResponse;
use Luminal\OpenApiSdk\Model\SharedAccountPageRequest;
use Luminal\OpenApiSdk\Model\SharedAccountResponse;
use Luminal\OpenApiSdk\Model\SharedAccountTransactionIdResponse;
use Luminal\OpenApiSdk\Model\SharedAccountTransactionResponse;
use Luminal\OpenApiSdk\Model\SharedAccountTransactionsRequest;

/** Shared-account endpoints. */
final class SharedAccountsApi
{
    private const PATH = '/open-api/v1/shared-account';

    public function __construct(private readonly TransportInterface $transport)
    {
    }

    /** Creates and initially funds a shared account. */
    public function create(CreateSharedAccountRequest $request): ?SharedAccountIdResponse
    {
        return self::objectResult($this->post('/create', $request), SharedAccountIdResponse::class);
    }

    /** Lists shared accounts for the current member. */
    public function list(SharedAccountPageRequest $request): ?PageResult
    {
        return self::page($this->post('/list', $request), SharedAccountResponse::class);
    }

    /** Deposits funds into a shared account. */
    public function increase(SharedAccountBalanceRequest $request): ?SharedAccountTransactionIdResponse
    {
        return self::objectResult($this->post('/increase', $request), SharedAccountTransactionIdResponse::class);
    }

    /** Withdraws funds from a shared account. */
    public function decrease(SharedAccountBalanceRequest $request): ?SharedAccountTransactionIdResponse
    {
        return self::objectResult($this->post('/decrease', $request), SharedAccountTransactionIdResponse::class);
    }

    /** Retrieves one shared account. */
    public function details(SharedAccountGetRequest $request): ?SharedAccountResponse
    {
        return self::objectResult($this->post('/details', $request), SharedAccountResponse::class);
    }

    /** Lists shared-account transactions. */
    public function transactions(SharedAccountTransactionsRequest $request): ?PageResult
    {
        return self::page($this->post('/transactions', $request), SharedAccountTransactionResponse::class);
    }

    private function post(string $path, JsonModel $request): mixed
    {
        return $this->transport->postAuthorized(self::PATH . $path, $request);
    }

    /** @param class-string<JsonModel> $class */
    private static function objectResult(mixed $data, string $class): ?JsonModel
    {
        return $data === null ? null : $class::fromArray(self::object($data));
    }

    /** @param class-string<JsonModel> $itemClass */
    private static function page(mixed $data, string $itemClass): ?PageResult
    {
        if ($data === null) {
            return null;
        }
        return PageResult::hydrate(self::object($data), $itemClass);
    }

    private static function object(mixed $data): array
    {
        if (!is_array($data)) {
            throw new ApiException('Shared-account response data must be a JSON object.');
        }
        return $data;
    }
}

