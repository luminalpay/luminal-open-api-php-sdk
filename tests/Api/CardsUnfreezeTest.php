<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\Model\CardIdRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardsUnfreezeTest extends EndpointTestCase
{
    public function testUnfreezesCard(): void
    {
        $request = new CardIdRequest(7);
        $result = (new CardsApi($this->transport(true)))->unfreeze($request);

        self::assertTrue($result);
        $this->assertRequest('/open-api/v1/cards/unfreeze', $request);
    }
}
