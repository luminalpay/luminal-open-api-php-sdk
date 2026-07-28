<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardGroupsApi;
use Luminal\OpenApiSdk\Model\CardGroupDeleteRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardGroupsDeleteTest extends EndpointTestCase
{
    public function testDeletesCardGroup(): void
    {
        $request = new CardGroupDeleteRequest(3);
        $result = (new CardGroupsApi($this->transport(true)))->delete($request);

        self::assertTrue($result);
        $this->assertRequest('/open-api/v1/cards/group/delete', $request);
    }
}
