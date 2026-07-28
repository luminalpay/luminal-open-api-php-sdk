<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\CanonicalJson;
use Luminal\OpenApiSdk\HttpTransport;
use Luminal\OpenApiSdk\Model\IssueCardRequest;
use Luminal\OpenApiSdk\Model\OAuth2Token;
use Luminal\OpenApiSdk\Model\SharedAccountBalanceRequest;
use PHPUnit\Framework\TestCase;

final class CanonicalJsonTest extends TestCase
{
    public function testPhpIntegersAlwaysSerializeAsJsonNumbers(): void
    {
        self::assertSame(
            '{"int32PlusOne":2147483648,"maximumNumber":9007199254740990,"maximumString":9007199254740991,"minimumNumber":-9007199254740990,"minimumString":-9007199254740991,"nested":[{"outside":9007199254740992}],"zero":0}',
            CanonicalJson::encode([
                'zero' => 0,
                'int32PlusOne' => 2_147_483_648,
                'minimumString' => -9_007_199_254_740_991,
                'minimumNumber' => -9_007_199_254_740_990,
                'maximumNumber' => 9_007_199_254_740_990,
                'maximumString' => 9_007_199_254_740_991,
                'nested' => [['outside' => 9_007_199_254_740_992]],
            ]),
        );
    }

    public function testIssueRequestSerializesJavaLongAndBigDecimalByWireRule(): void
    {
        $request = null;
        $transport = new HttpTransport(
            'https://api.example.test',
            'test-token',
            function (string $method, string $url, array $headers, ?string $body) use (&$request): array {
                $request = compact('method', 'url', 'headers', 'body');
                return ['status' => 200, 'body' => '{"code":0,"data":123}'];
            },
        );

        (new CardsApi($transport))->issue(new IssueCardRequest(
            applyCount: 1,
            cardBinId: '2011314055960203265',
            cardGroupId: '2079840146018058242',
            cardName: 'issue-test',
            cardType: 'SHARED',
            memberSharedAccountId: '2079840147377098752',
            rechargeAmount: '1.00',
        ), 'signature');

        self::assertSame(
            '{"applyCount":1,"cardBinId":"2011314055960203265","cardGroupId":"2079840146018058242","cardName":"issue-test","cardType":"SHARED","memberSharedAccountId":"2079840147377098752","rechargeAmount":1.00}',
            $request['body'],
        );
    }

    public function testRequestBodiesUseTheSameSerializationRule(): void
    {
        $request = null;
        $transport = new HttpTransport(
            'https://api.example.test',
            'test-token',
            function (string $method, string $url, array $headers, ?string $body) use (&$request): array {
                $request = compact('method', 'url', 'headers', 'body');
                return ['status' => 200, 'body' => '{"code":0,"data":true}'];
            },
        );

        $transport->postAuthorized('/open-api/v1/example', [
            'amount' => 100,
            'memberNo' => 2_064_991_632_710_991_874,
        ]);

        self::assertIsArray($request);
        self::assertSame('{"amount":100,"memberNo":2064991632710991874}', $request['body']);
    }

    public function testTypedObjectsApplyTheSameLongRule(): void
    {
        self::assertSame(
            '{"amount":100,"memberSharedAccountId":2147483648}',
            CanonicalJson::encode(new SharedAccountBalanceRequest(2_147_483_648, 100)),
        );
        self::assertSame(
            '{"amount":100,"memberSharedAccountId":"2064991632710991874"}',
            CanonicalJson::encode(new SharedAccountBalanceRequest('2064991632710991874', 100)),
        );
        self::assertSame(
            '{"amount":100,"memberSharedAccountId":9007199254740990}',
            CanonicalJson::encode(new SharedAccountBalanceRequest('9007199254740990', 100)),
        );
    }

    public function testResponseOversizedNumbersAreDecodedAsStrings(): void
    {
        $transport = new HttpTransport(
            'https://api.example.test',
            'test-token',
            static fn (): array => [
                'status' => 200,
                'body' => '{"code":0,"data":{"memberNo":2064991632710991874,"amount":100}}',
            ],
        );

        $data = $transport->postAuthorized('/open-api/v1/example', null);

        self::assertSame('2064991632710991874', $data['memberNo']);
        self::assertSame(100, $data['amount']);
    }

    public function testDecodedInt64ValuesUseTheJavaScriptSafeIntegerSerializationRule(): void
    {
        self::assertSame(
            [
                '-9007199254740992',
                '-9007199254740991',
                -9_007_199_254_740_990,
                0,
                9_007_199_254_740_990,
                '9007199254740991',
                '9007199254740992',
            ],
            CanonicalJson::normalizeDecoded([
                -9_007_199_254_740_992,
                -9_007_199_254_740_991,
                -9_007_199_254_740_990,
                0,
                9_007_199_254_740_990,
                9_007_199_254_740_991,
                9_007_199_254_740_992,
            ]),
        );
    }

    public function testTypedModelsRejectInvalidLongAndDecimalValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CanonicalJson::encode(new SharedAccountBalanceRequest('9223372036854775808', 100));
    }

    public function testTypedModelsRejectNonDecimalAmounts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CanonicalJson::encode(new SharedAccountBalanceRequest(100, 'not-a-decimal'));
    }

    public function testTypedModelsAcceptSignedLongBoundaries(): void
    {
        self::assertSame(
            '{"expiresTime":"-9223372036854775808"}',
            CanonicalJson::encode(new OAuth2Token(expiresTime: '-9223372036854775808')),
        );
        self::assertSame(
            '{"expiresTime":"9223372036854775807"}',
            CanonicalJson::encode(new OAuth2Token(expiresTime: '9223372036854775807')),
        );
        self::assertSame(
            '{"expiresTime":-9223372036854775808}',
            CanonicalJson::encodeForSignature(new OAuth2Token(expiresTime: '-9223372036854775808')),
        );
        self::assertSame(
            '{"expiresTime":9223372036854775807}',
            CanonicalJson::encodeForSignature(new OAuth2Token(expiresTime: '9223372036854775807')),
        );
    }

    public function testTypedLongUsesDifferentRequestAndSignatureRulesAtSafeIntegerBoundary(): void
    {
        $request = new SharedAccountBalanceRequest('9007199254740991', 100);

        self::assertSame(
            '{"amount":100,"memberSharedAccountId":"9007199254740991"}',
            CanonicalJson::encode($request),
        );
        self::assertSame(
            '{"amount":100,"memberSharedAccountId":9007199254740991}',
            CanonicalJson::encodeForSignature($request),
        );
    }

    public function testDebugInfoRedactsSensitiveValues(): void
    {
        $authRequest = new \Luminal\OpenApiSdk\Model\AuthTokenRequest('app-id', 'app-secret');
        $token = new \Luminal\OpenApiSdk\Model\OAuth2Token('access-token', 'Bearer', 100, 'refresh-token');
        $card = new \Luminal\OpenApiSdk\Model\CardCvvResponse(1, '4111111111111111', '123', '12/30');

        self::assertSame('<redacted>', $authRequest->__debugInfo()['appSecret']);
        self::assertSame('<redacted>', $token->__debugInfo()['accessToken']);
        self::assertSame('<redacted>', $token->__debugInfo()['refreshToken']);
        self::assertSame('<redacted>', $card->__debugInfo()['cardNo']);
        self::assertSame('<redacted>', $card->__debugInfo()['cvv']);
        self::assertSame('app-id', $authRequest->__debugInfo()['appId']);
    }

}
