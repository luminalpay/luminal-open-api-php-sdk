<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Api;

use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\TransportInterface;
use Luminal\OpenApiSdk\Model\CardGroupCreateRequest;
use Luminal\OpenApiSdk\Model\CardGroupDeleteRequest;
use Luminal\OpenApiSdk\Model\CardGroupRequest;
use Luminal\OpenApiSdk\Model\CardGroupResponse;
use Luminal\OpenApiSdk\Model\CardGroupUpdateRequest;
use Luminal\OpenApiSdk\Model\PageResult;

/** Card-group endpoints. */
final class CardGroupsApi
{
    private const PATH = '/open-api/v1/cards/group';

    public function __construct(private readonly TransportInterface $transport)
    {
    }

    /** Lists card groups. */
    public function list(CardGroupRequest $request): ?PageResult
    {
        return self::page($this->transport->postAuthorized(self::PATH, $request));
    }

    /** Creates a card group. */
    public function create(CardGroupCreateRequest $request): ?CardGroupResponse
    {
        $data = $this->transport->postAuthorized(self::PATH . '/create', $request);
        return $data === null ? null : CardGroupResponse::fromArray(self::object($data));
    }

    /** Updates a card-group name. */
    public function update(CardGroupUpdateRequest $request): bool
    {
        return $this->transport->postAuthorizedBoolean(self::PATH . '/update', $request);
    }

    /** Deletes a card group. */
    public function delete(CardGroupDeleteRequest $request): bool
    {
        return $this->transport->postAuthorizedBoolean(self::PATH . '/delete', $request);
    }

    private static function page(mixed $data): ?PageResult
    {
        if ($data === null) {
            return null;
        }
        if (!is_array($data)) {
            throw new ApiException('Card-group response data must be a JSON object.');
        }
        return PageResult::hydrate($data, CardGroupResponse::class);
    }

    private static function object(mixed $data): array
    {
        if (!is_array($data)) {
            throw new ApiException('Card-group response data must be a JSON object.');
        }
        return $data;
    }
}

