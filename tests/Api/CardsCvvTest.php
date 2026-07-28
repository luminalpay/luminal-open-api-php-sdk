<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\Model\CardIdRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardsCvvTest extends EndpointTestCase
{
    public function testRetrievesCardSensitiveData(): void
    {
        $request = new CardIdRequest(7);
        $result = (new CardsApi($this->transport(['cardNumber' => '4111111111111111', 'cvv' => '123'])))->cvv($request);

        self::assertSame('123', $result?->cvv);
        $this->assertRequest('/open-api/v1/cards/cvv', $request);
    }
}
