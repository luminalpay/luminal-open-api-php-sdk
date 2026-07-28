<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\AuthApi;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class AuthLogoutTest extends EndpointTestCase
{
    public function testLogsOutConfiguredToken(): void
    {
        self::assertTrue((new AuthApi($this->transport(true)))->logout());
        $this->assertRequest('/open-api/v1/auth/logout', null);
    }
}
