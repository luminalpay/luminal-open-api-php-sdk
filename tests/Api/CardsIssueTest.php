<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\CanonicalJson;
use Luminal\OpenApiSdk\Model\IssueCardRequest;
use Luminal\OpenApiSdk\RsaSignatures;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardsIssueTest extends EndpointTestCase
{
    public function testIssuesCardWithProvidedSignature(): void
    {
        $request = new IssueCardRequest(1, 1001, null, null, null, 77, null, 100.00);
        $result = (new CardsApi($this->transport(9001)))->issue($request, 'signature');

        self::assertSame(9001, $result);
        $this->assertRequest('/open-api/v1/cards/issue', $request);
        self::assertSame('signature', $this->request['headers']['sign'] ?? null);
    }

    public function testIssuesCardWithPrivateKey(): void
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        self::assertNotFalse($key);
        $privateKeyPem = null;
        self::assertTrue(openssl_pkey_export($key, $privateKeyPem));
        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);
        self::assertIsString($details['key'] ?? null);

        $request = new IssueCardRequest(
            1,
            '9007199254740991',
            null,
            null,
            null,
            '9007199254740992',
            null,
            '100.00',
        );
        $result = (new CardsApi($this->transport(9002)))->issue($request, $key);

        self::assertSame(9002, $result);
        $this->assertRequest('/open-api/v1/cards/issue', $request);
        self::assertFalse(RsaSignatures::verify(
            $this->request['body'],
            $this->request['headers']['sign'],
            $details['key'],
        ));
        self::assertTrue(RsaSignatures::verify(
            CanonicalJson::encodeForSignature($request),
            $this->request['headers']['sign'],
            $details['key'],
        ));
    }
}
