<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\SharedAccountsApi;
use Luminal\OpenApiSdk\Model\SharedAccountBalanceRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class SharedAccountsIncreaseTest extends EndpointTestCase
{
    public function testDepositsIntoSharedAccount(): void
    {
        $request = new SharedAccountBalanceRequest(11, 10.50);
        $result = (new SharedAccountsApi($this->transport(['sharedAccountTransactionId' => 'T1'])))->increase($request);

        self::assertSame('T1', $result?->sharedAccountTransactionId);
        $this->assertRequest('/open-api/v1/shared-account/increase', $request);
    }
}
