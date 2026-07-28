<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\AuthApi;
use Luminal\OpenApiSdk\Model\RefreshTokenRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class AuthRefreshTokenTest extends EndpointTestCase
{
    public function testRefreshesToken(): void
    {
        $request = new RefreshTokenRequest('refresh-token');
        $result = (new AuthApi($this->transport(['accessToken' => 'new-token'])))->refreshToken($request);

        self::assertSame('new-token', $result?->accessToken);
        $this->assertRequest('/open-api/v1/auth/refresh-token', $request);
    }
}
