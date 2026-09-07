<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Api;

use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\Model\CardHolderCardPageRequest;
use Luminal\OpenApiSdk\Model\CardHolderCardResponse;
use Luminal\OpenApiSdk\Model\CardHolderCountryResponse;
use Luminal\OpenApiSdk\Model\CardHolderCreateRequest;
use Luminal\OpenApiSdk\Model\CardHolderDetailResponse;
use Luminal\OpenApiSdk\Model\CardHolderModifyRequest;
use Luminal\OpenApiSdk\Model\CardHolderPageRequest;
use Luminal\OpenApiSdk\Model\CardHolderPageResponse;
use Luminal\OpenApiSdk\Model\JsonModel;
use Luminal\OpenApiSdk\Model\PageResult;
use Luminal\OpenApiSdk\TransportInterface;

/** Cardholder management endpoints. */
final class CardHoldersApi
{
    private const PATH = '/open-api/v1/card-holders';

    public function __construct(private readonly TransportInterface $transport)
    {
    }

    /** Creates a cardholder and returns its identifier. */
    public function add(CardHolderCreateRequest $request): int|string|null
    {
        $data = $this->transport->postAuthorized(self::PATH . '/add', $request);
        return self::identifier($data, 'Cardholder add response data must be an identifier.');
    }

    /** Lists active countries and regions available for cardholder information. */
    public function countries(): ?array
    {
        $data = $this->transport->getAuthorized(self::PATH . '/countries');
        if ($data === null) {
            return null;
        }
        if (!is_array($data) || !array_is_list($data)) {
            throw new ApiException('Cardholder country response data must be an array.');
        }
        return array_map(
            static fn (mixed $item): CardHolderCountryResponse => is_array($item)
                ? CardHolderCountryResponse::fromArray($item)
                : throw new ApiException('Cardholder country items must be objects.'),
            $data,
        );
    }

    /** Updates all editable fields of a cardholder. */
    public function modify(CardHolderModifyRequest $request): void
    {
        $this->transport->postAuthorized(self::PATH . '/modify', $request);
    }

    /** Retrieves cardholder details by identifier. */
    public function detail(int|string $cardHolderId): ?CardHolderDetailResponse
    {
        $id = self::pathIdentifier($cardHolderId);
        $data = $this->transport->postSerializedAuthorized(self::PATH . '/info/' . $id, '{}');
        return $data === null ? null : CardHolderDetailResponse::fromArray(self::object($data));
    }

    /** Lists cardholders owned by the current member. */
    public function page(CardHolderPageRequest $request): ?PageResult
    {
        return self::hydratePage(
            $this->transport->postAuthorized(self::PATH . '/page', $request),
            CardHolderPageResponse::class,
        );
    }

    /** Lists cards associated with a cardholder. */
    public function associatedCards(CardHolderCardPageRequest $request): ?PageResult
    {
        return self::hydratePage(
            $this->transport->postAuthorized(self::PATH . '/card/page', $request),
            CardHolderCardResponse::class,
        );
    }

    /** @param class-string<JsonModel> $itemClass */
    private static function hydratePage(mixed $data, string $itemClass): ?PageResult
    {
        if ($data === null) {
            return null;
        }
        if (!is_array($data)) {
            throw new ApiException('Cardholder response data must be a JSON object.');
        }
        return PageResult::hydrate($data, $itemClass);
    }

    private static function object(mixed $data): array
    {
        if (!is_array($data)) {
            throw new ApiException('Cardholder response data must be a JSON object.');
        }
        return $data;
    }

    private static function identifier(mixed $data, string $message): int|string|null
    {
        if ($data === null || is_int($data) || is_string($data)) {
            return $data;
        }
        throw new ApiException($message);
    }

    private static function pathIdentifier(int|string $value): string
    {
        if (is_int($value)) {
            if ($value < 1) {
                throw new \InvalidArgumentException('cardHolderId must be greater than zero.');
            }
            return (string) $value;
        }
        if (!preg_match('/^\+?[0-9]+$/D', $value)) {
            throw new \InvalidArgumentException('cardHolderId must be a positive integer.');
        }
        $normalized = ltrim($value, '+0');
        if ($normalized === '') {
            throw new \InvalidArgumentException('cardHolderId must be greater than zero.');
        }
        if (strlen($normalized) > 19
            || (strlen($normalized) === 19 && strcmp($normalized, '9223372036854775807') > 0)) {
            throw new \InvalidArgumentException('cardHolderId exceeds the signed 64-bit integer range.');
        }
        return $normalized;
    }
}
