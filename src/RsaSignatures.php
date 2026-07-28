<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk;

/** SHA-256 with RSA signing and verification helpers. */
final class RsaSignatures
{
    private function __construct()
    {
    }

    /** Returns a Base64 SHA256withRSA signature for exact bytes. */
    public static function sign(string $content, string|\OpenSSLAsymmetricKey $privateKey): string
    {
        $key = $privateKey instanceof \OpenSSLAsymmetricKey
            ? $privateKey
            : openssl_pkey_get_private($privateKey);
        if ($key === false || !openssl_sign($content, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new \InvalidArgumentException('Invalid RSA private key or signing failure.');
        }
        return base64_encode($signature);
    }

    /** Returns true only when the Base64 signature matches the exact bytes. */
    public static function verify(string $content, string $signature, string $publicKeyPem): bool
    {
        if ($signature === '' || ($decoded = base64_decode($signature, true)) === false) {
            return false;
        }
        $key = openssl_pkey_get_public($publicKeyPem);
        return $key !== false && openssl_verify($content, $decoded, $key, OPENSSL_ALGO_SHA256) === 1;
    }

    /** Signs the canonical JSON representation of a value. */
    public static function signCanonicalJson(mixed $value, string $privateKeyPem, array $numericFields = []): string
    {
        $body = $numericFields === []
            ? CanonicalJson::encodeForSignature($value)
            : CanonicalJson::encodeNumericFields($value, $numericFields);
        return self::sign($body, $privateKeyPem);
    }
}
