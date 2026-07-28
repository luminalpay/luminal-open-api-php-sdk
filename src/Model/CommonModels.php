<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;

/** Standard paginated response. The list contains typed response objects. */
final class PageResult extends JsonModel
{
    /** @param list<JsonModel>|null $list */
    public function __construct(
        public readonly int|string|null $total = null,
        public readonly ?array $list = null,
        public readonly mixed $extra = null,
    ) {
    }

    /** Hydrates a page and converts each item to the requested response model. */
    public static function hydrate(array $data, string $itemClass, ?string $extraClass = null): self
    {
        if (array_is_list($data)) {
            throw new \UnexpectedValueException('Page response must be a JSON object.');
        }
        if (!is_a($itemClass, JsonModel::class, true)) {
            throw new \InvalidArgumentException('Page item class must extend JsonModel.');
        }
        $items = $data['list'] ?? null;
        if ($items !== null && !is_array($items)) {
            throw new \UnexpectedValueException('Page list must be an array.');
        }
        if ($items !== null) {
            $items = array_map(
                static fn (mixed $item): JsonModel => is_array($item)
                    ? $itemClass::fromArray($item)
                    : throw new \UnexpectedValueException('Page list items must be JSON objects.'),
                $items,
            );
        }
        $extra = $data['extra'] ?? null;
        if ($extraClass !== null && is_array($extra)) {
            if (!is_a($extraClass, JsonModel::class, true)) {
                throw new \InvalidArgumentException('Page extra class must extend JsonModel.');
            }
            $extra = $extraClass::fromArray($extra);
        }

        return new self($data['total'] ?? null, $items, $extra);
    }
}
