<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk;

use Luminal\OpenApiSdk\Api\AccountsApi;
use Luminal\OpenApiSdk\Api\AuthApi;
use Luminal\OpenApiSdk\Api\CardGroupsApi;
use Luminal\OpenApiSdk\Api\CardHoldersApi;
use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\Api\SharedAccountsApi;
use Luminal\OpenApiSdk\Api\TransactionsApi;
use Luminal\OpenApiSdk\Webhook\WebhookEvent;
use Luminal\OpenApiSdk\Webhook\WebhookVerifier;
use Psr\Log\LoggerInterface;

/** Immutable facade exposing every Luminal Open API controller group. */
final class Client
{
    private TransportInterface $transport;
    private AuthApi $auth;
    private AccountsApi $accounts;
    private TransactionsApi $transactions;
    private SharedAccountsApi $sharedAccounts;
    private CardsApi $cards;
    private CardHoldersApi $cardHolders;
    private CardGroupsApi $cardGroups;

    /**
     * Accepts a base URL for the built-in transport or a custom transport implementation.
     *
     * @param string|TransportInterface $baseUrl
     * @param string|null $bearerToken
     * @param string|null $caBundle Optional readable PEM CA bundle for private enterprise CAs.
     */
    public function __construct(string|TransportInterface $baseUrl, ?string $bearerToken = null, ?string $caBundle = null)
    {
        if ($baseUrl instanceof TransportInterface) {
            if ($bearerToken !== null) {
                throw new \InvalidArgumentException('bearerToken cannot be combined with a custom transport.');
            }
            if ($caBundle !== null) {
                throw new \InvalidArgumentException('caBundle cannot be combined with a custom transport.');
            }
            $this->initialize($baseUrl);
            return;
        }
        $this->initialize(new HttpTransport($baseUrl, $bearerToken, caBundle: $caBundle));
    }

    public function withLogger(LoggerInterface $logger): self
    {
        if (!$this->transport instanceof HttpTransport) {
            throw new \InvalidArgumentException('Logger can only be set on the built-in HttpTransport.');
        }
        $client = clone $this;
        $client->initialize($client->transport->withLogger($logger));
        return $client;
    }

    public function withHttpLogging(bool $enabled = true): self
    {
        if (!$this->transport instanceof HttpTransport) {
            throw new \InvalidArgumentException('HTTP logging can only be set on the built-in HttpTransport.');
        }
        $client = clone $this;
        $client->initialize($client->transport->withHttpLogging($enabled));
        return $client;
    }

    public function withLocale(string $locale): self
    {
        $client = clone $this;
        if (!$client->transport instanceof HttpTransport) {
            throw new \InvalidArgumentException('Locale can only be set on the built-in HttpTransport.');
        }
        $client->initialize($client->transport->withLocale($locale));
        return $client;
    }

    public function withTokenProvider(callable $tokenProvider, int $unauthorizedRetryCount = 1, string $locale = 'en'): self
    {
        if (!$this->transport instanceof HttpTransport) {
            throw new \InvalidArgumentException('Token provider can only be set on the built-in HttpTransport.');
        }
        $client = clone $this;
        $client->initialize($this->transport->withTokenProvider($tokenProvider, $unauthorizedRetryCount, $locale));
        return $client;
    }

    /** Returns a copy configured with a different bearer token. */
    public function withBearerToken(string $token): self
    {
        $client = clone $this;
        $client->initialize($this->transport->withBearerToken($token));
        return $client;
    }

    public function parseWebhook(string $event, string $eventId, string $rawBody, string $signature, string $publicKeyPem): WebhookEvent
    {
        $webhook = WebhookVerifier::parse($event, $eventId, $rawBody, $signature, $publicKeyPem);
        if ($this->transport instanceof HttpTransport) {
            $this->transport->logWebhook($event, $eventId, $rawBody);
        }
        return $webhook;
    }

    public function auth(): AuthApi
    {
        return $this->auth;
    }

    public function accounts(): AccountsApi
    {
        return $this->accounts;
    }

    public function transactions(): TransactionsApi
    {
        return $this->transactions;
    }

    public function sharedAccounts(): SharedAccountsApi
    {
        return $this->sharedAccounts;
    }

    public function cards(): CardsApi
    {
        return $this->cards;
    }

    public function cardHolders(): CardHoldersApi
    {
        return $this->cardHolders;
    }

    public function cardGroups(): CardGroupsApi
    {
        return $this->cardGroups;
    }

    private function initialize(TransportInterface $transport): void
    {
        $this->transport = $transport;
        $this->auth = new AuthApi($transport);
        $this->accounts = new AccountsApi($transport);
        $this->transactions = new TransactionsApi($transport);
        $this->sharedAccounts = new SharedAccountsApi($transport);
        $this->cards = new CardsApi($transport);
        $this->cardHolders = new CardHoldersApi($transport);
        $this->cardGroups = new CardGroupsApi($transport);
    }
}
