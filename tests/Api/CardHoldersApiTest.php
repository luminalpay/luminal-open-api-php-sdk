<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use DateTimeImmutable;
use Luminal\OpenApiSdk\Api\CardHoldersApi;
use Luminal\OpenApiSdk\Model\CardHolderCardPageRequest;
use Luminal\OpenApiSdk\Model\CardHolderCreateRequest;
use Luminal\OpenApiSdk\Model\CardHolderModifyRequest;
use Luminal\OpenApiSdk\Model\CardHolderPageRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardHoldersApiTest extends EndpointTestCase
{
    public function testListsCountriesWithAuthorizedGet(): void
    {
        $result = (new CardHoldersApi($this->transport([
            ['countryId' => 244, 'countryName' => 'Hong Kong', 'countryCode' => 'HK', 'areaCode' => '852', 'phoneMaxLength' => 8],
        ])))->countries();

        self::assertSame(244, $result[0]->countryId);
        self::assertSame('852', $result[0]->areaCode);
        self::assertSame(8, $result[0]->phoneMaxLength);
        $this->assertGet('/open-api/v1/card-holders/countries');
    }

    public function testAddsCardholderWithDateOnlyBirthDate(): void
    {
        $request = $this->createRequest();
        $result = (new CardHoldersApi($this->transport(801)))->add($request);

        self::assertSame(801, $result);
        $this->assertRequest('/open-api/v1/card-holders/add', $request);
        $this->assertJsonBodyContains('"birthDate":"1990-01-15"');
    }

    public function testModifiesCardholder(): void
    {
        $request = new CardHolderModifyRequest(
            801,
            'Smith',
            'John',
            new DateTimeImmutable('1990-01-15'),
            'john.smith@example.com',
            '2025550123',
            '1',
            244,
            '10001',
            'New York',
            'New York',
            '350 Fifth Avenue',
            'Suite 12',
        );

        (new CardHoldersApi($this->transport(null)))->modify($request);

        $this->assertRequest('/open-api/v1/card-holders/modify', $request);
        $this->assertJsonBodyContains('"addressLine2":"Suite 12"');
    }

    public function testGetsCardholderDetailsFromPath(): void
    {
        $result = (new CardHoldersApi($this->transport([
            'cardHolderId' => 801,
            'lastName' => 'Smith',
            'firstName' => 'John',
            'birthDate' => '1990-01-15',
            'createTime' => 1788220800000,
        ])))->detail(801);

        self::assertSame(801, $result?->cardHolderId);
        self::assertSame('1990-01-15', $result?->birthDate?->format('Y-m-d'));
        self::assertSame('2026', $result?->createTime?->format('Y'));
        $this->assertEmptyJsonPost('/open-api/v1/card-holders/info/801');
    }

    public function testGetsCardholderDetailsWithJavaLocalDateArray(): void
    {
        $result = (new CardHoldersApi($this->transport([
            'cardHolderId' => 801,
            'birthDate' => [1990, 1, 15],
        ])))->detail(801);

        self::assertSame('1990-01-15', $result?->birthDate?->format('Y-m-d'));
    }

    public function testListsCardholders(): void
    {
        $request = new CardHolderPageRequest(
            1,
            20,
            801,
            'John',
            '202555',
            'john.smith@example.com',
            ['1788134400000', '1788220800000'],
        );
        $result = (new CardHoldersApi($this->transport([
            'total' => 1,
            'list' => [['cardHolderId' => 801, 'fullName' => 'John Smith', 'createTime' => 1788220800000]],
        ])))->page($request);

        self::assertSame(1, $result?->total);
        self::assertSame('John Smith', $result?->list[0]->fullName);
        $this->assertRequest('/open-api/v1/card-holders/page', $request);
        $this->assertJsonBodyContains('"createTime":[1788134400000,1788220800000]');
    }

    public function testListsCardsAssociatedWithCardholder(): void
    {
        $request = new CardHolderCardPageRequest(1, 20, 801, 901);
        $result = (new CardHoldersApi($this->transport([
            'total' => 1,
            'list' => [['memberCardId' => 901, 'maskCardNo' => '515783******1234', 'openTime' => 1788220800000]],
        ])))->associatedCards($request);

        self::assertSame(901, $result?->list[0]->memberCardId);
        self::assertSame('2026', $result?->list[0]->openTime?->format('Y'));
        $this->assertRequest('/open-api/v1/card-holders/card/page', $request);
    }

    private function createRequest(): CardHolderCreateRequest
    {
        return new CardHolderCreateRequest(
            'Smith',
            'John',
            new DateTimeImmutable('1990-01-15'),
            'john.smith@example.com',
            '2025550123',
            '1',
            244,
            '10001',
            'New York',
            'New York',
            '350 Fifth Avenue',
        );
    }
}
