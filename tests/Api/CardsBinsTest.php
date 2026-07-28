<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\Model\CardBinsRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardsBinsTest extends EndpointTestCase
{
    public function testListsCardBins(): void
    {
        $request = new CardBinsRequest(1, 20);
        $result = (new CardsApi($this->transport(['list' => [['cardBinId' => 1]]])))->bins($request);

        self::assertSame(1, $result?->list[0]->cardBinId);
        $this->assertRequest('/open-api/v1/cards/bins', $request);
    }
}
