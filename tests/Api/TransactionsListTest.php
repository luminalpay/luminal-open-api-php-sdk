<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\TransactionsApi;
use Luminal\OpenApiSdk\Model\WalletTransactionRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class TransactionsListTest extends EndpointTestCase
{
    public function testListsWalletTransactions(): void
    {
        $request = new WalletTransactionRequest(1, 10, 1);
        $result = (new TransactionsApi($this->transport(['list' => [['orderNo' => 'O1']]])))->list($request);

        self::assertSame('O1', $result?->list[0]->orderNo);
        $this->assertRequest('/open-api/v1/transactions/list', $request);
    }
}
