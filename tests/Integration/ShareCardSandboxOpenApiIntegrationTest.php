<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Integration;

use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\Client;
use Luminal\OpenApiSdk\Model\AuthTokenRequest;
use Luminal\OpenApiSdk\Model\CardBinResponse;
use Luminal\OpenApiSdk\Model\CardBinsRequest;
use Luminal\OpenApiSdk\Model\CardCvvResponse;
use Luminal\OpenApiSdk\Model\CardGroupCreateRequest;
use Luminal\OpenApiSdk\Model\CardGroupDeleteRequest;
use Luminal\OpenApiSdk\Model\CardGroupRequest;
use Luminal\OpenApiSdk\Model\CardGroupResponse;
use Luminal\OpenApiSdk\Model\CardGroupUpdateRequest;
use Luminal\OpenApiSdk\Model\CardIdRequest;
use Luminal\OpenApiSdk\Model\CardLimitResponse;
use Luminal\OpenApiSdk\Model\CardLimitUpdateRequest;
use Luminal\OpenApiSdk\Model\CardTransactionsRequest;
use Luminal\OpenApiSdk\Model\CardPoolRequest;
use Luminal\OpenApiSdk\Model\CardPoolResponse;
use Luminal\OpenApiSdk\Model\CreateSharedAccountRequest;
use Luminal\OpenApiSdk\Model\IssueCardDetailsRequest;
use Luminal\OpenApiSdk\Model\IssueCardRequest;
use Luminal\OpenApiSdk\Model\MemberCardPageRequest;
use Luminal\OpenApiSdk\Model\MemberCardResponse;
use Luminal\OpenApiSdk\Model\RefreshTokenRequest;
use Luminal\OpenApiSdk\Model\SharedAccountBalanceRequest;
use Luminal\OpenApiSdk\Model\SharedAccountGetRequest;
use Luminal\OpenApiSdk\Model\SharedAccountPageRequest;
use Luminal\OpenApiSdk\Model\SharedAccountTransactionsRequest;
use Luminal\OpenApiSdk\Model\WalletInfoRequest;
use Luminal\OpenApiSdk\Model\WalletTransactionRequest;
use Luminal\OpenApiSdk\Model\OAuth2Token;
use Luminal\OpenApiSdk\Model\PageResult;
use Luminal\OpenApiSdk\RsaSignatures;
use Luminal\OpenApiSdk\Webhook\WebhookEventType;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
class ShareCardSandboxOpenApiIntegrationTest extends TestCase
{
    private const DEFAULT_BASE_URL = 'https://sandbox-openapi.luminalads.com';
    private const TEST_CARD_BIN = '22346703';
    private const SHARED_CARD_TYPE = 'SHARED';
    private const DEFAULT_WEBHOOK_HOST = '0.0.0.0';
    private const DEFAULT_WEBHOOK_PORT = 18081;
    private const DEFAULT_WEBHOOK_PATH = '/luminal-open-api-webhook';
    private const WEBHOOK_TIMEOUT_SECONDS = 30;
    private const WEBHOOK_LOG_INTERVAL_SECONDS = 10;
    private static ?Client $managedClient = null;
    private static ?OAuth2Token $cachedToken = null;
    private static bool $tokenFlowReady = false;
    private static int|string|null $cachedSharedAccountId = null;
    private static int|string|null $cachedCardGroupId = null;
    private static int|string|null $cachedIssueTaskId = null;
    private static int|string|null $cachedCardId = null;
    private static ?CardBinResponse $cachedCardBin = null;
    private static ?CardPoolResponse $cachedCardPool = null;
    private static bool $cardPoolFlow = false;
    private static mixed $webhookProcess = null;
    private static ?string $webhookEventDirectory = null;
    private const APP_ID = 'lpsha6pj5mwsb7tz';
    private const APP_SECRET = 'P11g59PXY33JjqL4CRJ2Oz3nfsjsWRKe';
    private const RSA_PRIVATE_KEY = <<<'KEY'
-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCLrBT15MJCcaAh
lv+Z/lTdnyu68ysMV3gqVOINbObUDI2HShcPxjYHu+AReQy03xobKbwMvpNk4x20
zVcAuTTT2KRkSzPwGTmls5TA+n1AZuGRjpUY7T1Bd5YkDf11Kc9qIrPKVm7GzkF9
hRGKVX1q8u0KL/PAb0uRwDOzk+X4vmZmFk5KdDJPFQuhWP/j7RTA7JaY0WC2Bs8e
HcDwKwwggNzRXcIdn89FvqWCxf2aufuiBHr1bPctlzYGxtI/Aq/U4grMWr5u0hwR
B3c0JpUY7jUIirKh3HxOgLK4IEUZmNX2k8Of5n0JSYnOBjNDBCT/Os4roA+dpVmg
h4NUg1XZAgMBAAECggEACjJs4NNbyddLECy6mjLm3fu7CPXsFoVzxs5l5wJEe2Zz
tjT26E5Jn0iseAYZAwL3QFSsj5chwmew4WRs5cb/t/gs70wMvYqP6nyo/2pCNu+T
6AkrcjOOyW7qQZVaY+GCrK5eJydmhMHl5tyuTkXFx2c3HktoLaxKbXiFZcbGT0G2
rI8y19/6PgoBvW5U4PoufdTcDvKZarGajemy40qGZJGscw2vfoMLHhL3vmERacbg
CFcURdU3PzdntsOgJOdT7PcgDVyxgHcew9hpg1vyDhneRuFCr8jDC8KYN9dtAUdT
ODw4bttkJItEbX8zgag2284ZvbJsF65v+a8D20PQUQKBgQDFEmlSfx9W4luZJqcQ
d9qhwTykW68SK17fCNITnx+LToNe0KstainEep6DOEYzt3tKTbkCIbTfeVhOMqRo
YwgZfV045kUZ22CArZ7FBYaut/G2yOPAg5mVpkeIpjC1rK2A9J6zMlwiJ3E75G0J
2nVo+PKdwcnB7LJPuXiSCBBOkQKBgQC1b8ycLWpK+XZAGeohzpxz8qInlHcgLyYC
J0y+kNIOWT1oxqT5iJGA00Rdy+PtV8T5FLmoraavmB7ws41M/6XfJx1JkBAEzx8r
r4llHTe8zZ5MRnxAjArZvAvESZYbzCqwPJTjGvH8qfNhFiQIdrsuw61wCOC6ZNSj
vgHkV5pGyQKBgA25XoRUPgZ69Q4RVwkaj6s8HdEEYYjOZGj74EVli3jUGun7djBP
eGEqeOeCf8ESQg/God+4ITR+6ttnQ3PRkbrUtC1GPAG0+V98t9XYsKxyOu8Txmid
wZBeaBToHfRI9jxIzNSF6UynmoclPUK2Z/7Ld3ntCPPsW+6ZaAAjd59BAoGAZDOk
SrSCOXngJrKpLZaPrTFZAIbr62hek13k8nHEsIv0cEMUpYMY6I7E+RA7hr6sV+ts
RY3xupRGsiRXayjdEIrnj9LyJdXFnzjIpoEmYS0luXZL9NHixDEoRnVlY2C0SrSK
fYpKDoJFmV7C87Gu2rrStEcS5Z3+GZg8L0F6QJECgYAVsEa5JTNCMsz9AKo4/FL4
Alfhyf/W0SbJtgiBeBLJDoEu3+cY6CJ79hndhJFCvcc+aWQAQVZXxscbORoLuoDb
+aJVaQhaniN9sxwd2S6TxzfoTr6HGmsoAYyrKcDi7wSVKfj4PH1P39qRLXVWuWRJ
YSl1QnrMvJj2mvDWk5nntw==
-----END PRIVATE KEY-----
KEY;

    private const RSA_PRIVATE_KEY_ENV = 'LUMINAL_OPEN_API_RSA_PRIVATE_KEY';

    protected function setUp(): void
    {
        parent::setUp();
        fwrite(STDOUT, sprintf('[START] %s::%s%s', static::class, $this->name(), PHP_EOL));
    }

    protected function tearDown(): void
    {
        fwrite(STDOUT, sprintf('[END] %s::%s%s', static::class, $this->name(), PHP_EOL));
        parent::tearDown();
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::resetFlowState();
        self::startWebhookListener();
    }

    public static function tearDownAfterClass(): void
    {
        self::stopWebhookListener();
        parent::tearDownAfterClass();
    }

    /** Enables pool-based resource selection for the inheriting Sandbox flow. */
    protected static function useCardPoolFlow(): void
    {
        self::$cardPoolFlow = true;
    }

    private static function resetFlowState(): void
    {
        self::$managedClient = null;
        self::$cachedToken = null;
        self::$tokenFlowReady = false;
        self::$cachedSharedAccountId = null;
        self::$cachedCardGroupId = null;
        self::$cachedIssueTaskId = null;
        self::$cachedCardId = null;
        self::$cachedCardBin = null;
        self::$cachedCardPool = null;
        self::$cardPoolFlow = false;
    }

    public function testGetsTokenFromSandbox(): void
    {
        $token = self::fetchToken();
        self::assertNotNull($token->accessToken);
        self::assertNotSame('', $token->accessToken);
        self::$tokenFlowReady = true;
    }

    public function testRefreshesTokenFromSandbox(): void
    {
        self::ensureTokenFlowReady();
        $issued = self::issueToken();
        self::assertNotNull($issued->refreshToken);

        try {
            $refreshed = self::publicClient()->withBearerToken($issued->accessToken ?? '')
                ->auth()->refreshToken(new RefreshTokenRequest($issued->refreshToken));
        } catch (ApiException $exception) {
            if ($exception->httpStatus !== 401
                && $exception->apiCode !== 401
                && !str_contains($exception->getMessage(), 'Account is not logged in')) {
                throw $exception;
            }
            $refreshed = self::fetchToken();
        }
        self::assertNotNull($refreshed);
        self::assertNotSame('', $refreshed?->accessToken);
        self::$cachedToken = $refreshed;
    }

    public function testLogsOutFromSandbox(): void
    {
        self::ensureTokenFlowReady();

        $loggedOut = self::logoutSafely(self::authorizedClient(), 'auth.logout');
        self::assertTrue($loggedOut);

        self::$cachedToken = null;
        self::$tokenFlowReady = false;
    }

    public function testGetsTokenAfterLogoutFromSandbox(): void
    {
        $token = self::fetchToken();
        self::assertNotNull($token->accessToken);
        self::assertNotSame('', $token->accessToken);
        self::$tokenFlowReady = true;
    }

    public function testCreatesFundsAndReadsSharedAccountInSandbox(): void
    {
        $client = self::authorizedClient();
        $binId = self::firstCardBinId();

        $created = self::sharedAccountFixture($client, $binId);
        self::assertNotNull($created);
        $accountId = self::id($created?->memberSharedAccountId, 'memberSharedAccountId');

        try {
            $increased = $client->sharedAccounts()->increase(new SharedAccountBalanceRequest(
                memberSharedAccountId: $accountId,
                amount: self::configured('LUMINAL_OPEN_API_INCREASE_AMOUNT', '1.00'),
            ));
        } catch (ApiException $exception) {
            if ($exception->getMessage() === 'System error') {
            }
            throw $exception;
        }
        self::assertNotNull($increased);
        self::awaitSharedAccountTransaction(
            self::id($increased->sharedAccountTransactionId, 'sharedAccountTransactionId'),
            $accountId,
        );

        $decreased = $client->sharedAccounts()->decrease(new SharedAccountBalanceRequest(
            memberSharedAccountId: $accountId,
            amount: self::configured('LUMINAL_OPEN_API_DECREASE_AMOUNT', '1.00'),
        ));
        self::assertNotNull($decreased);
        self::awaitSharedAccountTransaction(
            self::id($decreased->sharedAccountTransactionId, 'sharedAccountTransactionId'),
            $accountId,
        );
        $details = $client->sharedAccounts()->details(new SharedAccountGetRequest($accountId));
        self::assertNotNull($details);
        self::assertSame((string)$accountId, (string)$details->memberSharedAccountId);
        self::assertSame((string)$binId, (string)$details->cardBinId);
        if (self::$cardPoolFlow) {
            self::assertSame((string)self::currentCardPoolId(), (string)$details->cardPoolId);
        }

        $transactions = $client->sharedAccounts()->transactions(new SharedAccountTransactionsRequest(
            pageNo: 1,
            pageSize: 20,
            memberSharedAccountId: $accountId,
        ));
        self::assertInstanceOf(PageResult::class, $transactions);
        self::assertNotNull($transactions->list);
    }

    public function testIssuesCardsWithProvidedAndPrivateKeySignaturesInSandbox(): void
    {
        $privateKey = self::privateKey();

        $client = self::authorizedClient();
        $binId = self::firstCardBinId();
        $accountId = self::sharedAccountId($client);
        $groupId = self::sharedCardGroupId($client);

        $privateKeyRequest = self::issueRequest($binId, $groupId, $accountId, 'private-key');
        $privateKeyTaskId = self::issueCardTaskId($client, $privateKeyRequest, 'cards.issueWithPrivateKey');
        self::$cachedIssueTaskId = $privateKeyTaskId;
        self::$cachedCardId = self::awaitIssuedCardId($client, $privateKeyTaskId);
        self::assertNotSame('', (string)self::$cachedCardId);
    }

    public function testListsAccountsAndWalletTransactionsFromSandbox(): void
    {
        $client = self::authorizedClient();

        $accounts = $client->accounts()->list(new WalletInfoRequest(
            pageNo: 1,
            pageSize: 20,
            currency: 'USD',
        ));
        self::assertInstanceOf(PageResult::class, $accounts);

        $transactions = $client->transactions()->list(new WalletTransactionRequest(
            pageNo: 1,
            pageSize: 20,
        ));
        self::assertInstanceOf(PageResult::class, $transactions);
        self::assertNotNull($transactions->list);
    }

    public function testListsAndReadsSharedAccountsFromSandbox(): void
    {
        $client = self::authorizedClient();
        $accountId = self::sharedAccountId($client);
        try {
            $accounts = $client->sharedAccounts()->list(new SharedAccountPageRequest(
                pageNo: 1,
                pageSize: 20,
                cardPoolId: self::currentCardPoolId(),
            ));
        } catch (ApiException $exception) {
            if (str_contains($exception->getMessage(), 'Account is not logged in')) {
            }
            throw $exception;
        }
        self::assertInstanceOf(PageResult::class, $accounts);
        self::assertNotNull($accounts->list);
        self::assertTrue(array_filter(
            $accounts->list,
            static fn (mixed $account): bool => is_object($account)
                && (string)($account->memberSharedAccountId ?? '') === (string)$accountId,
        ) !== []);

        $details = $client->sharedAccounts()->details(new SharedAccountGetRequest($accountId));
        self::assertNotNull($details);
        self::assertSame((string)$accountId, (string)$details->memberSharedAccountId);
        self::assertSame((string)self::firstCardBinId(), (string)$details->cardBinId);
        if (self::$cardPoolFlow) {
            self::assertSame((string)self::currentCardPoolId(), (string)$details->cardPoolId);
        }

        $transactions = $client->sharedAccounts()->transactions(new SharedAccountTransactionsRequest(
            pageNo: 1,
            pageSize: 20,
            memberSharedAccountId: $accountId,
        ));
        self::assertInstanceOf(PageResult::class, $transactions);
        self::assertNotNull($transactions->list);
    }

    public function testListsAndReadsCardsFromSandbox(): void
    {
        $client = self::authorizedClient();
        $cardId = self::ensureCardId($client);
        $bins = $client->cards()->bins(new CardBinsRequest(
            pageNo: 1,
            pageSize: 20,
            cardPoolId: self::currentCardPoolId(),
            cardType: self::SHARED_CARD_TYPE,
            cardBin: self::TEST_CARD_BIN,
        ));
        self::assertInstanceOf(PageResult::class, $bins);
        self::assertNotNull($bins->list);
        self::assertTrue(array_filter(
            $bins->list,
            static fn (mixed $bin): bool => is_object($bin)
                && (string)($bin->cardBinId ?? '') === (string)self::firstCardBinId(),
        ) !== []);

        $cards = $client->cards()->list(new MemberCardPageRequest(
            pageNo: 1,
            pageSize: 20,
            memberCardId: $cardId,
            cardType: 'SHARED',
        ));
        self::assertInstanceOf(PageResult::class, $cards);
        self::assertNotNull($cards->list);
        self::assertTrue(array_filter(
            $cards->list,
            static fn (mixed $card): bool => $card instanceof MemberCardResponse
                && (string)($card->memberCardId ?? '') === (string)$cardId,
        ) !== []);

        $cvv = $client->cards()->cvv(new CardIdRequest($cardId));
        self::assertInstanceOf(CardCvvResponse::class, $cvv);
        self::assertNotNull($cvv);
        self::assertSame((string)$cardId, (string)$cvv->memberCardId);
        self::assertNotNull($cvv->cardNo);
        self::assertNotNull($cvv->cvv);
        self::assertNotNull($cvv->expiryDate);

        $transactions = $client->cards()->transactions(new CardTransactionsRequest(
            pageNo: 1,
            pageSize: 20,
            cardType: 'SHARED',
            memberCardId: $cardId,
        ));
        self::assertInstanceOf(PageResult::class, $transactions);
        self::assertNotNull($transactions->list);

        $limit = $client->cards()->limit(new CardIdRequest($cardId));
        self::assertInstanceOf(CardLimitResponse::class, $limit);
        self::assertNotNull($limit);
        self::assertSame((string)$cardId, (string)$limit->memberCardId);
    }

    public function testGetsCardIssueDetailsFromSandbox(): void
    {
        $client = self::authorizedClient();
        $cardId = self::ensureCardId($client);
        $taskId = self::$cachedIssueTaskId;
        if ($taskId === null) {
            self::fail('Card issue task ID was not cached.');
        }
        $details = $client->cards()->issueDetails(new IssueCardDetailsRequest($taskId));
        foreach ($details ?? [] as $detail) {
            if (is_object($detail) && (string)($detail->memberCardId ?? '') === (string)$cardId) {
                self::assertSame('SUCCESS', strtoupper((string)($detail->cardStatus ?? '')));
                return;
            }
        }
        self::fail('Issued card was missing from the Sandbox issue-details response.');
    }

    public function testListsCardGroupsFromSandbox(): void
    {
        try {
            $groups = self::authorizedClient()->cardGroups()->list(new CardGroupRequest(
                pageNo: 1,
                pageSize: 20,
                cardType: 'SHARED'
            ));
        } catch (ApiException $exception) {
            if (str_contains($exception->getMessage(), 'Account is not logged in')) {
            }
            throw $exception;
        }
        self::assertInstanceOf(PageResult::class, $groups);
        self::assertNotNull($groups->list);
        self::assertTrue(array_filter(
            $groups->list,
            static fn (mixed $group): bool => $group instanceof CardGroupResponse
                && (string)($group->cardGroupId ?? '') === (string)self::sharedCardGroupId(self::authorizedClient()),
        ) !== []);
    }

    public function testCreatesUpdatesAndDeletesCardGroupInSandbox(): void
    {
        $client = self::authorizedClient();
        $name = 'sdk-php-' . bin2hex(random_bytes(4));
        try {
            $created = $client->cardGroups()->create(new CardGroupCreateRequest($name, 'SHARED'));
        } catch (ApiException $exception) {
            if (str_contains($exception->getMessage(), 'Account is not logged in')) {
            }
            throw $exception;
        }
        self::assertNotNull($created);
        $groupId = self::id($created?->cardGroupId, 'cardGroupId');

        try {
            $updated = $client->cardGroups()->update(new CardGroupUpdateRequest(
                cardGroupId: $groupId,
                cardGroupName: $name . '-updated',
            ));
            self::assertTrue($updated);
        } finally {
            $deleted = $client->cardGroups()->delete(new CardGroupDeleteRequest($groupId));
            self::assertTrue($deleted);
        }
    }

    public function testModifiesFreezesAndUnfreezesCardInSandbox(): void
    {
        $client = self::authorizedClient();
        $cardId = self::ensureCardId($client);
        self::ensureCardStatus($client, $cardId, 'ACTIVE');

        $modified = $client->cards()->modifyLimit(new CardLimitUpdateRequest(
            memberCardId: $cardId,
            totalLimit: self::configured('LUMINAL_OPEN_API_CARD_LIMIT', '100.00'),
        ));
        self::assertTrue($modified);

        self::ensureCardStatus($client, $cardId, 'FREEZE');
        self::ensureCardStatus($client, $cardId, 'ACTIVE');

        if (self::configured('LUMINAL_OPEN_API_RUN_DESTRUCTIVE_TESTS', 'false') === 'true') {
            $cancelled = $client->cards()->cancel(new CardIdRequest($cardId));
            self::assertTrue($cancelled);
            self::awaitCardStatus($client, $cardId, 'CANCEL');
        }
    }

    private static function publicClient(): Client
    {
        self::credentials();
        return self::newClient();
    }

    private static function newClient(): Client
    {
        return (new Client(self::configured('LUMINAL_OPEN_API_BASE_URL', self::DEFAULT_BASE_URL)))
            ->withLogger(new Logger('luminal-open-api', [
                new StreamHandler('php://stdout', Logger::INFO),
            ]));
    }

    private static function authorizedClient(): Client
    {
        return self::$managedClient ??= self::publicClient()->withTokenProvider(
            static fn(): OAuth2Token => self::fetchToken(),
            2,
            'en',
        );
    }

    private static function issueToken(): OAuth2Token
    {
        $token = self::$cachedToken;
        if (!$token instanceof OAuth2Token || $token->accessToken === null || trim($token->accessToken) === '') {
            $token = self::fetchToken();
        }
        return $token;
    }

    private static function fetchToken(): OAuth2Token
    {
        $credentials = self::credentials();
        $client = self::newClient();
        try {
            $token = $client->auth()->getToken(new AuthTokenRequest($credentials[0], $credentials[1]));
        } catch (ApiException $exception) {
            if (self::networkBlocked($exception)) {
            }
            if (!str_starts_with($exception->getMessage(), 'Luminal API request failed.')) {
                throw $exception;
            }
            usleep(500000);
            $token = $client->auth()->getToken(new AuthTokenRequest($credentials[0], $credentials[1]));
        }
        if ($token === null || $token->accessToken === null || trim($token->accessToken) === '') {
            self::fail('Sandbox did not return an access token.');
        }
        self::$cachedToken = $token;
        return $token;
    }

    private static function ensureTokenFlowReady(): void
    {
        if (!self::$tokenFlowReady) {
            self::fetchToken();
            self::$tokenFlowReady = true;
        }
    }


    /** @return array{0:string,1:string} */
    private static function credentials(): array
    {
        return [self::APP_ID, self::APP_SECRET];
    }

    private static function requireMutations(): void
    {
    }

    private static function configured(string $name, ?string $default = null): ?string
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return $default;
        }
        return $value;
    }

    private static function networkBlocked(ApiException $exception): bool
    {
        return str_contains($exception->getMessage(), 'forbidden by its access permissions')
            || str_contains($exception->getMessage(), 'socket')
            || str_contains($exception->getMessage(), 'operation not permitted');
    }

    private static function privateKey(): string
    {
        $key = self::configured(self::RSA_PRIVATE_KEY_ENV);
        if ($key === null) {
            return self::RSA_PRIVATE_KEY;
        }
        return str_replace('\n', "
", $key);
    }

    private static function issueCardTaskId(Client $client, IssueCardRequest $request, string $name): int|string
    {
        $signatures = [
            RsaSignatures::signCanonicalJson($request, self::privateKey()),
            RsaSignatures::sign(json_encode(get_object_vars($request), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR), self::privateKey()),
        ];

        try {
            $privateKeyTaskId = $client->cards()->issueWithPrivateKey($request, self::privateKey());
            if ($privateKeyTaskId !== null) {
                return $privateKeyTaskId;
            }
        } catch (ApiException $exception) {
            if (!str_contains($exception->getMessage(), 'Signature error')) {
                throw $exception;
            }
        }

        foreach ($signatures as $signature) {
            try {
                $taskId = $client->cards()->issue($request, $signature);
                self::assertNotNull($taskId);
                return $taskId;
            } catch (ApiException $exception) {
                if (!str_contains($exception->getMessage(), 'Signature error')) {
                    throw $exception;
                }
            }
        }

        self::fail('Sandbox rejected both card-issue signature variants.');
        self::assertNotNull($taskId);
        return $taskId;
    }

    private static function issueRequest(int|string $binId, int|string $groupId, int|string $accountId, string $suffix): IssueCardRequest
    {
        return new IssueCardRequest(
            applyCount: 1,
            cardBinId: $binId,
            cardGroupId: $groupId,
            cardName: 'sdk-php-' . $suffix . '-' . bin2hex(random_bytes(3)),
            cardType: self::SHARED_CARD_TYPE,
            memberSharedAccountId: $accountId,
            rechargeAmount: self::configured('LUMINAL_OPEN_API_CARD_RECHARGE_AMOUNT', '1.00'),
        );
    }

    /** Returns the first selected shared-card BIN, resolving it within the selected pool when enabled. */
    private static function firstCardBin(): CardBinResponse
    {
        if (self::$cachedCardBin instanceof CardBinResponse) {
            return self::$cachedCardBin;
        }

        $bins = self::authorizedClient()->cards()->bins(new CardBinsRequest(
            pageNo: 1,
            pageSize: 20,
            cardPoolId: self::currentCardPoolId(),
            cardType: self::SHARED_CARD_TYPE,
            cardBin: self::TEST_CARD_BIN,
        ));
        self::assertNotNull($bins);
        self::assertNotNull($bins->list);
        self::assertNotEmpty($bins->list, 'No shared-card BIN was returned by Sandbox.');

        foreach ($bins->list as $item) {
            if (!$item instanceof CardBinResponse
                || strcasecmp((string)$item->cardType, self::SHARED_CARD_TYPE) !== 0
                || (string)$item->cardBin !== self::TEST_CARD_BIN) {
                continue;
            }
            if (self::$cardPoolFlow && $item->cardPoolId !== null
                && (string)$item->cardPoolId !== (string)self::currentCardPoolId()) {
                continue;
            }
            self::$cachedCardBin = $item;
            break;
        }

        self::assertNotNull(self::$cachedCardBin, 'No matching shared-card BIN was returned by Sandbox.');
        return self::$cachedCardBin;
    }

    private static function firstCardBinId(): int|string
    {
        return self::id(self::firstCardBin()->cardBinId, 'cardBinId');
    }

    /** Selects and caches the first Sandbox card pool for the pool-based flow. */
    protected static function selectedCardPool(): ?CardPoolResponse
    {
        if (!self::$cardPoolFlow) {
            return null;
        }
        if (!self::$cachedCardPool instanceof CardPoolResponse) {
            $pools = self::authorizedClient()->cardPools()->list(new CardPoolRequest());
            self::assertNotNull($pools);
            self::assertNotEmpty($pools, 'No available card pool was returned by Sandbox.');
            foreach ($pools as $pool) {
                if ($pool instanceof CardPoolResponse) {
                    self::$cachedCardPool = $pool;
                    break;
                }
            }
            self::assertNotNull(self::$cachedCardPool, 'No available card pool was returned by Sandbox.');
            self::assertNotNull(self::$cachedCardPool->cardPoolId, 'Selected card pool ID is missing.');
        }
        return self::$cachedCardPool;
    }

    private static function currentCardPoolId(): int|string|null
    {
        return self::$cardPoolFlow
            ? self::id(self::selectedCardPool()?->cardPoolId, 'cardPoolId')
            : null;
    }

    private static function sharedAccountAmount(): string
    {
        return self::$cardPoolFlow
            ? '100.00'
            : (self::configured('LUMINAL_OPEN_API_CREATE_AMOUNT', '100.00') ?? '100.00');
    }

    private static function firstId(?PageResult $page, string $field, string $message): int|string
    {
        $item = self::firstItem($page, $message);
        return self::id($item->{$field} ?? null, $field);
    }

    private static function firstItem(?PageResult $page, string $message): object
    {
        foreach ($page?->list ?? [] as $item) {
            if (is_object($item)) {
                return $item;
            }
        }
        return (object)[];
    }

    private static function id(mixed $value, string $field): int|string
    {
        if (is_int($value) || (is_string($value) && trim($value) !== '')) {
            return $value;
        }
        self::fail($field . ' was missing in the Sandbox response.');
    }

    private static function logoutSafely(Client $client, string $name): bool
    {
        try {
            $result = $client->auth()->logout();
        } catch (ApiException $exception) {
            if (str_contains($exception->getMessage(), 'not logged in') || str_contains($exception->getMessage(), 'already')) {
                $result = true;
            } else {
                throw $exception;
            }
        }
        return (bool)$result;
    }

    private static function awaitSharedAccountTransaction(int|string $transactionId, int|string $accountId): void
    {
        $webhook = self::awaitWebhook(WebhookEventType::SHARE_ACCOUNT_FUND_TRANSACTIONS, $transactionId);
        if ($webhook !== null) {
            $payload = $webhook['payload'] ?? [];
            if ((string)($payload['sharedAccountTransactionId'] ?? '') !== (string)$transactionId) {
                throw new \RuntimeException('Shared-account transaction webhook ID mismatch.');
            }
            $status = strtoupper(trim((string)($payload['status'] ?? '')));
            if ($status === 'SUCCESS') {
                return;
            }
            throw new \RuntimeException('Shared-account transaction failed: ' . $transactionId . '; status=' . ($status ?: 'missing'));
        }

        $status = null;
        $transactions = self::authorizedClient()->sharedAccounts()->transactions(new SharedAccountTransactionsRequest(
            pageNo: 1,
            pageSize: 1,
            sharedAccountTransactionId: $transactionId,
            memberSharedAccountId: $accountId,
        ));
        foreach ($transactions?->list ?? [] as $transaction) {
            if (!is_object($transaction)) {
                continue;
            }
            $status = strtoupper(trim((string)($transaction->status ?? '')));
            if ($status === 'SUCCESS') {
                return;
            }
            if ($status === 'FAIL') {
                throw new \RuntimeException('Shared-account transaction failed: ' . $transactionId);
            }
        }

        throw new \RuntimeException('Shared-account transaction did not complete: ' . $transactionId . '; status=' . ($status ?: 'missing'));
    }

    private static function awaitIssuedCardId(Client $client, int|string $taskId): int|string
    {
        $webhook = self::awaitWebhook(WebhookEventType::CARD_OPEN_STATUS, $taskId);
        $expectedCardId = null;
        if ($webhook !== null) {
            $payload = $webhook['payload'] ?? [];
            if ((string)($payload['cardApplyTaskId'] ?? '') !== (string)$taskId) {
                throw new \RuntimeException('Card-open webhook task ID mismatch.');
            }
            foreach ($payload['list'] ?? [] as $result) {
                if (!is_array($result)) {
                    continue;
                }
                $status = strtoupper(trim((string)($result['cardStatus'] ?? '')));
                if ($status === 'FAIL') {
                    throw new \RuntimeException('Card issue failed: ' . ($result['message'] ?? 'unknown error'));
                }
                if ($status === 'SUCCESS' && ($result['memberCardId'] ?? null) !== null) {
                    $expectedCardId = self::id($result['memberCardId'], 'memberCardId');
                    break;
                }
            }
            if ($expectedCardId === null) {
                throw new \RuntimeException('Card-open webhook contained no successful card.');
            }
        }

        $details = $client->cards()->issueDetails(new IssueCardDetailsRequest($taskId));
        foreach ($details ?? [] as $detail) {
            if (!is_object($detail) || ($detail->memberCardId ?? null) === null) {
                continue;
            }
            $cardId = self::id($detail->memberCardId, 'memberCardId');
            if ($expectedCardId !== null && (string)$cardId !== (string)$expectedCardId) {
                continue;
            }
            $status = strtoupper(trim((string)($detail->cardStatus ?? '')));
            if ($status === 'FAIL') {
                throw new \RuntimeException('Card issue failed: ' . ($detail->message ?? 'unknown error'));
            }
            if ($status === 'SUCCESS') {
                self::awaitCardStatus($client, $cardId, 'ACTIVE');
                return $cardId;
            }
        }

        throw new \RuntimeException('Card issue did not complete: ' . $taskId);
    }

    private static function awaitSharedAccountOpenStatus(Client $client, int|string $accountId): void
    {
        $webhook = self::awaitWebhook(WebhookEventType::SHARED_ACCOUNT_OPEN_STATUS, $accountId);
        if ($webhook !== null) {
            $payload = $webhook['payload'] ?? [];
            if ((string)($payload['memberSharedAccountId'] ?? '') !== (string)$accountId) {
                throw new \RuntimeException('Shared-account webhook ID mismatch.');
            }
            $status = strtoupper(trim((string)($payload['status'] ?? '')));
            if ($status === 'SUCCESS') {
                return;
            }
            throw new \RuntimeException('Shared-account creation failed: ' . $accountId . '; status=' . ($status ?: 'missing'));
        }

        $details = $client->sharedAccounts()->details(new SharedAccountGetRequest($accountId));
        if ($details !== null && (string)($details->memberSharedAccountId ?? '') === (string)$accountId) {
            return;
        }
        throw new \RuntimeException('Shared-account creation did not complete: ' . $accountId);
    }

    private static function awaitWebhook(string $event, int|string $correlationId): ?array
    {
        $directory = self::$webhookEventDirectory
            ?? throw new \RuntimeException('Webhook listener is not running.');
        $file = $directory . DIRECTORY_SEPARATOR . hash('sha256', $event . "\0" . (string)$correlationId) . '.json';
        $errorFile = $directory . DIRECTORY_SEPARATOR . 'error.json';
        $deadline = microtime(true) + self::WEBHOOK_TIMEOUT_SECONDS;
        $startedAt = microtime(true);
        $nextLog = microtime(true) + self::WEBHOOK_LOG_INTERVAL_SECONDS;
        $status = null;
        do {
            if (is_file($errorFile)) {
                $error = json_decode((string)file_get_contents($errorFile), true);
                throw new \RuntimeException('Webhook listener rejected an event: ' . ($error['message'] ?? 'unknown error'));
            }
            if (is_file($file)) {
                $data = json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
                if (($data['event'] ?? null) !== $event) {
                    throw new \RuntimeException('Webhook event type mismatch.');
                }
                unlink($file);
                return $data;
            }
            if (microtime(true) >= $nextLog) {
                fwrite(STDERR, sprintf(
                    "Waiting for %s transactionId=%s webhook, elapsed=%ds\n",
                    $event,
                    $correlationId,
                    (int)(microtime(true) - $startedAt),
                ));
                $nextLog += self::WEBHOOK_LOG_INTERVAL_SECONDS;
            }
            usleep(250000);
        } while (microtime(true) < $deadline);
        return null;
    }

    private static function ensureCardStatus(Client $client, int|string $cardId, string $expectedStatus): void
    {
        $status = self::cardStatus($client, $cardId);
        if (strcasecmp($status, $expectedStatus) === 0) {
            return;
        }

        if ($expectedStatus === 'FREEZE') {
            if ($status === 'PRE_UNFREEZE') {
                self::awaitCardStatus($client, $cardId, 'ACTIVE');
            } elseif ($status === 'PRE_FREEZE') {
                self::awaitCardStatus($client, $cardId, 'FREEZE');
                return;
            } elseif ($status !== 'ACTIVE') {
                throw new \RuntimeException('Card cannot be frozen from status ' . $status);
            }
            self::assertTrue($client->cards()->freeze(new CardIdRequest($cardId)));
        } elseif ($expectedStatus === 'ACTIVE') {
            if ($status === 'PRE_FREEZE') {
                self::awaitCardStatus($client, $cardId, 'FREEZE');
            } elseif ($status === 'PRE_UNFREEZE') {
                self::awaitCardStatus($client, $cardId, 'ACTIVE');
                return;
            } elseif ($status !== 'FREEZE') {
                throw new \RuntimeException('Card cannot be unfrozen from status ' . $status);
            }
            self::assertTrue($client->cards()->unfreeze(new CardIdRequest($cardId)));
        } else {
            throw new \InvalidArgumentException('Unsupported card status: ' . $expectedStatus);
        }

        self::awaitCardStatus($client, $cardId, $expectedStatus);
    }

    private static function awaitCardStatus(Client $client, int|string $cardId, string $expectedStatus): void
    {
        $webhook = self::awaitWebhook(WebhookEventType::CARD_STATUS, $cardId);
        if ($webhook !== null) {
            $payload = $webhook['payload'] ?? [];
            if ((string)($payload['memberCardId'] ?? '') !== (string)$cardId) {
                throw new \RuntimeException('Card status webhook ID mismatch.');
            }
            $status = strtoupper(trim((string)($payload['cardStatus'] ?? '')));
            if (strcasecmp($status, $expectedStatus) === 0) {
                return;
            }
            throw new \RuntimeException('Card ' . $cardId . ' webhook status=' . $status
                . '; expected=' . $expectedStatus);
        }

        $status = self::cardStatus($client, $cardId);
        if (strcasecmp($status, $expectedStatus) !== 0) {
            throw new \RuntimeException('Card ' . $cardId . ' did not reach status ' . $expectedStatus . '; current status=' . $status);
        }
    }

    private static function cardStatus(Client $client, int|string $cardId): string
    {
        $cards = $client->cards()->list(new MemberCardPageRequest(
            pageNo: 1,
            pageSize: 1,
            memberCardId: $cardId,
            cardType: 'SHARED',
        ));
        foreach ($cards?->list ?? [] as $card) {
            if (is_object($card) && (string)($card->memberCardId ?? '') === (string)$cardId) {
                $status = trim((string)($card->status ?? ''));
                if ($status !== '') {
                    return strtoupper($status);
                }
            }
        }
        throw new \RuntimeException('Card not found: ' . $cardId);
    }

    private static function ensureCardId(Client $client): int|string
    {
        if (self::$cachedCardId !== null) {
            return self::$cachedCardId;
        }

        $binId = self::firstCardBinId();
        $accountId = self::sharedAccountId($client);
        $groupId = self::sharedCardGroupId($client);
        $taskId = self::issueOnce($client, self::issueRequest($binId, $groupId, $accountId, 'auto'));
        self::$cachedCardId = self::awaitIssuedCardId($client, $taskId);
        return self::$cachedCardId;
    }

    private static function sharedAccountId(Client $client): int|string
    {
        if (self::$cachedSharedAccountId !== null) {
            return self::$cachedSharedAccountId;
        }
        $binId = self::firstCardBinId();
        $created = self::sharedAccountFixture($client, $binId);
        self::$cachedSharedAccountId = self::id($created?->memberSharedAccountId, 'memberSharedAccountId');
        return self::$cachedSharedAccountId;
    }

    private static function sharedCardGroupId(Client $client): int|string
    {
        if (self::$cachedCardGroupId !== null) {
            return self::$cachedCardGroupId;
        }
        $name = 'sdk-php-group-' . bin2hex(random_bytes(4));
        $created = $client->cardGroups()->create(new CardGroupCreateRequest($name, 'SHARED'));
        self::$cachedCardGroupId = self::id($created?->cardGroupId, 'cardGroupId');
        return self::$cachedCardGroupId;
    }

    private static function sharedAccountFixture(Client $client, int|string $binId): object
    {
        if (self::$cachedSharedAccountId !== null) {
            return (object)['memberSharedAccountId' => self::$cachedSharedAccountId];
        }
        $name = 'sdk-php-' . bin2hex(random_bytes(4));
        $request = self::$cardPoolFlow
            ? new CreateSharedAccountRequest(
                cardPoolId: self::currentCardPoolId(),
                rechargeAmount: self::sharedAccountAmount(),
                accountName: $name,
            )
            : new CreateSharedAccountRequest(
                cardBinId: $binId,
                rechargeAmount: self::sharedAccountAmount(),
                accountName: $name,
            );
        try {
            $created = $client->sharedAccounts()->create($request);
        } catch (ApiException $exception) {
            if ($exception->getMessage() === 'System error') {
                usleep(500000);
                $created = $client->sharedAccounts()->create($request);
            } else {
                throw $exception;
            }
        }
        $accountId = self::id($created?->memberSharedAccountId, 'memberSharedAccountId');
        self::awaitSharedAccountOpenStatus($client, $accountId);
        self::$cachedSharedAccountId = $accountId;
        return $created;
    }

    /** Creates an additional shared account with a BIN explicitly belonging to the selected card pool. */
    protected static function createSharedAccountWithCardBinAndCardPool(): int|string
    {
        $client = self::authorizedClient();
        $poolId = self::id(self::currentCardPoolId(), 'cardPoolId');
        $bins = $client->cards()->bins(new CardBinsRequest(
            pageNo: 1,
            pageSize: 20,
            cardPoolId: $poolId,
            cardType: self::SHARED_CARD_TYPE,
            cardBin: self::TEST_CARD_BIN,
        ));
        self::assertNotNull($bins);
        self::assertNotNull($bins->list);
        self::assertNotEmpty($bins->list, 'No shared-card BIN was returned by Sandbox.');

        $selectedBin = null;
        foreach ($bins->list as $item) {
            if (!$item instanceof CardBinResponse
                || strcasecmp((string)$item->cardType, self::SHARED_CARD_TYPE) !== 0
                || (string)$item->cardBin !== self::TEST_CARD_BIN
                || $item->cardPoolId === null
                || (string)$item->cardPoolId !== (string)$poolId) {
                continue;
            }
            $selectedBin = $item;
            break;
        }
        self::assertNotNull($selectedBin, 'Selected card BIN does not belong to the selected card pool.');
        $binId = self::id($selectedBin->cardBinId, 'cardBinId');

        $created = $client->sharedAccounts()->create(new CreateSharedAccountRequest(
            cardBinId: $binId,
            cardPoolId: $poolId,
            rechargeAmount: self::sharedAccountAmount(),
            accountName: 'sdk-php-bin-pool-' . bin2hex(random_bytes(4)),
        ));
        $accountId = self::id($created?->memberSharedAccountId, 'memberSharedAccountId');
        self::awaitSharedAccountOpenStatus($client, $accountId);
        return $accountId;
    }

    private static function startWebhookListener(): void
    {
        if (!function_exists('proc_open')) {
            throw new \RuntimeException('proc_open is required for webhook integration tests.');
        }
        $host = trim(self::configured('LUMINAL_OPEN_API_WEBHOOK_HOST', self::DEFAULT_WEBHOOK_HOST));
        $port = (int)self::configured('LUMINAL_OPEN_API_WEBHOOK_PORT', (string)self::DEFAULT_WEBHOOK_PORT);
        $path = trim(self::configured('LUMINAL_OPEN_API_WEBHOOK_PATH', self::DEFAULT_WEBHOOK_PATH));
        if ($host === '' || preg_match('/\s|[\/:]/', $host) || $port < 1 || $port > 65535
            || !str_starts_with($path, '/') || preg_match('/[\s?#]/', $path)) {
            throw new \RuntimeException('Invalid webhook listener configuration.');
        }

        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'luminal-php-sdk-webhooks-' . bin2hex(random_bytes(8));
        if (!mkdir($directory, 0700) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create webhook event directory.');
        }
        self::$webhookEventDirectory = $directory;
        $key = getenv('LUMINAL_OPEN_API_WEBHOOK_PUBLIC_KEY');
        if (!is_string($key) || trim($key) === '') {
            $key = (string)file_get_contents(__DIR__ . '/platform-public-key.pem');
        } elseif (is_file($key) && is_readable($key)) {
            $key = (string)file_get_contents($key);
        } else {
            $key = str_replace('\\n', "\n", $key);
        }
        $keyFile = $directory . DIRECTORY_SEPARATOR . 'public-key.pem';
        file_put_contents($keyFile, $key, LOCK_EX);

        $stdout = $directory . DIRECTORY_SEPARATOR . 'server.log';
        $stderr = $directory . DIRECTORY_SEPARATOR . 'server-error.log';
        $environment = getenv();
        $environment = is_array($environment) ? $environment : [];
        $environment['LUMINAL_OPEN_API_WEBHOOK_EVENT_DIR'] = $directory;
        $environment['LUMINAL_OPEN_API_WEBHOOK_PATH'] = $path;
        $environment['LUMINAL_OPEN_API_WEBHOOK_PUBLIC_KEY_FILE'] = $keyFile;
        $process = proc_open(
            [PHP_BINARY, '-S', $host . ':' . $port, __DIR__ . '/webhook-router.php'],
            [
                0 => ['file', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'r'],
                1 => ['file', $stdout, 'a'],
                2 => ['file', $stderr, 'a'],
            ],
            $pipes,
            dirname(__DIR__, 2),
            $environment,
            ['bypass_shell' => true],
        );
        if (!is_resource($process)) {
            self::stopWebhookListener();
            throw new \RuntimeException('Unable to start webhook listener.');
        }
        self::$webhookProcess = $process;

        $deadline = microtime(true) + 5;
        do {
            $socket = @fsockopen($host === '0.0.0.0' ? '127.0.0.1' : $host, $port, $errorCode, $errorMessage, 0.2);
            if (is_resource($socket)) {
                fclose($socket);
                return;
            }
            usleep(100000);
        } while (microtime(true) < $deadline);
        $log = is_file($stderr) ? trim((string)file_get_contents($stderr)) : '';
        self::stopWebhookListener();
        throw new \RuntimeException('Webhook listener failed to start.' . ($log !== '' ? ' ' . $log : ''));
    }

    private static function stopWebhookListener(): void
    {
        if (is_resource(self::$webhookProcess)) {
            $status = proc_get_status(self::$webhookProcess);
            if ($status['running'] ?? false) {
                proc_terminate(self::$webhookProcess);
                usleep(100000);
            }
            proc_close(self::$webhookProcess);
        }
        self::$webhookProcess = null;
        if (self::$webhookEventDirectory !== null && is_dir(self::$webhookEventDirectory)) {
            foreach (scandir(self::$webhookEventDirectory) ?: [] as $name) {
                $file = self::$webhookEventDirectory . DIRECTORY_SEPARATOR . $name;
                if ($name !== '.' && $name !== '..' && is_file($file)) {
                    unlink($file);
                }
            }
            rmdir(self::$webhookEventDirectory);
        }
        self::$webhookEventDirectory = null;
    }

    private static function issueOnce(Client $client, IssueCardRequest $request): int|string
    {
        if (self::$cachedIssueTaskId !== null) {
            return self::$cachedIssueTaskId;
        }
        self::$cachedIssueTaskId = self::issueCardTaskId($client, $request, 'cards.issue.cached');
        return self::$cachedIssueTaskId;
    }

}
