<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\SharedAccountsApi;
use Luminal\OpenApiSdk\Model\SharedAccountBalanceRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class SharedAccountsDecreaseTest extends EndpointTestCase
{
    public function testWithdrawsFromSharedAccount(): void
    {
        $request = new SharedAccountBalanceRequest(11, 5.25);
        $result = (new SharedAccountsApi($this->transport(['sharedAccountTransactionId' => 'T2'])))->decrease($request);

        self::assertSame('T2', $result?->sharedAccountTransactionId);
        $this->assertRequest('/open-api/v1/shared-account/decrease', $request);
    }
}
