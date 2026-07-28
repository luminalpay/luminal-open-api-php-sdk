<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\AccountsApi;
use Luminal\OpenApiSdk\Model\WalletInfoRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class AccountsListTest extends EndpointTestCase
{
    public function testListsAccounts(): void
    {
        $request = new WalletInfoRequest(1, 10, 'USD');
        $result = (new AccountsApi($this->transport(['list' => [['walletNo' => 'W1']]])))->list($request);

        self::assertSame('W1', $result?->list[0]->walletNo);
        $this->assertRequest('/open-api/v1/accounts', $request);
    }
}
