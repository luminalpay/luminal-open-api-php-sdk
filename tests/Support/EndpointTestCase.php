<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Support;

use Luminal\OpenApiSdk\CanonicalJson;
use Luminal\OpenApiSdk\HttpTransport;
use Luminal\OpenApiSdk\Model\JsonModel;
use PHPUnit\Framework\TestCase;

/** Shared fake transport assertions used by one test class per controller endpoint. */
abstract class EndpointTestCase extends TestCase
{
    /** @var array{method:string,url:string,headers:array<string,string>,body:?string} */
    protected array $request = [];

    protected function transport(mixed $data): HttpTransport
    {
        return new HttpTransport(
            'https://api.example.test',
            'test-token',
            function (string $method, string $url, array $headers, ?string $body) use ($data): array {
                $this->request = compact('method', 'url', 'headers', 'body');
                return [
                    'status' => 200,
                    'body' => json_encode(['code' => 0, 'msg' => 'success', 'data' => $data], JSON_THROW_ON_ERROR),
                ];
            },
        );
    }

    protected function assertRequest(string $path, ?JsonModel $body, string $authorization = 'Bearer test-token'): void
    {
        self::assertSame('POST', $this->request['method']);
        self::assertSame('https://api.example.test' . $path, $this->request['url']);
        self::assertSame($authorization, $this->request['headers']['Authorization'] ?? null);
        if ($body === null) {
            self::assertNull($this->request['body']);
            return;
        }
        self::assertSame(CanonicalJson::encode($body), $this->request['body']);
        self::assertSame('application/json', $this->request['headers']['Content-Type'] ?? null);
    }

    protected function assertGet(string $path, string $authorization = 'Bearer test-token'): void
    {
        self::assertSame('GET', $this->request['method']);
        self::assertSame('https://api.example.test' . $path, $this->request['url']);
        self::assertSame($authorization, $this->request['headers']['Authorization'] ?? null);
        self::assertNull($this->request['body']);
        self::assertArrayNotHasKey('Content-Type', $this->request['headers']);
    }

    protected function assertEmptyJsonPost(string $path, string $authorization = 'Bearer test-token'): void
    {
        self::assertSame('POST', $this->request['method']);
        self::assertSame('https://api.example.test' . $path, $this->request['url']);
        self::assertSame($authorization, $this->request['headers']['Authorization'] ?? null);
        self::assertSame('{}', $this->request['body']);
        self::assertSame('application/json', $this->request['headers']['Content-Type'] ?? null);
    }

    protected function assertJsonBodyContains(string $fragment): void
    {
        self::assertStringContainsString($fragment, $this->request['body'] ?? '');
    }
}
