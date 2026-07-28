<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\Model\CardTransactionsRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardsTransactionsTest extends EndpointTestCase
{
    public function testListsCardTransactions(): void
    {
        $request = new CardTransactionsRequest(1, 10, null, 7, [1, 2]);
        $result = (new CardsApi($this->transport(['list' => [['memberCardId' => 88]]])))->transactions($request);

        self::assertSame(88, $result?->list[0]->memberCardId);
        $this->assertRequest('/open-api/v1/cards/transactions', $request);
    }
}
