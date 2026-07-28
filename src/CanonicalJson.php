<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk;

use DateTimeInterface;
use JsonException;
use JsonSerializable;
use Luminal\OpenApiSdk\Model\JsonModel;

/** Canonical JSON for requests and card-issuance signatures. */
final class CanonicalJson
{
    private const JS_SAFE_INTEGER_LIMIT = '9007199254740991';

    private function __construct()
    {
    }

    /** @throws JsonException when the value cannot be encoded as JSON. */
    public static function encode(mixed $value): string
    {
        return self::encodeValue($value, false);
    }

    /** Encodes the numeric form required by card-issuance signatures. */
    public static function encodeForSignature(mixed $value): string
    {
        return self::encodeValue($value, true);
    }

    /**
     * Encodes explicitly identified numeric fields using the Long/BigDecimal
     * wire rules. Kept for callers signing untyped arrays.
     *
     * @param list<string> $fieldNames
     */
    public static function encodeNumericFields(mixed $value, array $fieldNames): string
    {
        $fields = [];
        foreach ($fieldNames as $fieldName) {
            $fields[(string) $fieldName] = 'auto';
        }
        return self::encodeObjectValue($value, $fields, true);
    }

    /** Applies the JavaScript safe-integer rule to decoded JSON integers. */
    public static function normalizeDecoded(mixed $value): mixed
    {
        if (is_int($value) && self::isOutsideJavaScriptSafeIntegerRange($value)) {
            return (string) $value;
        }
        if (!is_array($value)) {
            return $value;
        }
        return array_map(self::normalizeDecoded(...), $value);
    }

    /** @param array<string, 'long'|'decimal'|'auto'> $numericFields */
    private static function encodeObjectValue(mixed $value, array $numericFields, bool $forSignature): string
    {
        if ($value instanceof JsonModel) {
            $modelNumericFields = $value->numericJsonFieldTypes();
            foreach ($value->numericJsonFields() as $fieldName) {
                $modelNumericFields[$fieldName] ??= 'auto';
            }
            $numericFields = array_merge($numericFields, $modelNumericFields);
            $value = $value->toArray();
        } elseif ($value instanceof JsonSerializable) {
            $value = $value->jsonSerialize();
        }
        if (is_object($value)) {
            $value = get_object_vars($value);
        }
        if (!is_array($value) || array_is_list($value)) {
            return self::encodeValue($value, $forSignature);
        }

        ksort($value, SORT_STRING);
        $items = [];
        foreach ($value as $key => $item) {
            if ($item === null) {
                continue;
            }
            $items[] = self::encodeValue((string) $key, $forSignature) . ':'
                . self::encodeValue($item, $forSignature, $numericFields[(string) $key] ?? null);
        }
        return '{' . implode(',', $items) . '}';
    }

    private static function encodeValue(mixed $value, bool $forSignature, ?string $numericType = null): string
    {
        if ($value instanceof DateTimeInterface) {
            $microseconds = $value->format('u');
            $value = $microseconds === '000000'
                ? $value->format('Y-m-d\\TH:i:s')
                : rtrim(rtrim($value->format('Y-m-d\\TH:i:s.u'), '0'), '.');
        }
        if ($value instanceof JsonModel) {
            return self::encodeObjectValue($value, [], $forSignature);
        }
        if ($value instanceof JsonSerializable) {
            $value = $value->jsonSerialize();
        }
        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if ($numericType !== null) {
            return self::encodeNumeric($value, $numericType, $forSignature);
        }
        if (is_array($value)) {
            if (array_is_list($value)) {
                return '[' . implode(',', array_map(
                    static fn (mixed $item): string => self::encodeValue($item, $forSignature),
                    $value,
                )) . ']';
            }
            return self::encodeObjectValue($value, [], $forSignature);
        }
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
        );
    }

    private static function encodeNumeric(mixed $value, string $numericType, bool $forSignature): string
    {
        if (is_array($value)) {
            return '[' . implode(',', array_map(
                static fn (mixed $item): string => self::encodeNumeric(
                    $item,
                    $numericType === 'auto' ? self::inferNumericType($item) : $numericType,
                    $forSignature,
                ),
                $value,
            )) . ']';
        }
        if ($numericType === 'decimal') {
            return self::encodeNumber($value);
        }
        if ($numericType === 'auto') {
            $numericType = self::inferNumericType($value);
        }
        return $numericType === 'long' ? self::encodeLong($value, $forSignature) : self::encodeNumber($value);
    }

    private static function inferNumericType(mixed $value): string
    {
        return is_string($value) && preg_match('/[.eE]/', $value) === 1 ? 'decimal' : 'long';
    }

    private static function encodeLong(mixed $value, bool $forSignature): string
    {
        if (is_int($value)) {
            $normalized = (string) $value;
        } else {
            if (!is_string($value) || !preg_match('/^[+-]?[0-9]+$/D', $value)) {
                throw new \InvalidArgumentException('Long field must be a signed 64-bit integer.');
            }

            $negative = str_starts_with($value, '-');
            $digits = ltrim($value, '+-0');
            $digits = $digits === '' ? '0' : $digits;
            $normalized = ($negative && $digits !== '0' ? '-' : '') . $digits;
        }

        return !$forSignature && self::isOutsideJavaScriptSafeIntegerString($normalized)
            ? json_encode($normalized, JSON_THROW_ON_ERROR)
            : $normalized;
    }

    private static function encodeNumber(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_float($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        }
        if (!is_string($value) || !preg_match('/^[+-]?(?:0|[1-9][0-9]*)(?:\\.[0-9]+)?(?:[eE][+-]?[0-9]+)?$/D', $value)) {
            throw new \InvalidArgumentException('Numeric field must be a valid JSON number.');
        }
        if (str_starts_with($value, '+')) {
            return substr($value, 1);
        }
        return $value;
    }

    private static function isOutsideJavaScriptSafeIntegerRange(int $value): bool
    {
        return self::isOutsideJavaScriptSafeIntegerString((string) $value);
    }

    private static function isOutsideJavaScriptSafeIntegerString(string $value): bool
    {
        $negative = str_starts_with($value, '-');
        $digits = ltrim($value, '+-0');
        $digits = $digits === '' ? '0' : $digits;
        return strlen($digits) > strlen(self::JS_SAFE_INTEGER_LIMIT)
            || (strlen($digits) === strlen(self::JS_SAFE_INTEGER_LIMIT)
                && strcmp($digits, self::JS_SAFE_INTEGER_LIMIT) >= 0);
    }
}
