<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\Model\CardIdRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardsFreezeTest extends EndpointTestCase
{
    public function testFreezesCard(): void
    {
        $request = new CardIdRequest(7);
        $result = (new CardsApi($this->transport(true)))->freeze($request);

        self::assertTrue($result);
        $this->assertRequest('/open-api/v1/cards/freeze', $request);
    }
}
