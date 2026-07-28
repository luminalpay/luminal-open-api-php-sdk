<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\SharedAccountsApi;
use Luminal\OpenApiSdk\Model\SharedAccountGetRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class SharedAccountsDetailsTest extends EndpointTestCase
{
    public function testGetsSharedAccountDetails(): void
    {
        $request = new SharedAccountGetRequest(11);
        $result = (new SharedAccountsApi($this->transport(['memberSharedAccountId' => 11])))->details($request);

        self::assertSame(11, $result?->memberSharedAccountId);
        $this->assertRequest('/open-api/v1/shared-account/details', $request);
    }
}
