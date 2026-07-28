<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\Model\MemberCardPageRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardsListTest extends EndpointTestCase
{
    public function testListsCards(): void
    {
        $request = new MemberCardPageRequest(1, 10, null, 'ACTIVE');
        $result = (new CardsApi($this->transport(['total' => 1, 'list' => [['memberCardId' => 7]]])))->list($request);

        self::assertSame(1, $result?->total);
        $this->assertRequest('/open-api/v1/cards/list', $request);
    }
}
