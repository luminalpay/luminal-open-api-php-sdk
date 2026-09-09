<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Api;

use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\Model\CardPoolRequest;
use Luminal\OpenApiSdk\Model\CardPoolResponse;
use Luminal\OpenApiSdk\TransportInterface;

/** Card-pool endpoints. */
final class CardPoolsApi
{
    private const PATH = '/open-api/v1/cards/pools';

    public function __construct(private readonly TransportInterface $transport)
    {
    }

    /**
     * Lists card pools available to the current member.
     *
     * @return list<CardPoolResponse>|null
     */
    public function list(CardPoolRequest $request): ?array
    {
        $data = $this->transport->postAuthorized(self::PATH, $request);
        if ($data === null) {
            return null;
        }
        if (!is_array($data) || !array_is_list($data)) {
            throw new ApiException('Card-pool response data must be a JSON array.');
        }

        return array_map(
            static fn (mixed $item): CardPoolResponse => is_array($item)
                ? CardPoolResponse::fromArray($item)
                : throw new ApiException('Card-pool response items must be JSON objects.'),
            $data,
        );
    }
}
