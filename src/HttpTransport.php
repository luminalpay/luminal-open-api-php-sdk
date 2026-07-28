<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk;

use Closure;
use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Luminal\OpenApiSdk\Internal\CookieJar;
use Psr\Log\LoggerInterface;
use JsonException;
use Throwable;

/** Minimal standalone POST transport for the Luminal response envelope. */
final class HttpTransport implements TransportInterface
{
    private const SUCCESS_CODE = 0;
    private const DEFAULT_TIMEOUT_SECONDS = 30.0;
    private const DEFAULT_LOCALE = 'en';
    private const DEFAULT_HTTP_LOGGING = true;
    private const DEFAULT_MAX_RESPONSE_BODY_BYTES = 1_048_576;
    private const SENSITIVE_LOG_HEADERS = ['authorization', 'cookie', 'set-cookie', 'sign'];
    private const SENSITIVE_LOG_BODY_KEYS = ['accesstoken', 'refreshtoken', 'appsecret', 'cvv', 'cardno', 'cardnumber'];

    /**
     * @param callable|null $sender Test hook receiving method, URL, headers, and nullable body.
     */
    public function __construct(
        string           $baseUrl,
        ?string          $bearerToken = null,
        ?callable        $sender = null,
        float            $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
        bool             $verifyTls = true,
        ?callable        $tokenProvider = null,
        int              $unauthorizedRetryCount = 1,
        string           $locale = self::DEFAULT_LOCALE,
        bool             $httpLogging = self::DEFAULT_HTTP_LOGGING,
        ?LoggerInterface $logger = null,
        ?string          $caBundle = null,
    )
    {
        $this->baseUrl = self::validateBaseUrl($baseUrl);
        $this->bearerToken = $bearerToken === null ? null : self::requireNonBlank($bearerToken, 'bearerToken');
        $this->sender = $sender === null ? null : Closure::fromCallable($sender);
        $this->timeoutSeconds = self::validateTimeout($timeoutSeconds);
        $this->verifyTls = $verifyTls;
        $this->tokenProvider = $tokenProvider === null ? null : Closure::fromCallable($tokenProvider);
        $this->unauthorizedRetryCount = max(0, $unauthorizedRetryCount);
        $this->locale = self::normalizeLocale($locale);
        $this->httpLogging = $httpLogging;
        $this->logger = $logger;
        $this->caBundle = self::validateCaBundle($caBundle);
        $this->cookies = new CookieJar();
    }

    private readonly string $baseUrl;
    private readonly ?string $bearerToken;
    private readonly ?Closure $sender;
    private readonly float $timeoutSeconds;
    private readonly bool $verifyTls;
    private readonly ?Closure $tokenProvider;
    private readonly int $unauthorizedRetryCount;
    private readonly string $locale;
    private ?array $cachedToken = null;
    private readonly bool $httpLogging;
    private readonly ?LoggerInterface $logger;
    private readonly ?string $caBundle;
    private CookieJar $cookies;

    public function withBearerToken(string $token): static
    {
        return $this->shareCookies(new self(
            $this->baseUrl,
            self::requireNonBlank($token, 'bearerToken'),
            $this->sender,
            $this->timeoutSeconds,
            $this->verifyTls,
            $this->tokenProvider,
            $this->unauthorizedRetryCount,
            $this->locale,
            $this->httpLogging,
            $this->logger,
            $this->caBundle,
        ));
    }

    public function withTokenProvider(callable $tokenProvider, int $unauthorizedRetryCount = 1, string $locale = self::DEFAULT_LOCALE): static
    {
        return $this->shareCookies(new self(
            $this->baseUrl,
            $this->bearerToken,
            $this->sender,
            $this->timeoutSeconds,
            $this->verifyTls,
            $tokenProvider,
            $unauthorizedRetryCount,
            $locale,
            $this->httpLogging,
            $this->logger,
            $this->caBundle,
        ));
    }


    public function withLogger(LoggerInterface $logger): static
    {
        return $this->shareCookies(new self(
            $this->baseUrl,
            $this->bearerToken,
            $this->sender,
            $this->timeoutSeconds,
            $this->verifyTls,
            $this->tokenProvider,
            $this->unauthorizedRetryCount,
            $this->locale,
            $this->httpLogging,
            $logger,
            $this->caBundle,
        ));
    }

    public function withHttpLogging(bool $enabled = true): static
    {
        return $this->shareCookies(new self(
            $this->baseUrl,
            $this->bearerToken,
            $this->sender,
            $this->timeoutSeconds,
            $this->verifyTls,
            $this->tokenProvider,
            $this->unauthorizedRetryCount,
            $this->locale,
            $enabled,
            $this->logger,
            $this->caBundle,
        ));
    }

    public function withLocale(string $locale): static
    {
        return $this->shareCookies(new self(
            $this->baseUrl,
            $this->bearerToken,
            $this->sender,
            $this->timeoutSeconds,
            $this->verifyTls,
            $this->tokenProvider,
            $this->unauthorizedRetryCount,
            $locale,
            $this->httpLogging,
            $this->logger,
            $this->caBundle,
        ));
    }

    private function shareCookies(self $transport): self
    {
        $transport->cookies = $this->cookies;
        return $transport;
    }

    /** Serializes a request using the canonical SDK JSON rules. */
    public function serialize(mixed $body): string
    {
        try {
            return CanonicalJson::encode($body);
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException('Value cannot be serialized as JSON.', 0, $exception);
        }
    }

    public function postPublic(string $path, mixed $body, array $headers = []): mixed
    {
        return $this->post($path, $body === null ? null : $this->serialize($body), $headers, false);
    }

    public function postAuthorized(string $path, mixed $body, array $headers = []): mixed
    {
        return $this->post($path, $body === null ? null : $this->serialize($body), $headers, true);
    }

    public function postSerializedAuthorized(string $path, string $body, array $headers = []): mixed
    {
        return $this->post($path, $body, $headers, true);
    }

    public function postAuthorizedBoolean(string $path, mixed $body): bool
    {
        return $this->postAuthorized($path, $body) === true;
    }

    public static function requireNonBlank(string $value, string $name): string
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException($name . ' must not be blank.');
        }
        return $value;
    }

    private function post(string $path, ?string $body, array $headers, bool $authorized): mixed
    {
        self::validatePath($path);
        $headers = self::normalizeHeaders($headers);
        self::setHeaderIfMissing($headers, 'Accept', 'application/json');
        self::setHeaderIfMissing($headers, 'Accept-Language', $this->locale);
        if ($body !== null) {
            self::setHeaderIfMissing($headers, 'Content-Type', 'application/json');
        }

        $attempts = $authorized ? $this->unauthorizedRetryCount + 1 : 1;
        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            if ($authorized) {
                self::setHeader($headers, 'Authorization', 'Bearer ' . $this->resolveBearerToken());
            }

            try {
                $this->logRequest($path, $headers, $body);
                $response = $this->sendOnce($path, $body, $headers);
            } catch (ApiException $exception) {
                if ($attempt + 1 < $attempts && $this->shouldRetryRequest($exception, $authorized)) {
                    $this->refreshCachedToken();
                    usleep(200000);
                    continue;
                }
                throw $exception;
            } catch (Throwable $exception) {
                throw new ApiException('Luminal API request failed.', 0, null, null, $exception);
            }

            if (!is_array($response) || !array_key_exists('status', $response) || !array_key_exists('body', $response)) {
                throw new ApiException('HTTP sender returned an invalid response.');
            }
            if (!is_int($response['status']) || !is_string($response['body'])) {
                throw new ApiException('HTTP sender returned an invalid response shape.');
            }
            $status = $response['status'];
            $responseBody = $response['body'];
            if (strlen($responseBody) > self::DEFAULT_MAX_RESPONSE_BODY_BYTES) {
                throw new ApiException(
                    'Luminal API response body exceeds 1048576 bytes.',
                    $status,
                );
            }
            $this->logResponse($this->baseUrl . $path, $status, $responseBody);
            if ($status !== 200) {
                if ($authorized && $status === 401 && $attempt + 1 < $attempts) {
                    $this->refreshCachedToken();
                    usleep(200000);
                    continue;
                }
                throw new ApiException("Luminal API returned HTTP {$status}.", $status, null, $responseBody);
            }

            try {
                $envelope = json_decode(
                    $responseBody,
                    true,
                    512,
                    JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING,
                );
            } catch (JsonException $exception) {
                throw new ApiException('Luminal API returned invalid JSON.', $status, null, $responseBody, $exception);
            }
            if (!is_array($envelope) || !array_key_exists('code', $envelope) || !is_int($envelope['code'])) {
                throw new ApiException('Luminal API response is missing an integer code.', $status, null, $responseBody);
            }
            $code = $envelope['code'];
            if ($code !== self::SUCCESS_CODE) {
                $message = isset($envelope['msg']) && is_string($envelope['msg']) && trim($envelope['msg']) !== ''
                    ? $envelope['msg']
                    : "Luminal API returned business code {$code}.";
                if ($attempt + 1 < $attempts && $authorized && $code === 401) {
                    $this->refreshCachedToken();
                    usleep(200000);
                    continue;
                }
                throw new ApiException($message, $status, $code, $responseBody);
            }
            return CanonicalJson::normalizeDecoded($envelope['data'] ?? null);
        }

        throw new ApiException('Luminal API request failed after token retry.');
    }

    private function sendOnce(string $path, ?string $body, array $headers): array
    {
        $method = 'POST';
        $url = $this->baseUrl . $path;
        if ($this->sender !== null) {
            return ($this->sender)($method, $url, $headers, $body);
        }
        return $this->sendHttp($method, $url, $headers, $body);
    }

    private function resolveBearerToken(): string
    {
        if ($this->bearerToken !== null) {
            return $this->bearerToken;
        }
        if ($this->tokenProvider === null) {
            throw new \InvalidArgumentException('bearerToken or tokenProvider must be configured for authorized requests.');
        }
        $token = $this->cachedToken;
        if ($token === null || !isset($token['accessToken']) || !is_string($token['accessToken']) || trim($token['accessToken']) === '' || $this->shouldRefreshCachedToken($token)) {
            $this->cachedToken = $this->normalizeToken(($this->tokenProvider)());
        }
        return $this->cachedToken['accessToken'];
    }

    private function refreshCachedToken(): void
    {
        if ($this->tokenProvider === null) {
            return;
        }
        $this->cachedToken = $this->normalizeToken(($this->tokenProvider)());
    }

    private function shouldRetryRequest(ApiException $exception, bool $authorized): bool
    {
        return $authorized
            && $this->unauthorizedRetryCount > 0
            && ($exception->httpStatus === 401 || $exception->apiCode === 401);
    }

    private function shouldRefreshCachedToken(array $token): bool
    {
        $expiresAt = $this->tokenExpiresAtMs($token);
        if ($expiresAt === null) {
            return false;
        }
        $remaining = $expiresAt - (int)floor(microtime(true) * 1000);
        $lifetime = $expiresAt - ($token['issuedAtMs'] ?? 0);
        return $lifetime > 0 && $remaining <= intdiv($lifetime, 2);
    }

    private function tokenExpiresAtMs(array $token): ?int
    {
        if (!isset($token['expiresTime']) || !is_int($token['expiresTime'])) {
            return null;
        }
        return $token['expiresTime'] < 1_000_000_000_000
            ? $token['expiresTime'] * 1000
            : $token['expiresTime'];
    }

    private function normalizeToken(mixed $token): array
    {
        if (!is_object($token) && !is_array($token)) {
            throw new \InvalidArgumentException('tokenProvider must return an OAuth2Token or array-like token.');
        }
        $data = json_decode(json_encode($token, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $data['issuedAtMs'] = (int)floor(microtime(true) * 1000);
        if (!isset($data['accessToken']) || !is_string($data['accessToken']) || trim($data['accessToken']) === '') {
            throw new \InvalidArgumentException('tokenProvider returned an invalid token without accessToken.');
        }
        if (isset($data['expiresTime']) && is_string($data['expiresTime']) && ctype_digit($data['expiresTime'])) {
            $data['expiresTime'] = (int)$data['expiresTime'];
        }
        return $data;
    }

    private static function normalizeLocale(string $locale): string
    {
        $value = strtolower(trim($locale));
        if (!in_array($value, ['en', 'zh'], true)) {
            throw new \InvalidArgumentException('locale must be en or zh.');
        }
        return $value;
    }


    private function logRequest(string $path, array $headers, ?string $body): void
    {
        if (!$this->httpLogging || $this->logger === null) {
            return;
        }
        $message = 'HTTP request method=POST url=' . $this->baseUrl . $path;
        $context = ['headers' => self::redactLogHeaders($headers), 'body' => self::redactLogBody($body)];
        $this->logger->info($message, $context);
    }

    public function logWebhook(string $event, string $eventId, string $body): void
    {
        if (!$this->httpLogging || $this->logger === null) {
            return;
        }
        $this->logger->info(
            "WEBHOOK event={$event} eventId={$eventId}",
            ["body" => self::redactLogBody($body)],
        );
    }

    private function logResponse(string $url, int $status, string $body): void
    {
        if (!$this->httpLogging || $this->logger === null) {
            return;
        }
        $this->logger->info(
            'HTTP response url=' . $url . ' status=' . $status,
            ['body' => self::redactLogBody($body)],
        );
    }

    /** @param array<string, string> $headers */
    private static function redactLogHeaders(array $headers): array
    {
        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), self::SENSITIVE_LOG_HEADERS, true)) {
                $headers[$name] = '<redacted>';
            }
        }
        return $headers;
    }

    private static function redactLogBody(?string $body): string
    {
        if ($body === null || $body === '') {
            return '';
        }
        try {
            $value = json_decode($body, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            return json_encode(
                self::redactLogValue($value),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException) {
            return '<non-json ' . strlen($body) . ' bytes>';
        }
    }

    private static function redactLogValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        foreach ($value as $key => $child) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_LOG_BODY_KEYS, true)) {
                $value[$key] = '<redacted>';
                continue;
            }
            $value[$key] = self::redactLogValue($child);
        }
        return $value;
    }


    private function sendHttp(string $method, string $url, array $headers, ?string $body): array
    {
        return $this->sendHttpWithGuzzle($method, $url, $headers, $body);
    }

    private static function validateCaBundle(?string $caBundle): ?string
    {
        if ($caBundle === null || trim($caBundle) === '') {
            return null;
        }
        $path = trim($caBundle);
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException("caBundle must be a readable PEM CA bundle file: {$path}");
        }
        return $path;
    }

    private static function resolveCaFile(): string
    {
        return CaBundle::getSystemCaRootBundlePath();
    }

    private function sendHttpWithGuzzle(string $method, string $url, array $headers, ?string $body): array
    {
        if (($cookie = $this->cookies->headerFor($url)) !== null) {
            self::setHeaderIfMissing($headers, 'Cookie', $cookie);
        }
        $options = [
            'headers' => $headers,
            'http_errors' => false,
            'allow_redirects' => false,
            'timeout' => $this->timeoutSeconds,
            'verify' => $this->verifyTls ? ($this->caBundle ?? self::resolveCaFile()) : false,
        ];
        if ($body !== null) {
            $options['body'] = $body;
        }
        try {
            $response = (new GuzzleClient())->request($method, $url, $options);
        } catch (GuzzleException $exception) {
            throw new ApiException('Luminal API request failed. ' . $exception->getMessage(), 0, null, null, $exception);
        }
        $responseHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $responseHeaders[] = $name . ': ' . $value;
            }
        }
        $this->cookies->storeFromResponse($url, $responseHeaders);
        $responseBody = (string)$response->getBody();
        if (strlen($responseBody) > self::DEFAULT_MAX_RESPONSE_BODY_BYTES) {
            throw new ApiException(
                'Luminal API response body exceeds 1048576 bytes.',
                $response->getStatusCode(),
            );
        }
        return ['status' => $response->getStatusCode(), 'body' => $responseBody];
    }

    /** @return array<string, string> */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            if (!is_string($name) || !preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D', $name)) {
                throw new \InvalidArgumentException('HTTP header name is invalid.');
            }
            if (!is_string($value) || preg_match('/[\r\n]/', $value)) {
                throw new \InvalidArgumentException("HTTP header '{$name}' value is invalid.");
            }
            self::setHeader($normalized, $name, $value);
        }
        return $normalized;
    }

    /** @param array<string, string> $headers */
    private static function setHeader(array &$headers, string $name, string $value): void
    {
        foreach (array_keys($headers) as $existingName) {
            if (strcasecmp($existingName, $name) === 0) {
                unset($headers[$existingName]);
            }
        }
        $headers[$name] = $value;
    }

    /** @param array<string, string> $headers */
    private static function setHeaderIfMissing(array &$headers, string $name, string $value): void
    {
        foreach (array_keys($headers) as $existingName) {
            if (strcasecmp($existingName, $name) === 0) {
                return;
            }
        }
        $headers[$name] = $value;
    }

    private static function validatePath(string $path): void
    {
        if (!str_starts_with($path, '/') || preg_match('/[\r\n]/', $path)) {
            throw new \InvalidArgumentException("Path must start with '/' and contain no line breaks.");
        }
    }

    private function getTokenData(): ?array
    {
        return $this->cachedToken;
    }

    private static function validateTimeout(float $timeoutSeconds): float
    {
        if (!is_finite($timeoutSeconds) || $timeoutSeconds <= 0) {
            throw new \InvalidArgumentException('timeoutSeconds must be a finite positive number.');
        }
        return $timeoutSeconds;
    }

    private static function validateBaseUrl(string $baseUrl): string
    {
        $value = rtrim(self::requireNonBlank($baseUrl, 'baseUrl'), '/');
        $parts = parse_url($value);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['query'], $parts['fragment'])
            || isset($parts['user'], $parts['pass'])) {
            throw new \InvalidArgumentException('baseUrl must be an absolute HTTP or HTTPS URL without credentials, query, or fragment.');
        }
        return $value;
    }
}
