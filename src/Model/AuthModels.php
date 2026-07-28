<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;

/** OAuth token request used for Basic authentication. */
final class AuthTokenRequest extends JsonModel
{
    public function __construct(
        public readonly string $appId,
        public readonly string $appSecret,
    ) {
    }

    protected function validateModel(): void
    {
        $this->requireNonBlank($this->appId, 'appId');
        $this->requireNonBlank($this->appSecret, 'appSecret');
    }
    }

/** OAuth2 access and refresh token data. */
final class OAuth2Token extends JsonModel
{
    public function __construct(
        public readonly ?string $accessToken = null,
        public readonly ?string $tokenType = null,
        public readonly int|string|null $expiresTime = null,
        public readonly ?string $refreshToken = null,
        public readonly ?string $scope = null,
        public readonly ?string $jti = null,
    ) {
    }

    public function __toString(): string
    {
        return 'OAuth2Token[accessToken=<redacted>, tokenType=' . $this->tokenType
            . ', expiresTime=' . $this->expiresTime . ', refreshToken=<redacted>, scope='
            . $this->scope . ', jti=' . $this->jti . ']';
    }
}

/** Request for a new access token using a refresh token. */
final class RefreshTokenRequest extends JsonModel
{
    public function __construct(public readonly string $refreshToken)
    {
    }

    public function __toString(): string
    {
        return 'RefreshTokenRequest[refreshToken=<redacted>]';
    }

    protected function validateModel(): void
    {
        $this->requireNonBlank($this->refreshToken, 'refreshToken');
    }
}
