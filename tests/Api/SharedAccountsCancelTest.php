<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\SharedAccountsApi;
use Luminal\OpenApiSdk\Model\SharedAccountCancelRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class SharedAccountsCancelTest extends EndpointTestCase
{
    public function testCancelsSharedAccountWithVerificationCode(): void
    {
        $request = new SharedAccountCancelRequest(11, 'No longer needed', 'otp-123');
        $result = (new SharedAccountsApi($this->transport(true)))->cancel($request);

        self::assertTrue($result);
        $this->assertRequest('/open-api/v1/shared-account/cancel', $request);
        $this->assertJsonBodyContains('"memberSharedAccountId":11');
    }
}
