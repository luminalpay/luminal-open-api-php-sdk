<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Integration;

use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\Client;
use Luminal\OpenApiSdk\Model\AuthTokenRequest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
final class AuthGetTokenIntegrationTest extends TestCase
{
    private const BASE_URL = 'https://sandbox-openapi.luminalads.com';
    private const APP_ID = 'lpsha6pj5mwsb7tz';
    private const APP_SECRET = 'P11g59PXY33JjqL4CRJ2Oz3nfsjsWRKe';

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

    public function testGetsTokenFromSandbox(): void
    {
        $client = new Client(self::BASE_URL);
        try {
            $token = $client->auth()->getToken(new AuthTokenRequest(self::APP_ID, self::APP_SECRET));
        } catch (ApiException $exception) {
            if (str_contains($exception->getMessage(), 'forbidden by its access permissions') || str_contains($exception->getMessage(), 'socket')) {
                self::markTestSkipped('Sandbox network blocked in this environment.');
            }
            throw $exception;
        }
        $printable = [];
        foreach ($token?->jsonSerialize() ?? [] as $key => $value) {
            $printable[$key] = preg_match('/(?:token|secret|password|cvv|cardNo|cardNumber|privateKey|signature)/i', (string) $key) === 1
                ? '<redacted>'
                : $value;
        }
        $output = json_encode($printable, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        fwrite(STDOUT, PHP_EOL . '[auth.getToken]' . PHP_EOL . ($output === false ? var_export($printable, true) : $output) . PHP_EOL);

        self::assertNotNull($token);
        self::assertNotSame('', $token->accessToken);
    }
}