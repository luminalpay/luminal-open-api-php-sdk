<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\Model\CardLimitUpdateRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardsModifyLimitTest extends EndpointTestCase
{
    public function testModifiesCardLimit(): void
    {
        $request = new CardLimitUpdateRequest(7, 750);
        $result = (new CardsApi($this->transport(true)))->modifyLimit($request);

        self::assertTrue($result);
        $this->assertRequest('/open-api/v1/cards/limit/modify', $request);
    }
}
