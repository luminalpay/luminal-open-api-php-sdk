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

    public function testModifiesRechargeCardLimitsWithoutLegacyCardTypeOnWire(): void
    {
        $request = new CardLimitUpdateRequest(7, 'RECHARGE', 10, 100, null);
        $result = (new CardsApi($this->transport(true)))->modifyLimit($request);

        self::assertTrue($result);
        $this->assertRequest('/open-api/v1/cards/limit/modify', $request);
        self::assertStringNotContainsString('cardType', $this->request['body']);
        self::assertStringContainsString('"dailyLimit":10', $this->request['body']);
        self::assertStringContainsString('"monthLimit":100', $this->request['body']);
    }

    public function testModifiesCardLimitAsynchronously(): void
    {
        $request = new CardLimitUpdateRequest(7, 10, 100, null);
        $result = (new CardsApi($this->transport(701)))->modifyLimitAsync($request);

        self::assertSame(701, $result);
        $this->assertRequest('/open-api/v1/cards/limit/modify/operation-record', $request);
        self::assertStringNotContainsString('cardType', $this->request['body']);
    }

    public function testSupportsAnOmittedDailyLimitInTheCurrentConstructor(): void
    {
        $request = new CardLimitUpdateRequest(
            memberCardId: 7,
            dailyLimit: null,
            monthLimit: 100,
            totalLimit: 750,
        );

        self::assertSame([
            'memberCardId' => 7,
            'dailyLimit' => null,
            'monthLimit' => 100,
            'totalLimit' => 750,
        ], $request->toArray());
    }
}
