<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardGroupsApi;
use Luminal\OpenApiSdk\Model\CardGroupRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardGroupsListTest extends EndpointTestCase
{
    public function testListsCardGroups(): void
    {
        $request = new CardGroupRequest(1, 10);
        $result = (new CardGroupsApi($this->transport(['list' => [['cardGroupId' => 3]]])))->list($request);

        self::assertSame(3, $result?->list[0]->cardGroupId);
        $this->assertRequest('/open-api/v1/cards/group', $request);
    }
}
