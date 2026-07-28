<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardGroupsApi;
use Luminal\OpenApiSdk\Model\CardGroupUpdateRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardGroupsUpdateTest extends EndpointTestCase
{
    public function testUpdatesCardGroup(): void
    {
        $request = new CardGroupUpdateRequest(3, 'Business');
        $result = (new CardGroupsApi($this->transport(true)))->update($request);

        self::assertTrue($result);
        $this->assertRequest('/open-api/v1/cards/group/update', $request);
    }
}
