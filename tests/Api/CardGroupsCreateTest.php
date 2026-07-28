<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardGroupsApi;
use Luminal\OpenApiSdk\Model\CardGroupCreateRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardGroupsCreateTest extends EndpointTestCase
{
    public function testCreatesCardGroup(): void
    {
        $request = new CardGroupCreateRequest('Travel', 'SHARED');
        $result = (new CardGroupsApi($this->transport(['cardGroupId' => 3, 'cardGroupName' => 'Travel'])))->create($request);

        self::assertSame(3, $result?->cardGroupId);
        $this->assertRequest('/open-api/v1/cards/group/create', $request);
    }
}
