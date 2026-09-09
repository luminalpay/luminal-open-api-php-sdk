<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Client;
use Luminal\OpenApiSdk\HttpTransport;
use Luminal\OpenApiSdk\Model\CardBinResponse;
use Luminal\OpenApiSdk\Model\CardPoolRequest;
use Luminal\OpenApiSdk\Model\CardPoolResponse;
use Luminal\OpenApiSdk\Model\CardBinsRequest;
use Luminal\OpenApiSdk\Model\CreateSharedAccountRequest;
use Luminal\OpenApiSdk\Model\SharedAccountPageRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

/** Local request/response coverage for pool-based shared-account opening. */
final class CardPoolSharedAccountApiTest extends EndpointTestCase
{
    public function testOpensSharedAccountFromOneCachedPoolListResult(): void
    {
        $requests = [];
        $client = $this->client($requests);

        $pools = $client->cardPools()->list(new CardPoolRequest());
        self::assertNotNull($pools);
        self::assertCount(1, $pools);
        self::assertInstanceOf(CardPoolResponse::class, $pools[0]);

        $selectedPool = $pools[0];
        $bins = $client->cards()->bins(new CardBinsRequest(
            pageNo: 1,
            pageSize: 20,
            cardPoolId: $selectedPool->cardPoolId,
            cardType: 'SHARED',
        ));
        self::assertNotNull($bins);
        self::assertInstanceOf(CardBinResponse::class, $bins->list[0]);

        $created = $client->sharedAccounts()->create(new CreateSharedAccountRequest(
            cardPoolId: $selectedPool->cardPoolId,
            rechargeAmount: '100.00',
            accountName: 'Main',
        ));

        self::assertSame(9001, $selectedPool->cardPoolId);
        self::assertSame(1001, $bins->list[0]->cardBinId);
        self::assertSame(2001, $created?->memberSharedAccountId);
        self::assertCount(3, $requests);

        self::assertSame('/open-api/v1/cards/pools', $requests[0]['path']);
        self::assertSame('Bearer test-token', $requests[0]['authorization']);
        self::assertStringNotContainsString('cardBinId', $requests[0]['body']);

        self::assertSame('/open-api/v1/cards/bins', $requests[1]['path']);
        self::assertStringContainsString('"cardPoolId":9001', $requests[1]['body']);

        self::assertSame('/open-api/v1/shared-account/create', $requests[2]['path']);
        self::assertStringContainsString('"cardPoolId":9001', $requests[2]['body']);
        self::assertStringNotContainsString('"cardBinId"', $requests[2]['body']);
    }

    public function testListsSharedAccountsByPoolAndDecodesPoolFields(): void
    {
        $requests = [];
        $client = $this->client($requests);

        $result = $client->sharedAccounts()->list(new SharedAccountPageRequest(
            pageNo: 1,
            pageSize: 20,
            cardPoolId: 9001,
        ));

        self::assertNotNull($result);
        self::assertNotNull($result->list);
        self::assertSame(9001, $result->list[0]->cardPoolId);
        self::assertSame('Main Pool', $result->list[0]->poolName);
        self::assertCount(1, $requests);
        self::assertSame('/open-api/v1/shared-account/list', $requests[0]['path']);
        self::assertStringContainsString('"cardPoolId":9001', $requests[0]['body']);
    }

    /** @param list<array{method:string,path:string,authorization:?string,body:string}> $requests */
    private function client(array &$requests): Client
    {
        $transport = new HttpTransport(
            'https://api.example.test',
            'test-token',
            static function (string $method, string $url, array $headers, ?string $body) use (&$requests): array {
                $path = (string)parse_url($url, PHP_URL_PATH);
                $requests[] = [
                    'method' => $method,
                    'path' => $path,
                    'authorization' => $headers['Authorization'] ?? null,
                    'body' => $body ?? '',
                ];
                $data = match ($path) {
                    '/open-api/v1/cards/pools' => [[
                        'cardPoolId' => 9001,
                        'poolName' => 'Main Pool',
                        'availableCount' => 1,
                        'sharedAccountCount' => 0,
                        'cardBins' => ['4416'],
                        'canApplyAccount' => 1,
                        'canApply' => 0,
                    ]],
                    '/open-api/v1/cards/bins' => [
                        'total' => 1,
                        'list' => [[
                            'cardBinId' => 1001,
                            'cardPoolId' => 9001,
                            'poolName' => 'Main Pool',
                            'cardBin' => '4416',
                            'cardType' => 'SHARED',
                        ]],
                        'extra' => null,
                    ],
                    '/open-api/v1/shared-account/create' => ['memberSharedAccountId' => 2001],
                    '/open-api/v1/shared-account/list' => [
                        'total' => 1,
                        'list' => [[
                            'memberSharedAccountId' => 2001,
                            'accountName' => 'Main',
                            'cardBinId' => 1001,
                            'cardPoolId' => 9001,
                            'poolName' => 'Main Pool',
                        ]],
                        'extra' => null,
                    ],
                    default => null,
                };
                return [
                    'status' => 200,
                    'body' => json_encode(['code' => 0, 'msg' => 'success', 'data' => $data], JSON_THROW_ON_ERROR),
                ];
            },
        );
        return new Client($transport);
    }
}
