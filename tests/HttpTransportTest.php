<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests;

use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\Client;
use Luminal\OpenApiSdk\HttpTransport;
use Luminal\OpenApiSdk\Internal\CookieJar;
use Luminal\OpenApiSdk\TransportInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HttpTransportTest extends TestCase
{
    public function testClientAcceptsAReplaceableTransport(): void
    {
        $transport = new class implements TransportInterface {
            public function withBearerToken(string $token): static
            {
                return $this;
            }

            public function serialize(mixed $body): string
            {
                return '{}';
            }

            public function postPublic(string $path, mixed $body, array $headers = []): mixed
            {
                return null;
            }

            public function postAuthorized(string $path, mixed $body, array $headers = []): mixed
            {
                return null;
            }

            public function postSerializedAuthorized(string $path, string $body, array $headers = []): mixed
            {
                return null;
            }

            public function postAuthorizedBoolean(string $path, mixed $body): bool
            {
                return true;
            }
        };

        self::assertTrue((new Client($transport))->auth()->logout());
    }

    public function testSenderExceptionsAreReportedAsApiExceptions(): void
    {
        $cause = new RuntimeException('connection refused');
        $transport = new HttpTransport(
            'https://api.example.test',
            'test-token',
            static function () use ($cause): never {
                throw $cause;
            },
        );

        try {
            $transport->postAuthorized('/open-api/v1/example', null);
            self::fail('Expected ApiException.');
        } catch (ApiException $exception) {
            self::assertSame('Luminal API request failed.', $exception->getMessage());
            self::assertSame($cause, $exception->getPrevious());
        }
    }

    public function testNonUnauthorizedPostFailureIsNotRetried(): void
    {
        $calls = 0;
        $transport = new HttpTransport(
            'https://api.example.test',
            'test-token',
            static function () use (&$calls): never {
                $calls++;
                throw new ApiException('Token backend failed.', 500);
            },
        );

        try {
            $transport->postAuthorized('/open-api/v1/example', null);
            self::fail('Expected ApiException.');
        } catch (ApiException) {
            self::assertSame(1, $calls);
        }
    }

    public function testUnauthorizedPostFailureRefreshesTokenAndRetriesOnce(): void
    {
        $providedTokens = 0;
        $authorizationHeaders = [];
        $transport = new HttpTransport(
            'https://api.example.test',
            null,
            static function (string $method, string $url, array $headers) use (&$authorizationHeaders): array {
                $authorizationHeaders[] = $headers['Authorization'];
                if (count($authorizationHeaders) === 1) {
                    throw new ApiException('Unauthorized.', 401);
                }
                return ['status' => 200, 'body' => '{"code":0,"data":true}'];
            },
            tokenProvider: static function () use (&$providedTokens): array {
                $providedTokens++;
                return ['accessToken' => $providedTokens === 1 ? 'old-token' : 'new-token'];
            },
        );

        self::assertTrue($transport->postAuthorized('/open-api/v1/example', null));
        self::assertSame(['Bearer old-token', 'Bearer new-token'], $authorizationHeaders);
        self::assertSame(2, $providedTokens);
    }

    public function testSecondBasedExpiryRefreshesAtHalfLifetime(): void
    {
        $providedTokens = 0;
        $authorizationHeaders = [];
        $transport = new HttpTransport(
            'https://api.example.test',
            sender: static function (string $method, string $url, array $headers) use (&$authorizationHeaders): array {
                $authorizationHeaders[] = $headers['Authorization'];
                return ['status' => 200, 'body' => '{"code":0,"data":true}'];
            },
            tokenProvider: static function () use (&$providedTokens): array {
                $providedTokens++;
                return $providedTokens === 1
                    ? ['accessToken' => 'old-token', 'expiresTime' => time() + 1]
                    : ['accessToken' => 'new-token'];
            },
        );

        self::assertTrue($transport->postAuthorized('/open-api/v1/example', null));
        usleep(600000);
        self::assertTrue($transport->postAuthorized('/open-api/v1/example', null));

        self::assertSame(['Bearer old-token', 'Bearer new-token'], $authorizationHeaders);
        self::assertSame(2, $providedTokens);
    }

    public function testDefaultCookieJarScopesCookiesAndIsSharedByTransportCopies(): void
    {
        $jar = new CookieJar();
        $jar->storeFromResponse('https://api.example.test/open-api/v1/token', [
            'Set-Cookie: session=abc; Path=/open-api; Max-Age=60; Secure',
            'Set-Cookie: expired=gone; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT',
        ]);

        self::assertSame('session=abc', $jar->headerFor('https://api.example.test/open-api/v1/cards'));
        self::assertNull($jar->headerFor('http://api.example.test/open-api/v1/cards'));
        self::assertNull($jar->headerFor('https://api.example.test/public'));
        self::assertNull($jar->headerFor('https://other.example.test/open-api/v1/cards'));

        $transport = new HttpTransport('https://api.example.test');
        $copy = $transport->withBearerToken('token');
        $property = new \ReflectionProperty(HttpTransport::class, 'cookies');
        self::assertSame($property->getValue($transport), $property->getValue($copy));
    }

    public function testRejectsOversizedResponseBody(): void
    {
        $transport = new HttpTransport(
            'https://api.example.test',
            'test-token',
            static fn(): array => ['status' => 200, 'body' => str_repeat('x', 1_048_577)],
        );

        try {
            $transport->postAuthorized('/open-api/v1/example', null);
            self::fail('Expected ApiException.');
        } catch (ApiException $exception) {
            self::assertSame(200, $exception->httpStatus);
            self::assertSame('Luminal API response body exceeds 1048576 bytes.', $exception->getMessage());
        }
    }

    public function testRejectsInvalidTimeout(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HttpTransport('https://api.example.test', null, null, 0.0);
    }

    public function testRejectsHeaderInjection(): void
    {
        $transport = new HttpTransport(
            'https://api.example.test',
            'test-token',
            static fn(): array => ['status' => 200, 'body' => '{"code":0,"data":true}'],
        );

        $this->expectException(\InvalidArgumentException::class);
        $transport->postAuthorized('/open-api/v1/example', null, ['X-Test' => "safe\r\nInjected: true"]);
    }

    public function testHeadersAreCaseInsensitive(): void
    {
        $request = null;
        $transport = new HttpTransport(
            'https://api.example.test',
            'test-token',
            function (string $method, string $url, array $headers, ?string $body) use (&$request): array {
                $request = $headers;
                return ['status' => 200, 'body' => '{"code":0,"data":true}'];
            },
        );

        $transport->postAuthorized('/open-api/v1/example', null, ['accept' => 'application/json']);

        self::assertIsArray($request);
        self::assertArrayNotHasKey('Accept', $request);
        self::assertSame('application/json', $request['accept']);
        self::assertSame('Bearer test-token', $request['Authorization']);
    }

    public function testHttpLoggingIsEnabledByDefault(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $transport = new HttpTransport(
            'https://api.example.test',
            'secret-token',
            static fn(): array => ['status' => 200, 'body' => '{"code":0,"data":true}'],
            logger: $logger,
        );

        self::assertTrue($transport->postAuthorized('/open-api/v1/example', null));
        $records = $handler->getRecords();
        self::assertCount(2, $records);
        self::assertSame('HTTP request method=POST url=https://api.example.test/open-api/v1/example', $records[0]->message);
        self::assertSame('HTTP response url=https://api.example.test/open-api/v1/example status=200', $records[1]->message);
        self::assertSame('', $records[0]->context['body']);
    }

    public function testHttpLoggingWithoutLoggerIsSilent(): void
    {
        $transport = new HttpTransport(
            'https://api.example.test',
            'secret-token',
            static fn(): array => ['status' => 200, 'body' => '{"code":0,"data":true}'],
        );

        self::assertTrue($transport->postAuthorized('/open-api/v1/example', null));
    }

    public function testHttpLoggingCanBeDisabled(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $transport = (new HttpTransport(
            'https://api.example.test',
            'secret-token',
            static fn(): array => ['status' => 200, 'body' => '{"code":0,"data":true}'],
            logger: $logger,
        ))->withHttpLogging(false);

        self::assertTrue($transport->postAuthorized('/open-api/v1/example', null));
        self::assertSame([], $handler->getRecords());
    }

    public function testHttpLoggingRedactsSensitiveHeadersAndJsonBodies(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $sentHeaders = null;
        $sentBody = null;
        $transport = new HttpTransport(
            'https://api.example.test',
            'bearer-secret',
            static function (string $method, string $url, array $headers, ?string $body) use (&$sentHeaders, &$sentBody): array {
                $sentHeaders = $headers;
                $sentBody = $body;
                return [
                    'status' => 200,
                    'body' => '{"code":0,"data":{"accessToken":"response-access-secret","refreshToken":"response-refresh-secret","appSecret":"response-app-secret","nested":{"cvv":"response-cvv-secret","cardNo":"response-card-secret","cardNumber":"response-card-number-secret"}}}',
                ];
            },
            logger: $logger,
        );

        $requestBody = ['refreshToken' => 'request-refresh-secret', 'nested' => ['CVV' => 'request-cvv-secret']];
        $requestHeaders = ['SiGn' => 'signature-secret', 'Cookie' => 'session=cookie-secret', 'sEt-CoOkIe' => 'response-cookie-secret'];
        $originalRequestBody = $requestBody;
        $originalRequestHeaders = $requestHeaders;
        $transport->postAuthorized(
            '/open-api/v1/example',
            $requestBody,
            $requestHeaders,
        );

        $records = $handler->getRecords();
        self::assertCount(2, $records);
        $logs = json_encode($records);
        self::assertIsString($logs);
        foreach ([
                     'bearer-secret', 'request-refresh-secret', 'request-cvv-secret', 'response-access-secret',
                     'response-refresh-secret', 'response-app-secret', 'response-cvv-secret', 'response-card-secret',
                     'response-card-number-secret', 'signature-secret', 'cookie-secret', 'response-cookie-secret',
                 ] as $secret) {
            self::assertStringNotContainsString($secret, $logs);
        }
        self::assertStringContainsString('<redacted>', $logs);
        self::assertSame($originalRequestBody, $requestBody);
        self::assertSame($originalRequestHeaders, $requestHeaders);
        self::assertSame('signature-secret', $sentHeaders['SiGn']);
        self::assertSame('session=cookie-secret', $sentHeaders['Cookie']);
        self::assertSame('response-cookie-secret', $sentHeaders['sEt-CoOkIe']);
        self::assertSame('Bearer bearer-secret', $sentHeaders['Authorization']);
        self::assertEquals($requestBody, json_decode((string)$sentBody, true));
    }

    public function testHttpLoggingDoesNotPrintNonJsonBodies(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $transport = new HttpTransport(
            'https://api.example.test',
            'token',
            static fn(): array => ['status' => 200, 'body' => 'not-json'],
            logger: $logger,
        );

        try {
            $transport->postAuthorized('/open-api/v1/example', null);
            self::fail('Expected ApiException.');
        } catch (ApiException) {
            $logs = json_encode($handler->getRecords());
            self::assertIsString($logs);
            self::assertStringNotContainsString('not-json', $logs);
            self::assertStringContainsString('<non-json 8 bytes>', $logs);
        }
    }
}
