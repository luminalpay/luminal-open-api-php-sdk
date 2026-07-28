<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\Model\CardIdRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardsLimitTest extends EndpointTestCase
{
    public function testGetsCardLimit(): void
    {
        $request = new CardIdRequest(7);
        $result = (new CardsApi($this->transport(['memberCardId' => 7, 'totalLimit' => 500])))->limit($request);

        self::assertSame(500, $result?->totalLimit);
        $this->assertRequest('/open-api/v1/cards/limit', $request);
    }
}
