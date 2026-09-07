<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Integration;

use DateTimeImmutable;
use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\Client;
use Luminal\OpenApiSdk\Model\AuthTokenRequest;
use Luminal\OpenApiSdk\Model\CardBinsRequest;
use Luminal\OpenApiSdk\Model\CardGroupCreateRequest;
use Luminal\OpenApiSdk\Model\CardGroupRequest;
use Luminal\OpenApiSdk\Model\CardHolderCardPageRequest;
use Luminal\OpenApiSdk\Model\CardHolderCreateRequest;
use Luminal\OpenApiSdk\Model\CardHolderModifyRequest;
use Luminal\OpenApiSdk\Model\CardHolderPageRequest;
use Luminal\OpenApiSdk\Model\CardIdRequest;
use Luminal\OpenApiSdk\Model\CardLimitUpdateRequest;
use Luminal\OpenApiSdk\Model\CardTransactionsRequest;
use Luminal\OpenApiSdk\Model\IssueCardDetailsRequest;
use Luminal\OpenApiSdk\Model\IssueCardRequest;
use Luminal\OpenApiSdk\Model\MemberCardPageRequest;
use Luminal\OpenApiSdk\Model\MemberCardRechargeRequest;
use Luminal\OpenApiSdk\Model\MemberCardWithdrawRequest;
use Luminal\OpenApiSdk\Model\OAuth2Token;
use Luminal\OpenApiSdk\Model\PageResult;
use Luminal\OpenApiSdk\Model\RechargeCardOperationRecordRequest;
use Luminal\OpenApiSdk\Model\RechargeCardOperationRecordResponse;
use Luminal\OpenApiSdk\Webhook\WebhookEventType;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
final class RechargeCardSandboxOpenApiIntegrationTest extends TestCase
{
    private const DEFAULT_BASE_URL = 'https://sandbox-openapi.luminalads.com';
    private const DEFAULT_CARD_BIN = '578391';
    private const DEFAULT_WEBHOOK_HOST = '0.0.0.0';
    private const DEFAULT_WEBHOOK_PORT = 18082;
    private const DEFAULT_WEBHOOK_PATH = '/luminal-open-api-webhook';
    private const WEBHOOK_TIMEOUT_SECONDS = 30;
    private const POLL_INTERVAL_MICROSECONDS = 250000;
    private const MAX_WEBHOOK_BODY_BYTES = 1048576;
    private const CURRENT_CARD_TYPE = 'RECHARGE';
    private const LIMIT_OPERATION_TYPE = 'MODIFY_LIMITS';

    private static ?Client $publicClient = null;
    private static ?Client $managedClient = null;
    private static ?OAuth2Token $cachedToken = null;
    private static mixed $cachedCardBin = null;
    private static mixed $cachedCardGroup = null;
    private static mixed $cachedCardholderTemplate = null;
    private static ?array $cachedCardholderCountries = null;
    private static int|string|null $cachedCardholderId = null;
    private static int|string|null $cachedIssueCardholderId = null;
    private static int|string|null $cachedIssueTaskId = null;
    private static int|string|null $cachedCardId = null;
    private static int|string|null $cachedLimitOperationId = null;
    private static int|string|null $cachedRechargeOperationId = null;
    private static int|string|null $cachedWithdrawOperationId = null;
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

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::sandboxEnabled()) {
            self::markTestSkipped(
                'Set LUMINAL_OPEN_API_RECHARGE_APP_ID and LUMINAL_OPEN_API_RECHARGE_APP_SECRET to run Sandbox tests.',
            );
        }
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
        if (self::sandboxEnabled()) {
            self::startWebhookListener();
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::stopWebhookListener();
        parent::tearDownAfterClass();
    }

    public function testGetTokenFromSandbox(): bool
    {
        $token = self::fetchToken();
        self::assertNotNull($token->accessToken);
        self::assertNotSame('', $token->accessToken);
        return true;
    }

    public function testListRechargeCardBinsFromSandbox(): bool
    {
        self::assertNotNull(self::firstRechargeCardBinId());
        return true;
    }

    public function testListCardholderCountriesFromSandbox(): bool
    {
        $country = self::selectedCardholderCountry(self::existingCardholderTemplate());
        self::assertNotNull($country->countryId);
        self::assertNotSame('', $country->areaCode);
        self::assertGreaterThan(0, $country->phoneMaxLength);
        return true;
    }

    public function testCreateCardholderRejectsBlankPhoneFromSandbox(): bool
    {
        $profile = self::cardholderProfile('Invalid blank local phone');
        try {
            self::authorizedClient()->cardHolders()->add(new CardHolderCreateRequest(
                lastName: $profile->lastName,
                firstName: $profile->firstName,
                birthDate: $profile->birthDate,
                mail: $profile->mail,
                phone: '',
                areaCode: $profile->areaCode,
                countryId: $profile->countryId,
                postalCode: $profile->postalCode,
                state: $profile->state,
                city: $profile->city,
                addressLine1: $profile->addressLine1,
                addressLine2: $profile->addressLine2,
            ));
            self::fail('Blank cardholder phone was accepted by the SDK.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('phone must not be blank', $exception->getMessage());
        }
        return true;
    }

    public function testCreateAndQueryCardholderFromSandbox(): bool
    {
        $id = self::ensureCardholderId();
        $detail = self::authorizedClient()->cardHolders()->detail($id);
        self::assertNotNull($detail);
        self::assertSame('Sdk', $detail->lastName);

        $page = self::authorizedClient()->cardHolders()->page(new CardHolderPageRequest(
            pageNo: 1,
            pageSize: 20,
            cardHolderId: $id,
        ));
        self::assertInstanceOf(PageResult::class, $page);
        self::assertTrue(array_reduce(
            $page->list ?? [],
            static fn(bool $found, mixed $item): bool => $found
                || (is_object($item) && (string)($item->cardHolderId ?? '') === (string)$id),
            false,
        ));
        return true;
    }

    public function testModifyCardholderFromSandbox(): bool
    {
        $id = self::ensureCardholderId();
        $before = self::authorizedClient()->cardHolders()->detail($id);
        self::assertNotNull($before);
        $updatedAddressLine2 = 'SDK address updated ' . (string)round(microtime(true) * 1000);
        $modified = false;
        try {
            self::authorizedClient()->cardHolders()->modify(self::modifyRequest(
                $before,
                $before->phone,
                $updatedAddressLine2,
            ));
            $modified = true;
            $after = self::authorizedClient()->cardHolders()->detail($id);
            self::assertSame($updatedAddressLine2, $after?->addressLine2);
        } finally {
            if ($modified) {
                self::authorizedClient()->cardHolders()->modify(self::modifyRequest(
                    $before,
                    $before->phone,
                    $before->addressLine2,
                ));
                self::$cachedCardholderTemplate = self::authorizedClient()->cardHolders()->detail($id);
            }
        }
        return true;
    }

    public function testModifyCardholderRejectsBlankPhoneFromSandbox(): bool
    {
        $before = self::authorizedClient()->cardHolders()->detail(self::ensureCardholderId());
        self::assertNotNull($before);
        try {
            self::authorizedClient()->cardHolders()->modify(self::modifyRequest(
                $before,
                '',
                $before->addressLine2,
            ));
            self::fail('Blank cardholder phone was accepted by the SDK.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('phone must not be blank', $exception->getMessage());
        }
        $after = self::authorizedClient()->cardHolders()->detail($before->cardHolderId);
        self::assertSame($before->countryId, $after?->countryId);
        self::assertSame($before->areaCode, $after?->areaCode);
        self::assertSame($before->phone, $after?->phone);
        self::assertSame($before->addressLine2, $after?->addressLine2);
        return true;
    }

    public function testReuseExistingCardholderForRechargeCardIssueFromSandbox(): bool
    {
        if (!self::supportsCustomCardholder(self::firstRechargeCardBin())) {
            return true;
        }
        $existing = self::existingCardholderTemplate();
        $expected = $existing?->cardHolderId ?? self::ensureCardholderId();
        self::assertSame((string)$expected, (string)self::cardholderIdForIssue());
        return true;
    }

    public function testCreateRechargeCardGroupFromSandbox(): bool
    {
        self::assertNotNull(self::ensureRechargeCardGroupId());
        return true;
    }

    public function testIssueRechargeCardFromSandbox(): bool
    {
        self::assertNotNull(self::issueRechargeCardTaskId());
        return true;
    }

    public function testWaitForRechargeCardOpenFromSandbox(): bool
    {
        self::assertNotNull(self::ensureRechargeCardId());
        return true;
    }

    public function testListRechargeCardsFromSandbox(): bool
    {
        $cardId = self::ensureRechargeCardId();
        $cards = self::authorizedClient()->cards()->list(new MemberCardPageRequest(
            pageNo: 1,
            pageSize: 20,
            memberCardId: $cardId,
            cardType: self::CURRENT_CARD_TYPE,
            cardGroups: [self::ensureRechargeCardGroupId()],
        ));
        self::assertInstanceOf(PageResult::class, $cards);
        self::assertTrue(array_reduce(
            $cards->list ?? [],
            static fn(bool $found, mixed $item): bool => $found
                || (is_object($item) && (string)($item->memberCardId ?? '') === (string)$cardId),
            false,
        ));
        return true;
    }

    public function testListCardsAssociatedWithCardholderFromSandbox(): bool
    {
        if (!self::supportsCustomCardholder(self::firstRechargeCardBin())) {
            return true;
        }
        $cards = self::authorizedClient()->cardHolders()->associatedCards(new CardHolderCardPageRequest(
            pageNo: 1,
            pageSize: 20,
            cardHolderId: self::cardholderIdForIssue(),
            memberCardId: self::ensureRechargeCardId(),
        ));
        self::assertInstanceOf(PageResult::class, $cards);
        self::assertTrue(array_reduce(
            $cards->list ?? [],
            static fn(bool $found, mixed $item): bool => $found
                || (is_object($item) && (string)($item->memberCardId ?? '') === (string)self::ensureRechargeCardId()),
            false,
        ));
        return true;
    }

    public function testGetRechargeCardCvvFromSandbox(): bool
    {
        self::assertNotNull(self::authorizedClient()->cards()->cvv(new CardIdRequest(self::ensureRechargeCardId())));
        return true;
    }

    public function testGetRechargeCardLimitFromSandbox(): bool
    {
        $limit = self::authorizedClient()->cards()->limit(new CardIdRequest(self::ensureRechargeCardId()));
        self::assertNotNull($limit);
        self::assertNotNull($limit->balance);
        return true;
    }

    public function testModifyRechargeCardLimitFromSandbox(): bool
    {
        if (!self::supportsCardLimit(self::firstRechargeCardBin())) {
            return true;
        }
        self::$cachedLimitOperationId = self::authorizedClient()->cards()->modifyLimitAsync(
            new CardLimitUpdateRequest(
                memberCardId: self::ensureRechargeCardId(),
                cardType: self::CURRENT_CARD_TYPE,
                dailyLimit: self::configuredDecimal([
                    'LUMINAL_OPEN_API_RECHARGE_DAILY_LIMIT',
                    'LUMINAL_OPEN_API_DAILY_LIMIT',
                ], '100.00'),
                monthLimit: self::configuredDecimal([
                    'LUMINAL_OPEN_API_RECHARGE_MONTH_LIMIT',
                    'LUMINAL_OPEN_API_MONTH_LIMIT',
                ], '1000.00'),
            ),
        );
        self::assertNotNull(self::$cachedLimitOperationId);
        $result = self::awaitOperation(self::$cachedLimitOperationId, self::LIMIT_OPERATION_TYPE);
        self::assertSame('SUCCESS', strtoupper((string)$result->status));
        return true;
    }

    public function testRechargeCardFromSandbox(): bool
    {
        self::$cachedRechargeOperationId = self::authorizedClient()->cards()->recharge(new MemberCardRechargeRequest(
            memberCardId: self::ensureRechargeCardId(),
            amount: self::configuredDecimal([
                'LUMINAL_OPEN_API_RECHARGE_AMOUNT',
                'LUMINAL_OPEN_API_CARD_RECHARGE_AMOUNT',
            ], '10.00'),
            remark: 'php-sdk Sandbox recharge',
        ));
        self::assertNotNull(self::$cachedRechargeOperationId);
        $result = self::awaitOperation(self::$cachedRechargeOperationId, 'RECHARGE');
        self::assertSame('SUCCESS', strtoupper((string)$result->status));
        return true;
    }

    public function testQueryRechargeOperationRecordFromSandbox(): bool
    {
        $operationId = self::$cachedRechargeOperationId;
        if ($operationId === null) {
            self::markTestSkipped('Recharge operation was not created in this Sandbox run.');
        }
        $result = self::authorizedClient()->cards()->operationRecord(new RechargeCardOperationRecordRequest(
            $operationId,
        ));
        if ($result === null || !self::isFinalStatus($result->status)) {
            self::markTestSkipped('Recharge operation is not in a final state.');
        }
        if (strcasecmp((string)$result->status, 'FAIL') === 0) {
            self::markTestSkipped('Recharge operation failed in Sandbox: ' . ($result->message ?? 'unknown error'));
        }
        self::assertSame('RECHARGE', strtoupper((string)$result->operationType));
        self::assertSame('SUCCESS', strtoupper((string)$result->status));
        return true;
    }

    public function testWithdrawCardFromSandbox(): bool
    {
        self::$cachedWithdrawOperationId = self::authorizedClient()->cards()->withdraw(new MemberCardWithdrawRequest(
            memberCardId: self::ensureRechargeCardId(),
            amount: self::configuredDecimal([
                'LUMINAL_OPEN_API_WITHDRAW_AMOUNT',
                'LUMINAL_OPEN_API_CARD_WITHDRAW_AMOUNT',
            ], '19.90'),
            remark: 'php-sdk Sandbox withdrawal',
        ));
        self::assertNotNull(self::$cachedWithdrawOperationId);
        $result = self::awaitOperation(self::$cachedWithdrawOperationId, 'WITHDRAW');
        self::assertSame('SUCCESS', strtoupper((string)$result->status));
        return true;
    }

    public function testQueryWithdrawOperationRecordFromSandbox(): bool
    {
        $operationId = self::$cachedWithdrawOperationId;
        if ($operationId === null) {
            self::markTestSkipped('Withdraw operation was not created in this Sandbox run.');
        }
        $result = self::authorizedClient()->cards()->operationRecord(new RechargeCardOperationRecordRequest(
            $operationId,
        ));
        if ($result === null || !self::isFinalStatus($result->status)) {
            self::markTestSkipped('Withdraw operation is not in a final state.');
        }
        if (strcasecmp((string)$result->status, 'FAIL') === 0) {
            self::markTestSkipped('Withdraw operation failed in Sandbox: ' . ($result->message ?? 'unknown error'));
        }
        self::assertSame('WITHDRAW', strtoupper((string)$result->operationType));
        self::assertSame('SUCCESS', strtoupper((string)$result->status));
        return true;
    }

    public function testListRechargeCardTransactionsFromSandbox(): bool
    {
        $cardId = self::ensureRechargeCardId();
        $transactions = self::authorizedClient()->cards()->transactions(new CardTransactionsRequest(
            pageNo: 1,
            pageSize: 20,
            cardType: self::CURRENT_CARD_TYPE,
            memberCardId: $cardId,
        ));
        self::assertInstanceOf(PageResult::class, $transactions);
        foreach ($transactions->list ?? [] as $transaction) {
            if (is_object($transaction) && $transaction->memberCardTransactionId !== null) {
                self::assertNotSame('', (string)$transaction->memberCardTransactionId);
            }
        }
        return true;
    }

    public function testFreezeRechargeCardFromSandbox(): bool
    {
        $cardId = self::ensureRechargeCardId();
        self::clearWebhook(WebhookEventType::CARD_STATUS, $cardId);
        self::assertTrue(self::authorizedClient()->cards()->freeze(new CardIdRequest($cardId)));
        self::awaitCardStatus($cardId, 'FREEZE');
        return true;
    }

    public function testUnfreezeRechargeCardFromSandbox(): bool
    {
        $cardId = self::ensureRechargeCardId();
        self::clearWebhook(WebhookEventType::CARD_STATUS, $cardId);
        self::assertTrue(self::authorizedClient()->cards()->unfreeze(new CardIdRequest($cardId)));
        self::awaitCardStatus($cardId, 'ACTIVE');
        return true;
    }

    public function testCancelRechargeCardFromSandbox(): bool
    {
        $cardId = self::ensureRechargeCardId();
        self::clearWebhook(WebhookEventType::CARD_STATUS, $cardId);
        self::assertTrue(self::authorizedClient()->cards()->cancel(new CardIdRequest($cardId)));
        self::awaitCardStatus($cardId, 'CANCEL');
        return true;
    }

    private static function sandboxEnabled(): bool
    {
        return self::configured([
            'LUMINAL_OPEN_API_RECHARGE_APP_ID',
            'LUMINAL_OPEN_API_APP_ID',
        ], self::APP_ID) !== null && self::configured([
            'LUMINAL_OPEN_API_RECHARGE_APP_SECRET',
            'LUMINAL_OPEN_API_APP_SECRET',
        ], self::APP_SECRET) !== null;
    }

    private static function configured(array $names, ?string $default = null): ?string
    {
        foreach ($names as $name) {
            $value = getenv($name);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }
        return $default;
    }

    private static function configuredDecimal(array $names, string $default): string
    {
        return self::configured($names, $default) ?? $default;
    }

    private static function baseUrl(): string
    {
        return self::configured([
            'LUMINAL_OPEN_API_RECHARGE_BASE_URL',
            'LUMINAL_OPEN_API_BASE_URL',
        ], self::DEFAULT_BASE_URL) ?? self::DEFAULT_BASE_URL;
    }

    private static function appId(): string
    {
        return self::configured([
            'LUMINAL_OPEN_API_RECHARGE_APP_ID',
            'LUMINAL_OPEN_API_APP_ID',
        ], self::APP_ID) ?? throw new \RuntimeException('Recharge-card app ID is not configured.');
    }

    private static function appSecret(): string
    {
        return self::configured([
            'LUMINAL_OPEN_API_RECHARGE_APP_SECRET',
            'LUMINAL_OPEN_API_APP_SECRET',
        ], self::APP_SECRET) ?? throw new \RuntimeException('Recharge-card app secret is not configured.');
    }

    private static function publicClient(): Client
    {
        return self::$publicClient ??= self::newClient();
    }

    private static function newClient(): Client
    {
        $client = (new Client(self::baseUrl()))->withLogger(new Logger('luminal-open-api-recharge', [
            new StreamHandler('php://stdout', Logger::INFO),
        ]));
        if (self::configured(['LUMINAL_OPEN_API_LOG_HTTP'], '1') === '0') {
            $client = $client->withHttpLogging(false);
        }
        return $client;
    }

    private static function authorizedClient(): Client
    {
        return self::$managedClient ??= self::publicClient()->withTokenProvider(
            static fn(): OAuth2Token => self::fetchToken(),
            1,
            self::configured(['LUMINAL_OPEN_API_RECHARGE_LOCALE', 'LUMINAL_OPEN_API_LOCALE'], 'en') ?? 'en',
        );
    }

    private static function fetchToken(): OAuth2Token
    {
        $token = self::publicClient()->auth()->getToken(new AuthTokenRequest(
            self::appId(),
            self::appSecret(),
        ));
        if (!$token instanceof OAuth2Token || $token->accessToken === null || trim($token->accessToken) === '') {
            throw new \RuntimeException('Sandbox did not return an access token.');
        }
        self::$cachedToken = $token;
        return $token;
    }

    private static function firstRechargeCardBin(): object
    {
        if (self::$cachedCardBin !== null) {
            return self::$cachedCardBin;
        }
        $expectedBin = self::configured([
            'LUMINAL_OPEN_API_RECHARGE_CARD_BIN',
            'LUMINAL_OPEN_API_CARD_BIN',
        ], self::DEFAULT_CARD_BIN) ?? self::DEFAULT_CARD_BIN;
        $page = self::authorizedClient()->cards()->bins(new CardBinsRequest(
            pageNo: 1,
            pageSize: 50,
            cardType: self::CURRENT_CARD_TYPE,
            cardBin: $expectedBin,
        ));
        self::assertInstanceOf(PageResult::class, $page);
        foreach ($page->list ?? [] as $item) {
            if (is_object($item) && (string)($item->cardBin ?? '') === $expectedBin) {
                self::$cachedCardBin = $item;
                self::assertNotNull($item->cardBinId);
                return $item;
            }
        }
        throw new \RuntimeException('Recharge-card BIN not found: ' . $expectedBin);
    }

    private static function firstRechargeCardBinId(): int|string
    {
        return self::identifier(self::firstRechargeCardBin()->cardBinId ?? null, 'cardBinId');
    }

    private static function supportsCustomCardholder(object $cardBin): bool
    {
        return (int)($cardBin->customCardholder ?? 0) === 1;
    }

    private static function supportsCardLimit(object $cardBin): bool
    {
        return (int)($cardBin->canLimit ?? 0) === 1;
    }

    private static function ensureRechargeCardGroupId(): int|string
    {
        if (self::$cachedCardGroup !== null) {
            return self::identifier(self::$cachedCardGroup->cardGroupId ?? null, 'cardGroupId');
        }
        $created = self::authorizedClient()->cardGroups()->create(new CardGroupCreateRequest(
            'sdk-php-sandbox-recharge-' . bin2hex(random_bytes(6)),
            self::CURRENT_CARD_TYPE,
        ));
        self::assertNotNull($created);
        self::$cachedCardGroup = $created;
        return self::identifier($created->cardGroupId ?? null, 'cardGroupId');
    }

    private static function cardholderCountries(): array
    {
        if (self::$cachedCardholderCountries !== null) {
            return self::$cachedCardholderCountries;
        }
        $countries = self::authorizedClient()->cardHolders()->countries();
        if (!is_array($countries) || $countries === []) {
            throw new \RuntimeException('cardHolders().countries() returned no countries.');
        }
        return self::$cachedCardholderCountries = $countries;
    }

    private static function existingCardholderTemplate(): ?object
    {
        if (self::$cachedCardholderTemplate !== null) {
            return self::$cachedCardholderTemplate;
        }
        $page = self::authorizedClient()->cardHolders()->page(new CardHolderPageRequest(
            pageNo: 1,
            pageSize: 1,
        ));
        foreach ($page?->list ?? [] as $item) {
            if (is_object($item) && ($item->cardHolderId ?? null) !== null) {
                return self::$cachedCardholderTemplate = self::authorizedClient()->cardHolders()->detail(
                    self::identifier($item->cardHolderId, 'cardHolderId'),
                );
            }
        }
        return null;
    }

    private static function selectedCardholderCountry(?object $template): object
    {
        $countries = self::cardholderCountries();
        $configuredId = self::configured([
            'LUMINAL_OPEN_API_CARD_HOLDER_COUNTRY_ID',
            'LUMINAL_OPEN_API_RECHARGE_CARD_HOLDER_COUNTRY_ID',
        ]);
        $configuredArea = self::configured([
            'LUMINAL_OPEN_API_CARD_HOLDER_AREA_CODE',
            'LUMINAL_OPEN_API_RECHARGE_CARD_HOLDER_AREA_CODE',
        ]);
        if ($configuredId !== null) {
            foreach ($countries as $country) {
                if (is_object($country) && (string)($country->countryId ?? '') === $configuredId) {
                    if ($configuredArea !== null && (string)($country->areaCode ?? '') !== $configuredArea) {
                        throw new \RuntimeException('Configured cardholder area code does not match country.');
                    }
                    return $country;
                }
            }
            throw new \RuntimeException('Configured cardholder country ID was not returned by the API.');
        }
        if ($configuredArea !== null) {
            $matches = array_values(array_filter(
                $countries,
                static fn(mixed $country): bool => is_object($country)
                    && (string)($country->areaCode ?? '') === $configuredArea,
            ));
            if (count($matches) !== 1) {
                throw new \RuntimeException('Configured cardholder area code must match exactly one country.');
            }
            return $matches[0];
        }
        if ($template?->countryId !== null) {
            foreach ($countries as $country) {
                if (is_object($country) && (string)($country->countryId ?? '') === (string)$template->countryId) {
                    return $country;
                }
            }
        }
        foreach ($countries as $country) {
            if (is_object($country) && strcasecmp((string)($country->countryCode ?? ''), 'HK') === 0) {
                return $country;
            }
        }
        $first = $countries[0] ?? null;
        if (!is_object($first)) {
            throw new \RuntimeException('Cardholder country response contains no objects.');
        }
        return $first;
    }

    private static function localPhone(object $country, int $suffix): string
    {
        $length = (int)($country->phoneMaxLength ?? 0);
        if ($length < 1) {
            throw new \RuntimeException('Cardholder country has an invalid phoneMaxLength.');
        }
        $seed = (string)($suffix % 1000000);
        $phone = '5';
        while (strlen($phone) < $length) {
            $phone .= $seed;
        }
        return substr($phone, 0, $length);
    }

    private static function cardholderProfile(string $addressLine2): CardHolderCreateRequest
    {
        $suffix = (int)(microtime(true) * 1000) % 1000000;
        $template = self::existingCardholderTemplate();
        $country = self::selectedCardholderCountry($template);
        return new CardHolderCreateRequest(
            lastName: 'Sdk',
            firstName: 'Sandbox' . self::alphabeticSuffix($suffix),
            birthDate: new DateTimeImmutable('1990-01-15'),
            mail: 'sdk-recharge-' . $suffix . '@example.com',
            phone: self::localPhone($country, $suffix),
            areaCode: $country->areaCode,
            countryId: $country->countryId,
            postalCode: $template?->postalCode ?? '10001',
            state: $template?->state ?? 'New York',
            city: $template?->city ?? 'New York',
            addressLine1: $template?->addressLine1 ?? '350 Fifth Avenue',
            addressLine2: $addressLine2,
        );
    }

    private static function alphabeticSuffix(int $value): string
    {
        $remaining = $value % (26 * 26 * 26);
        $suffix = '';
        for ($index = 0; $index < 3; $index++) {
            $suffix .= chr(ord('A') + ($remaining % 26));
            $remaining = intdiv($remaining, 26);
        }
        return $suffix;
    }

    private static function ensureCardholderId(): int|string
    {
        if (self::$cachedCardholderId === null) {
            $created = self::authorizedClient()->cardHolders()->add(
                self::cardholderProfile('Created by PHP SDK Sandbox test'),
            );
            self::$cachedCardholderId = self::identifier($created, 'cardHolderId');
            self::$cachedCardholderTemplate = self::authorizedClient()->cardHolders()->detail(
                self::$cachedCardholderId,
            );
        }
        return self::$cachedCardholderId;
    }

    private static function cardholderIdForIssue(): int|string
    {
        if (self::$cachedIssueCardholderId === null) {
            $existing = self::existingCardholderTemplate();
            self::$cachedIssueCardholderId = $existing?->cardHolderId ?? self::ensureCardholderId();
        }
        return self::$cachedIssueCardholderId;
    }

    private static function modifyRequest(object $profile, ?string $phone, ?string $addressLine2): CardHolderModifyRequest
    {
        return new CardHolderModifyRequest(
            cardHolderId: $profile->cardHolderId,
            lastName: $profile->lastName,
            firstName: $profile->firstName,
            birthDate: $profile->birthDate,
            mail: $profile->mail,
            phone: $phone,
            areaCode: $profile->areaCode,
            countryId: $profile->countryId,
            postalCode: $profile->postalCode,
            state: $profile->state,
            city: $profile->city,
            addressLine1: $profile->addressLine1,
            addressLine2: $addressLine2,
        );
    }

    private static function privateKey(): string
    {
        $value = self::configured([
            'LUMINAL_OPEN_API_RECHARGE_PRIVATE_KEY',
            'LUMINAL_OPEN_API_PRIVATE_KEY',
        ]);
        if ($value !== null && str_contains($value, '-----BEGIN')) {
            return str_replace('\\n', "\n", $value);
        }
        $path = self::configured([
            'LUMINAL_OPEN_API_RECHARGE_PRIVATE_KEY_PATH',
            'LUMINAL_OPEN_API_PRIVATE_KEY_PATH',
        ]) ?? $value;
        if ($path !== null && is_readable($path)) {
            return (string)file_get_contents($path);
        }
        return self::RSA_PRIVATE_KEY;
    }

    private static function issueRechargeCardTaskId(): int|string
    {
        if (self::$cachedIssueTaskId !== null) {
            return self::$cachedIssueTaskId;
        }
        $configuredTask = self::configured([
            'LUMINAL_OPEN_API_RECHARGE_ISSUE_TASK_ID',
            'LUMINAL_OPEN_API_ISSUE_TASK_ID',
        ]);
        if ($configuredTask !== null) {
            return self::$cachedIssueTaskId = $configuredTask;
        }
        $bin = self::firstRechargeCardBin();
        $request = new IssueCardRequest(
            applyCount: 1,
            cardBinId: self::firstRechargeCardBinId(),
            cardGroupId: self::ensureRechargeCardGroupId(),
            cardName: self::configured([
                'LUMINAL_OPEN_API_RECHARGE_ISSUE_CARD_NAME',
                'LUMINAL_OPEN_API_ISSUE_CARD_NAME',
            ], 'sdk-php-sandbox-recharge-' . bin2hex(random_bytes(6))),
            cardType: self::CURRENT_CARD_TYPE,
            dailyLimit: self::configuredDecimal([
                'LUMINAL_OPEN_API_RECHARGE_DAILY_LIMIT',
                'LUMINAL_OPEN_API_DAILY_LIMIT',
            ], '100.00'),
            monthLimit: self::configuredDecimal([
                'LUMINAL_OPEN_API_RECHARGE_MONTH_LIMIT',
                'LUMINAL_OPEN_API_MONTH_LIMIT',
            ], '1000.00'),
            rechargeAmount: self::configuredDecimal([
                'LUMINAL_OPEN_API_RECHARGE_ISSUE_AMOUNT',
                'LUMINAL_OPEN_API_ISSUE_AMOUNT',
            ], '10.00'),
            cardHolderId: self::supportsCustomCardholder($bin) ? self::cardholderIdForIssue() : null,
        );
        $taskId = self::authorizedClient()->cards()->issue($request, self::privateKey());
        return self::$cachedIssueTaskId = self::identifier($taskId, 'issueTaskId');
    }

    private static function ensureRechargeCardId(): int|string
    {
        if (self::$cachedCardId !== null) {
            return self::$cachedCardId;
        }
        $configuredCard = self::configured([
            'LUMINAL_OPEN_API_RECHARGE_CARD_ID',
            'LUMINAL_OPEN_API_CARD_ID',
        ]);
        if ($configuredCard !== null) {
            return self::$cachedCardId = $configuredCard;
        }
        $taskId = self::issueRechargeCardTaskId();
        $webhook = self::awaitWebhook(WebhookEventType::CARD_OPEN_STATUS, $taskId);
        if ($webhook !== null) {
            self::$cachedCardId = self::cardIdFromOpenWebhook($taskId, $webhook['payload'] ?? []);
        } else {
            self::$cachedCardId = self::issuedCardIdFromDetails($taskId);
        }
        return self::$cachedCardId;
    }

    private static function cardIdFromOpenWebhook(int|string $taskId, mixed $payload): int|string
    {
        if (!is_array($payload) || (string)($payload['cardApplyTaskId'] ?? '') !== (string)$taskId) {
            throw new \RuntimeException('CARD_OPEN_STATUS webhook task ID mismatch.');
        }
        foreach ($payload['list'] ?? [] as $result) {
            if (!is_array($result)) {
                continue;
            }
            $status = strtoupper(trim((string)($result['cardStatus'] ?? '')));
            if ($status === 'FAIL') {
                throw new \RuntimeException('Recharge-card opening failed: ' . ($result['message'] ?? 'unknown error'));
            }
            if ($status === 'SUCCESS' && ($result['memberCardId'] ?? null) !== null) {
                return self::identifier($result['memberCardId'], 'memberCardId');
            }
        }
        throw new \RuntimeException('CARD_OPEN_STATUS webhook contains no successful card.');
    }

    private static function issuedCardIdFromDetails(int|string $taskId): int|string
    {
        $deadline = microtime(true) + self::WEBHOOK_TIMEOUT_SECONDS;
        do {
            $details = self::authorizedClient()->cards()->issueDetails(new IssueCardDetailsRequest($taskId));
            foreach ($details ?? [] as $detail) {
                if (!is_object($detail) || ($detail->memberCardId ?? null) === null) {
                    continue;
                }
                $status = strtoupper(trim((string)($detail->cardStatus ?? '')));
                if ($status === 'FAIL') {
                    throw new \RuntimeException('Recharge-card opening failed: ' . ($detail->message ?? 'unknown error'));
                }
                if ($status !== '' && $status !== 'APPLYING') {
                    return self::identifier($detail->memberCardId, 'memberCardId');
                }
            }
            usleep(self::POLL_INTERVAL_MICROSECONDS);
        } while (microtime(true) < $deadline);
        throw new \RuntimeException('CARD_OPEN_STATUS timed out for task ' . $taskId);
    }

    private static function awaitOperation(int|string $operationId, string $expectedType): RechargeCardOperationRecordResponse
    {
        $event = match ($expectedType) {
            'RECHARGE' => WebhookEventType::CARD_RECHARGE_STATUS,
            'WITHDRAW' => WebhookEventType::CARD_WITHDRAW_STATUS,
            self::LIMIT_OPERATION_TYPE => WebhookEventType::CARD_LIMIT_STATUS,
            default => throw new \InvalidArgumentException('Unsupported recharge operation type: ' . $expectedType),
        };
        $webhook = self::awaitWebhook($event, $operationId);
        if ($webhook !== null) {
            $payload = $webhook['payload'] ?? [];
            if (self::isFinalStatus(is_array($payload) ? ($payload['status'] ?? null) : null)) {
                try {
                    self::validateTransfer($operationId, $expectedType, $payload);
                    return self::operationFromWebhook($payload);
                } catch (\Throwable $exception) {
                    self::markTestSkipped($expectedType . ' operation failed in Sandbox: ' . $exception->getMessage());
                }
            }
        }

        $deadline = microtime(true) + self::WEBHOOK_TIMEOUT_SECONDS;
        do {
            $result = self::authorizedClient()->cards()->operationRecord(
                new RechargeCardOperationRecordRequest($operationId),
            );
            if ($result !== null && self::isFinalStatus($result->status)) {
                try {
                    self::validateTransferObject($operationId, $expectedType, $result);
                    return $result;
                } catch (\Throwable $exception) {
                    self::markTestSkipped($expectedType . ' operation failed in Sandbox: ' . $exception->getMessage());
                }
            }
            usleep(self::POLL_INTERVAL_MICROSECONDS);
        } while (microtime(true) < $deadline);
        self::markTestSkipped($expectedType . ' operation did not reach a final state.');
    }

    private static function validateTransfer(int|string $operationId, string $expectedType, mixed $payload): void
    {
        if (!is_array($payload)) {
            throw new \RuntimeException('Recharge operation webhook payload is invalid.');
        }
        if ((string)($payload['memberCardOperationRecordId'] ?? '') !== (string)$operationId) {
            throw new \RuntimeException('Recharge operation webhook ID mismatch.');
        }
        if (strcasecmp((string)($payload['cardType'] ?? ''), self::CURRENT_CARD_TYPE) !== 0) {
            throw new \RuntimeException('Recharge operation webhook card type mismatch.');
        }
        if ((string)($payload['memberCardId'] ?? '') !== (string)self::ensureRechargeCardId()) {
            throw new \RuntimeException('Recharge operation webhook card ID mismatch.');
        }
        if (strcasecmp((string)($payload['operationType'] ?? ''), $expectedType) !== 0) {
            throw new \RuntimeException('Recharge operation webhook type mismatch.');
        }
        if (strcasecmp((string)($payload['status'] ?? ''), 'FAIL') === 0) {
            throw new \RuntimeException($expectedType . ' operation failed: ' . ($payload['message'] ?? 'unknown error'));
        }
    }

    private static function validateTransferObject(
        int|string $operationId,
        string $expectedType,
        RechargeCardOperationRecordResponse $result,
    ): void {
        if ((string)$result->memberCardOperationRecordId !== (string)$operationId
            || strcasecmp((string)$result->cardType, self::CURRENT_CARD_TYPE) !== 0
            || (string)$result->memberCardId !== (string)self::ensureRechargeCardId()
            || strcasecmp((string)$result->operationType, $expectedType) !== 0) {
            throw new \RuntimeException('Recharge operation record fields do not match the request.');
        }
        if (strcasecmp((string)$result->status, 'FAIL') === 0) {
            throw new \RuntimeException($expectedType . ' operation failed: ' . ($result->message ?? 'unknown error'));
        }
    }

    private static function operationFromWebhook(array $payload): RechargeCardOperationRecordResponse
    {
        return new RechargeCardOperationRecordResponse(
            memberCardOperationRecordId: $payload['memberCardOperationRecordId'] ?? null,
            memberCardId: $payload['memberCardId'] ?? null,
            cardType: $payload['cardType'] ?? null,
            operationType: $payload['operationType'] ?? null,
            amount: $payload['amount'] ?? null,
            currencyCode: $payload['currencyCode'] ?? null,
            balance: $payload['balance'] ?? null,
            status: $payload['status'] ?? null,
            message: $payload['message'] ?? null,
            updateTime: $payload['updateTime'] ?? null,
        );
    }

    private static function isFinalStatus(?string $status): bool
    {
        return in_array(strtoupper((string)$status), ['SUCCESS', 'FAIL'], true);
    }

    private static function clearWebhook(string $event, int|string $correlationId): void
    {
        if (self::$webhookEventDirectory === null) {
            return;
        }
        $file = self::$webhookEventDirectory . DIRECTORY_SEPARATOR
            . hash('sha256', $event . "\0" . (string)$correlationId) . '.json';
        if (is_file($file)) {
            unlink($file);
        }
    }

    private static function awaitCardStatus(int|string $cardId, string $expectedStatus): void
    {
        $deadline = microtime(true) + self::WEBHOOK_TIMEOUT_SECONDS;
        do {
            $webhook = self::awaitWebhook(WebhookEventType::CARD_STATUS, $cardId, 0.0);
            if ($webhook !== null) {
                $status = strtoupper(trim((string)(($webhook['payload'] ?? [])['cardStatus'] ?? '')));
                if ($status === strtoupper($expectedStatus)) {
                    return;
                }
            }
            if (strcasecmp(self::cardStatus($cardId), $expectedStatus) === 0) {
                return;
            }
            usleep(self::POLL_INTERVAL_MICROSECONDS);
        } while (microtime(true) < $deadline);
        throw new \RuntimeException('Card ' . $cardId . ' did not reach status ' . $expectedStatus . '.');
    }

    private static function cardStatus(int|string $cardId): string
    {
        $cards = self::authorizedClient()->cards()->list(new MemberCardPageRequest(
            pageNo: 1,
            pageSize: 1,
            memberCardId: $cardId,
            cardType: self::CURRENT_CARD_TYPE,
        ));
        foreach ($cards?->list ?? [] as $card) {
            if (is_object($card) && (string)($card->memberCardId ?? '') === (string)$cardId) {
                $status = trim((string)($card->status ?? ''));
                if ($status !== '') {
                    return strtoupper($status);
                }
            }
        }
        throw new \RuntimeException('Recharge card not found: ' . $cardId);
    }

    private static function awaitWebhook(string $event, int|string $correlationId, ?float $timeout = null): ?array
    {
        $directory = self::$webhookEventDirectory
            ?? throw new \RuntimeException('Recharge-card webhook listener is not running.');
        $file = $directory . DIRECTORY_SEPARATOR
            . hash('sha256', $event . "\0" . (string)$correlationId) . '.json';
        $errorFile = $directory . DIRECTORY_SEPARATOR . 'error.json';
        $seconds = $timeout ?? self::WEBHOOK_TIMEOUT_SECONDS;
        $deadline = microtime(true) + max(0.0, $seconds);
        do {
            if (is_file($errorFile)) {
                $error = json_decode((string)file_get_contents($errorFile), true);
                throw new \RuntimeException(
                    'Webhook listener rejected an event: ' . (is_array($error) ? ($error['message'] ?? 'unknown error') : 'unknown error'),
                );
            }
            if (is_file($file)) {
                $data = json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($data) || ($data['event'] ?? null) !== $event) {
                    throw new \RuntimeException('Webhook event type mismatch.');
                }
                unlink($file);
                return $data;
            }
            if ($seconds <= 0.0) {
                return null;
            }
            usleep(self::POLL_INTERVAL_MICROSECONDS);
        } while (microtime(true) < $deadline);
        return null;
    }

    private static function identifier(mixed $value, string $field): int|string
    {
        if (is_int($value) || (is_string($value) && trim($value) !== '')) {
            return $value;
        }
        throw new \RuntimeException($field . ' was missing in the Sandbox response.');
    }

    private static function startWebhookListener(): void
    {
        if (!function_exists('proc_open')) {
            throw new \RuntimeException('proc_open is required for webhook integration tests.');
        }
        $host = self::configured([
            'LUMINAL_OPEN_API_RECHARGE_WEBHOOK_HOST',
            'LUMINAL_OPEN_API_WEBHOOK_HOST',
        ], self::DEFAULT_WEBHOOK_HOST) ?? self::DEFAULT_WEBHOOK_HOST;
        $portValue = self::configured([
            'LUMINAL_OPEN_API_RECHARGE_WEBHOOK_PORT',
            'LUMINAL_OPEN_API_WEBHOOK_PORT',
        ], (string)self::DEFAULT_WEBHOOK_PORT) ?? (string)self::DEFAULT_WEBHOOK_PORT;
        $port = (int)$portValue;
        $path = self::configured([
            'LUMINAL_OPEN_API_RECHARGE_WEBHOOK_PATH',
            'LUMINAL_OPEN_API_WEBHOOK_PATH',
        ], self::DEFAULT_WEBHOOK_PATH) ?? self::DEFAULT_WEBHOOK_PATH;
        if ($path === '' || !str_starts_with($path, '/') || preg_match('/[\s?#]/', $path)) {
            throw new \RuntimeException('Invalid webhook listener path.');
        }
        if ($port < 1 || $port > 65535) {
            throw new \RuntimeException('Invalid webhook listener port.');
        }

        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'luminal-php-sdk-recharge-webhooks-' . bin2hex(random_bytes(8));
        if (!mkdir($directory, 0700) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create webhook event directory.');
        }
        self::$webhookEventDirectory = $directory;
        $key = self::webhookPublicKey();
        $keyFile = $directory . DIRECTORY_SEPARATOR . 'public-key.pem';
        file_put_contents($keyFile, $key, LOCK_EX);
        $stdout = $directory . DIRECTORY_SEPARATOR . 'server.log';
        $stderr = $directory . DIRECTORY_SEPARATOR . 'server-error.log';
        $environment = getenv();
        $environment = is_array($environment) ? $environment : [];
        $environment['LUMINAL_OPEN_API_BASE_URL'] = self::baseUrl();
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
            throw new \RuntimeException('Unable to start recharge-card webhook listener.');
        }
        self::$webhookProcess = $process;

        $connectHost = $host === '0.0.0.0' ? '127.0.0.1' : $host;
        $deadline = microtime(true) + 5;
        do {
            $socket = @fsockopen($connectHost, $port, $errorCode, $errorMessage, 0.2);
            if (is_resource($socket)) {
                fclose($socket);
                return;
            }
            usleep(100000);
        } while (microtime(true) < $deadline);
        $log = is_file($stderr) ? trim((string)file_get_contents($stderr)) : '';
        self::stopWebhookListener();
        throw new \RuntimeException('Recharge-card webhook listener failed to start.' . ($log !== '' ? ' ' . $log : ''));
    }

    private static function webhookPublicKey(): string
    {
        $value = self::configured([
            'LUMINAL_OPEN_API_RECHARGE_WEBHOOK_PUBLIC_KEY',
            'LUMINAL_OPEN_API_WEBHOOK_PUBLIC_KEY',
        ]);
        if ($value !== null && is_readable($value)) {
            return (string)file_get_contents($value);
        }
        if ($value !== null && str_contains($value, '-----BEGIN')) {
            return str_replace('\\n', "\n", $value);
        }
        $defaultPath = __DIR__ . '/platform-public-key.pem';
        if (!is_readable($defaultPath)) {
            throw new \RuntimeException('Webhook public key is not configured.');
        }
        return (string)file_get_contents($defaultPath);
    }

    private static function stopWebhookListener(): void
    {
        if (is_resource(self::$webhookProcess)) {
            $status = proc_get_status(self::$webhookProcess);
            if (($status['running'] ?? false) === true) {
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
}
