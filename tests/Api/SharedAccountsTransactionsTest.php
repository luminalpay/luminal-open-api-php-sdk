<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\SharedAccountsApi;
use Luminal\OpenApiSdk\Model\SharedAccountTransactionsRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class SharedAccountsTransactionsTest extends EndpointTestCase
{
    public function testListsSharedAccountTransactions(): void
    {
        $request = new SharedAccountTransactionsRequest(1, 10, null, 11, null, 'DEPOSIT');
        $result = (new SharedAccountsApi($this->transport(['list' => [['type' => 'DEPOSIT']]])))->transactions($request);

        self::assertSame('DEPOSIT', $result?->list[0]->type);
        $this->assertRequest('/open-api/v1/shared-account/transactions', $request);
    }
}
