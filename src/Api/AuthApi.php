<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Api;

use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\HttpTransport;
use Luminal\OpenApiSdk\TransportInterface;
use Luminal\OpenApiSdk\Model\AuthTokenRequest;
use Luminal\OpenApiSdk\Model\OAuth2Token;
use Luminal\OpenApiSdk\Model\RefreshTokenRequest;

/** Authorization endpoints. */
final class AuthApi
{
    private const PATH = '/open-api/v1/auth';

    public function __construct(private readonly TransportInterface $transport)
    {
    }

    /** Obtains an OAuth2 token with Basic Base64(appId:appSecret). */
    public function getToken(AuthTokenRequest $request): ?OAuth2Token
    {
        $credentials = HttpTransport::requireNonBlank($request->appId, 'appId') . ':'
            . HttpTransport::requireNonBlank($request->appSecret, 'appSecret');
        $data = $this->transport->postPublic(self::PATH . '/token', null, [
            'Authorization' => 'Basic ' . base64_encode($credentials),
        ]);
        return self::token($data);
    }

    /** Exchanges a refresh token for new OAuth2 token data. */
    public function refreshToken(RefreshTokenRequest $request): ?OAuth2Token
    {
        $data = $this->transport->postAuthorized(self::PATH . '/refresh-token', $request);
        return self::token($data);
    }

    /** Invalidates the configured bearer token. */
    public function logout(): bool
    {
        return $this->transport->postAuthorizedBoolean(self::PATH . '/logout', null);
    }

    private static function token(mixed $data): ?OAuth2Token
    {
        return $data === null ? null : OAuth2Token::fromArray(self::object($data));
    }

    private static function object(mixed $data): array
    {
        if (!is_array($data)) {
            throw new ApiException('Auth response data must be a JSON object.');
        }
        return $data;
    }
}


