# Luminal Open API PHP SDK

Standalone PHP 8.1+ SDK for every enabled Luminal Open API controller endpoint and supported webhook event.

## Guarantees

- No dependency on any Luminal project module.
- HTTP uses Guzzle 7 with Composer's maintained Mozilla CA bundle; TLS verification is enabled by default.
- Unless noted otherwise, requests use HTTP `POST`; `cardHolders()->countries()` uses an authorized `GET`. All requests decode the standard `{code, msg, data}` response envelope.
- Only HTTP `200` is transport success. Every other HTTP status, malformed envelope, or non-zero API code throws `ApiException`.
- Card issuance uses separate canonical JSON for transport and signing.
- Webhooks verify the exact raw request body before JSON decoding.

## Installation

```shell
composer require luminal/open-api-php-sdk
```

For local development:

```shell
composer install
composer test
```

## Client setup

```php
use Luminal\OpenApiSdk\Client;
use Luminal\OpenApiSdk\Model\AuthTokenRequest;
use Luminal\OpenApiSdk\Model\WalletInfoRequest;

$publicClient = new Client('https://api.example.com');
$token = $publicClient->auth()->getToken(new AuthTokenRequest('app-id', 'app-secret'));
if ($token === null || $token->accessToken === null) {
    throw new RuntimeException('The API did not return an access token.');
}

$client = $publicClient->withBearerToken($token->accessToken);
$accounts = $client->accounts()->list(new WalletInfoRequest(
    pageNo: 1,
    pageSize: 10,
    currency: 'USD',
));
```

`baseUrl` may include a gateway context path, for example `https://gateway.example.com/luminal`.
Do not include a query string or fragment. Controller methods accept typed request objects and return typed response objects, `PageResult`, lists of typed response objects, scalar values, or `null`.

## Int64 serialization

Regular request JSON follows the JavaScript safe-integer rule:

- Values strictly between `-9_007_199_254_740_991` and `9_007_199_254_740_991` are JSON numbers.
- Boundary values and values outside that range are JSON strings.
- Request numeric strings remain strings by default.

PHP has no built-in `Long` wrapper type. The SDK represents Java `Long` fields as `int|string|null`. API response Int64 values may be JSON numbers or decimal strings; the SDK preserves either form as `int|string|null`. For `/cards/issue`, transport JSON keeps the regular JavaScript safe-integer rule, while signature JSON emits typed `Long`/ID fields as JSON numbers. This includes `cardBinId`, `cardGroupId`, and `memberSharedAccountId`; decimal amount fields remain JSON numbers in both forms.

## Transport configuration

The built-in `HttpTransport` uses PHP stream wrappers, a 30-second timeout, TLS certificate/hostname verification, a 1 MiB response-body limit, `Accept-Language: en` by default, and automatic bearer-token refresh support when a token provider is configured. Configure the timeout, TLS behavior, locale, and retry policy explicitly when needed:

```php
use Luminal\OpenApiSdk\HttpTransport;

$transport = new HttpTransport(
    baseUrl: 'https://api.example.com',
    bearerToken: $token,
    timeoutSeconds: 10.0,
    verifyTls: true,
    locale: 'zh',
);
$client = new \Luminal\OpenApiSdk\Client($transport);
```

API groups and `Client` accept `TransportInterface`. Use a custom implementation for an existing HTTP stack, tracing, proxies, or retry policy. The built-in transport never retries non-`401` POST failures. An authorized `401` may refresh the token and retry up to the configured unauthorized retry count.

## Controller coverage

| SDK method | HTTP path | Description |
|---|---|---|
| `auth()->getToken()` | `/open-api/v1/auth/token` | Obtain an OAuth2 token with Basic authorization. |
| `auth()->refreshToken()` | `/open-api/v1/auth/refresh-token` | Exchange a refresh token for new token data. |
| `auth()->logout()` | `/open-api/v1/auth/logout` | Invalidate the configured bearer token. |
| `accounts()->list()` | `/open-api/v1/accounts` | List wallet accounts. |
| `transactions()->list()` | `/open-api/v1/transactions/list` | List wallet transactions. |
| `sharedAccounts()->create()` | `/open-api/v1/shared-account/create` | Create and initially fund a shared account. |
| `sharedAccounts()->list()` | `/open-api/v1/shared-account/list` | List shared accounts. |
| `sharedAccounts()->increase()` | `/open-api/v1/shared-account/increase` | Deposit into a shared account. |
| `sharedAccounts()->decrease()` | `/open-api/v1/shared-account/decrease` | Withdraw from a shared account. |
| `sharedAccounts()->cancel()` | `/open-api/v1/shared-account/cancel` | Cancel a shared account with an email/OTP verification code. |
| `sharedAccounts()->details()` | `/open-api/v1/shared-account/details` | Retrieve shared-account details. |
| `sharedAccounts()->transactions()` | `/open-api/v1/shared-account/transactions` | List shared-account transactions. |
| `cardPools()->list()` | `/open-api/v1/cards/pools` | List available card pools as a plain list. |
| `cards()->bins()` | `/open-api/v1/cards/bins` | List available card BIN products. |
| `cards()->issue()` | `/open-api/v1/cards/issue` | Submit a signed card issuance request. |
| `cards()->list()` | `/open-api/v1/cards/list` | List issued cards. |
| `cards()->cvv()` | `/open-api/v1/cards/cvv` | Retrieve card number, CVV, and expiry data. |
| `cards()->transactions()` | `/open-api/v1/cards/transactions` | List card transactions. |
| `cards()->limit()` | `/open-api/v1/cards/limit` | Retrieve a card limit. |
| `cards()->modifyLimit()` | `/open-api/v1/cards/limit/modify` | Update a card limit. |
| `cards()->modifyLimitAsync()` | `/open-api/v1/cards/limit/modify/operation-record` | Update a card limit and return an operation-record ID. |
| `cards()->freeze()` | `/open-api/v1/cards/freeze` | Freeze a card. |
| `cards()->unfreeze()` | `/open-api/v1/cards/unfreeze` | Unfreeze a card. |
| `cards()->cancel()` | `/open-api/v1/cards/cancel` | Cancel a card. |
| `cards()->recharge()` | `/open-api/v1/cards/recharge` | Submit a recharge-card funding request without a signature. |
| `cards()->withdraw()` | `/open-api/v1/cards/withdraw` | Submit a recharge-card withdrawal request without a signature. |
| `cards()->operationRecords()` | `/open-api/v1/cards/operation-record` | Query SHARED or RECHARGE card operation records with pagination. |
| `cardHolders()->countries()` | `/open-api/v1/card-holders/countries` | List supported cardholder countries and dialing-code rules. |
| `cardHolders()->add()` | `/open-api/v1/card-holders/add` | Create a cardholder. |
| `cardHolders()->modify()` | `/open-api/v1/card-holders/modify` | Update a cardholder. |
| `cardHolders()->detail()` | `/open-api/v1/card-holders/info/{cardHolderId}` | Retrieve cardholder details. |
| `cardHolders()->page()` | `/open-api/v1/card-holders/page` | List cardholders with pagination. |
| `cardHolders()->associatedCards()` | `/open-api/v1/card-holders/card/page` | List cards associated with a cardholder. |
| `cards()->issueDetails()` | `/open-api/v1/cards/issue/detail` | Retrieve card issuance task results. |
| `cardGroups()->list()` | `/open-api/v1/cards/group` | List card groups. |
| `cardGroups()->create()` | `/open-api/v1/cards/group/create` | Create a card group. |
| `cardGroups()->update()` | `/open-api/v1/cards/group/update` | Update a card group. |
| `cardGroups()->delete()` | `/open-api/v1/cards/group/delete` | Delete a card group. |

Request objects expose the controller field names as typed constructor arguments. Examples:

```php
use Luminal\OpenApiSdk\Model\CreateSharedAccountRequest;
use Luminal\OpenApiSdk\Model\MemberCardPageRequest;

$cards = $client->cards()->list(new MemberCardPageRequest(
    pageNo: 1,
    pageSize: 10,
    status: 'ACTIVE',
));

$sharedAccount = $client->sharedAccounts()->create(new CreateSharedAccountRequest(
    cardBinId: 1001,
    rechargeAmount: 100.00,
    accountName: 'Travel account',
));
```

Shared-account cancellation requires the server verification code:

```php
use Luminal\OpenApiSdk\Model\SharedAccountCancelRequest;

$canceled = $client->sharedAccounts()->cancel(new SharedAccountCancelRequest(
    memberSharedAccountId: 11,
    remark: 'No longer needed',
    verifyCode: $verificationCode,
));
```

`CardPoolRequest` only filters by pool ID and name; `cardBinId` is not a pool-list condition. Card-pool shared-account
opening first selects a pool, then resolves a SHARED BIN within that pool. Shared-account creation may use
`cardPoolId` without `cardBinId`; card issuance continues to require `cardBinId`:

```php
use Luminal\OpenApiSdk\Model\CardBinsRequest;
use Luminal\OpenApiSdk\Model\CardPoolRequest;
use Luminal\OpenApiSdk\Model\CreateSharedAccountRequest;

$pools = $client->cardPools()->list(new CardPoolRequest());
$pool = $pools[0] ?? throw new RuntimeException('No card pool is available.');
$bins = $client->cards()->bins(new CardBinsRequest(
    pageNo: 1,
    pageSize: 20,
    cardPoolId: $pool->cardPoolId,
    cardType: 'SHARED',
));
$sharedAccount = $client->sharedAccounts()->create(new CreateSharedAccountRequest(
    cardPoolId: $pool->cardPoolId,
    rechargeAmount: '100.00',
    accountName: 'Main account',
));
```

Shared-account creation may use `cardPoolId` when the BIN is selected from a pool. Card issuance continues to
require `cardBinId`.

Cardholder management uses `DateTimeImmutable` for `birthDate`; it is sent as a date-only `Y-m-d` value:

```php
use DateTimeImmutable;
use Luminal\OpenApiSdk\Model\CardHolderCreateRequest;

$cardHolderId = $client->cardHolders()->add(new CardHolderCreateRequest(
    lastName: 'Smith',
    firstName: 'John',
    birthDate: new DateTimeImmutable('1990-01-15'),
    mail: 'john.smith@example.com',
    phone: '2025550123',
    areaCode: '1',
    countryId: 244,
    postalCode: '10001',
    state: 'New York',
    city: 'New York',
    addressLine1: '350 Fifth Avenue',
));
```

## Card issuance signing

`/cards/issue` uses two canonical serializations. `CanonicalJson::encode()` produces the transmitted body with the regular Int64 number/string rule. `CanonicalJson::encodeForSignature()` produces the `SHA256withRSA` signature input with typed Long/ID fields kept as JSON numbers. Both omit null object fields and sort object keys alphabetically, but their bytes may differ:

```php
use Luminal\OpenApiSdk\Model\IssueCardRequest;

$request = new IssueCardRequest(
    applyCount: 1,
    cardBinId: 1001,
    cardType: 'RECHARGE',
    dailyLimit: 500.00,
    monthLimit: 5000.00,
    rechargeAmount: 100.00,
);
$taskId = $client->cards()->issueWithPrivateKey($request, $privateKeyPem);
```

Recharge-card funding and withdrawal are bearer-authorized JSON requests and do not require a signature:

```php
use Luminal\OpenApiSdk\Model\MemberCardRechargeRequest;
use Luminal\OpenApiSdk\Model\MemberCardWithdrawRequest;
use Luminal\OpenApiSdk\Model\RechargeCardOperationRecordRequest;

$rechargeRecordId = $client->cards()->recharge(
    new MemberCardRechargeRequest(memberCardId: 901, amount: 100.00, remark: 'Funding'),
);
$withdrawRecordId = $client->cards()->withdraw(
    new MemberCardWithdrawRequest(memberCardId: 901, amount: 25.00, remark: 'Withdrawal'),
);
$operation = $client->cards()->operationRecord(
    new RechargeCardOperationRecordRequest($rechargeRecordId),
);
```

Card-limit updates can include `dailyLimit`, `monthLimit`, and `totalLimit`. The current four-argument constructor is
`CardLimitUpdateRequest($memberCardId, $dailyLimit, $monthLimit, $totalLimit)`; the legacy `cardType` argument remains
accepted for source compatibility and is omitted from the request body. Use `modifyLimitAsync()` when the operation-record
identifier is needed for polling or webhook correlation.

For an externally computed signature:

```php
$taskId = $client->cards()->issue($request, $base64Signature);
```

`RsaSignatures::signCanonicalJson()` is available for integrations that need to precompute the signature. Typed request models apply numeric serialization automatically:

```php
$signature = RsaSignatures::signCanonicalJson(
    $request,
    $privateKeyPem,
);
$taskId = $client->cards()->issue($request, $signature);
```

## Webhooks

Webhook requests contain:

- `event`: one supported event name.
- `event_id`: unique event identifier.
- `sign`: Base64 `SHA256withRSA` signature.
- Body: exact JSON bytes covered by the signature.

Always pass the original raw request body. Never parse and reserialize it before verification:

```php
use Luminal\OpenApiSdk\Webhook\WebhookVerifier;

$event = WebhookVerifier::parse(
    $_SERVER['HTTP_EVENT'],
    $_SERVER['HTTP_EVENT_ID'],
    file_get_contents('php://input'),
    $_SERVER['HTTP_SIGN'],
    $publicKeyPem,
);

if ($event->type === 'CARD_STATUS') {
    $cardStatus = $event->payload instanceof \Luminal\OpenApiSdk\Model\CardStatusWebhook
        ? $event->payload->cardStatus
        : null;
}
```

Supported events:

| Event | Payload | Description |
|---|---|---|
| `CARD_TRANSACTIONS` | `TransactionWebhook` | Card transaction update. |
| `CARD_SETTLE_STATUS` | `TransactionWebhook` | Local settlement status update for recharge and shared cards. |
| `CARD_STATUS` | `CardStatusWebhook` | Card status change. |
| `CARD_OPEN_STATUS` | `CardOpenStatusWebhook` | Card issuance task result. |
| `CARD_RECHARGE_STATUS` | `RechargeCardTransferStatusWebhook` | Recharge-card funding result. |
| `CARD_WITHDRAW_STATUS` | `RechargeCardTransferStatusWebhook` | Recharge-card withdrawal result. |
| `CARD_LIMIT_STATUS` | `RechargeCardTransferStatusWebhook` | Asynchronous card-limit modification result. |
| `SHARED_ACCOUNT_OPEN_STATUS` | `SharedAccountOpenStatusWebhook` | Shared-account opening result. |
| `SHARE_ACCOUNT_FUND_TRANSACTIONS` | `TransactionWebhook` | Shared-account fund transaction update. |

The SDK verifies signatures but does not persist `event_id` values. Store event IDs and reject duplicates in the application according to its retention policy.

`TransactionWebhook` includes `memberCardTransactionId`, `settleStatus`, and `settleTime` for the newer card transaction
events. `RechargeCardTransferStatusWebhook` carries the operation-record ID, card ID, operation type, status, amount,
balance, and update time for recharge, withdrawal, and limit operations.

## Tests

Controller endpoint tests cover HTTP method, path, authorization, request body, and response decoding. Webhook tests cover
exact-byte verification, tampered bodies, all supported event types, invalid signatures, unknown events, and RSA PEM handling.

### Sandbox integration tests

The Java-aligned fixed-BIN shared-card flow is in
`tests/Integration/ShareCardSandboxOpenApiIntegrationTest.php`:

```powershell
vendor\bin\phpunit -c phpunit.xml.dist tests\Integration\ShareCardSandboxOpenApiIntegrationTest.php
```

The pool-based shared-card flow reuses the complete shared-card test suite and selects the first cached card pool and
its SHARED BIN. It is in `tests/Integration/CardPoolSharedAccountSandboxOpenApiIntegrationTest.php`:

```powershell
vendor\bin\phpunit -c phpunit.xml.dist tests\Integration\CardPoolSharedAccountSandboxOpenApiIntegrationTest.php
```

The pool flow opens the shared account with an initial amount of `100.00`; card issuance still sends the selected
`cardBinId`.

The recharge-card flow is independent and is in
`tests/Integration/RechargeCardSandboxOpenApiIntegrationTest.php`:

```powershell
$env:LUMINAL_OPEN_API_RECHARGE_APP_ID = '...'
$env:LUMINAL_OPEN_API_RECHARGE_APP_SECRET = '...'
$env:LUMINAL_OPEN_API_RECHARGE_PRIVATE_KEY_PATH = 'C:\keys\recharge-card-private-key.pem'
vendor\bin\phpunit -c phpunit.xml.dist tests\Integration\RechargeCardSandboxOpenApiIntegrationTest.php
```

The recharge-card test defaults to BIN `578391` and also accepts the shared
`LUMINAL_OPEN_API_*` variables as fallbacks. Without credentials, its 24
Sandbox tests are skipped.


## HTTP logging

Request/response logging is on by default.

Disable:

```php
$client = (new Client($baseUrl))->withHttpLogging(false);
```

### PSR-3 logger

Monolog is the common PHP choice.

```php
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$handler = new StreamHandler('php://stdout');
$handler->setFormatter(new LineFormatter(null, null, true, true));

$logger = new Logger('luminal-open-api');
$logger->pushHandler($handler);

$client = (new Client($baseUrl))
    ->withLogger($logger);
```

Without a custom logger, the SDK writes request/response logs to standard output. Header names and recursive JSON field names are matched case-insensitively. `Authorization`, `Cookie`, `Set-Cookie`, `sign`, `accessToken`, `refreshToken`, `appSecret`, `cvv`, `cardNo`, `cardNumber`, and `verifyCode` values are redacted as `<redacted>`. Non-JSON bodies log only `<non-json N bytes>`.

### TLS certificates

Public HTTPS certificates require no setup. The SDK uses Guzzle plus `composer/ca-bundle`, falling back to its maintained Mozilla CA bundle when PHP/OpenSSL has no configured CA file. PHP does not read the Java JDK `cacerts` truststore; therefore Java may succeed while an unconfigured PHP CLI reports cURL error 60.

For a private enterprise CA only, pass its root/intermediate chain as a readable PEM bundle:

```php
$client = new Client(
    baseUrl: 'https://sandbox-openapi.luminalads.com',
    caBundle: 'C:/certs/luminal-enterprise-ca-bundle.pem',
);
```

Equivalent process configuration:

```powershell
$env:SSL_CERT_FILE = 'C:\certs\luminal-enterprise-ca-bundle.pem'
```

Do not disable TLS verification. `CURLOPT_SSL_VERIFYPEER=false` hides certificate-chain problems and permits man-in-the-middle attacks.
