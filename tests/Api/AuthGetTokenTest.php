<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\AuthApi;
use Luminal\OpenApiSdk\Model\AuthTokenRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class AuthGetTokenTest extends EndpointTestCase
{
    public function testGetsTokenWithBasicAuthorization(): void
    {
        $result = (new AuthApi($this->transport(['accessToken' => 'access-token'])))->getToken(
            new AuthTokenRequest('app-id', 'app-secret'),
        );

        self::assertSame('access-token', $result?->accessToken);
        $this->assertRequest('/open-api/v1/auth/token', null, 'Basic ' . base64_encode('app-id:app-secret'));
    }
}
