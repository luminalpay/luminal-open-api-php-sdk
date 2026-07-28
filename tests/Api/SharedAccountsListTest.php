<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\SharedAccountsApi;
use Luminal\OpenApiSdk\Model\SharedAccountPageRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class SharedAccountsListTest extends EndpointTestCase
{
    public function testListsSharedAccounts(): void
    {
        $request = new SharedAccountPageRequest(1, 10, null, 'Travel');
        $result = (new SharedAccountsApi($this->transport(['list' => [['accountName' => 'Travel']]])))->list($request);

        self::assertSame('Travel', $result?->list[0]->accountName);
        $this->assertRequest('/open-api/v1/shared-account/list', $request);
    }
}
