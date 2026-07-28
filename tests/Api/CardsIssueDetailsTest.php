<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\Model\IssueCardDetailsRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardsIssueDetailsTest extends EndpointTestCase
{
    public function testGetsCardIssueDetails(): void
    {
        $request = new IssueCardDetailsRequest(9001);
        $result = (new CardsApi($this->transport([['memberCardId' => 7, 'cardStatus' => 'SUCCESS']])))->issueDetails($request);

        self::assertSame('SUCCESS', $result[0]->cardStatus);
        $this->assertRequest('/open-api/v1/cards/issue/detail', $request);
    }
}
