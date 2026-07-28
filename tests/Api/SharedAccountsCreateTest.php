<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\SharedAccountsApi;
use Luminal\OpenApiSdk\Model\CreateSharedAccountRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class SharedAccountsCreateTest extends EndpointTestCase
{
    public function testCreatesSharedAccount(): void
    {
        $request = new CreateSharedAccountRequest(1001, 100.00, 'Travel');
        $result = (new SharedAccountsApi($this->transport(['memberSharedAccountId' => 11])))->create($request);

        self::assertSame(11, $result?->memberSharedAccountId);
        $this->assertRequest('/open-api/v1/shared-account/create', $request);
    }
}
