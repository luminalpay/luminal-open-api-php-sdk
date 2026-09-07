<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Model;

use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/** Base class for typed SDK request and response objects. */
abstract class JsonModel implements JsonSerializable
{
    /** Returns public model fields for transport serialization. */
    public function toArray(): array
    {
        $this->validate();
        return self::wireValue(get_object_vars($this));
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** Returns fields whose wire type is Java Long or BigDecimal. */
    public function numericJsonFields(): array
    {
        return array_keys($this->numericJsonFieldTypes());
    }

    /** @return array<string, 'long'|'decimal'> */
    public function numericJsonFieldTypes(): array
    {
        $fields = [];
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $types = self::typeNames($property->getType());
            if (in_array('float', $types, true)) {
                $fields[$property->getName()] = 'decimal';
            } elseif (in_array('int', $types, true) && in_array('string', $types, true)) {
                $fields[$property->getName()] = 'long';
            }
        }
        return $fields;
    }

    /** Prevents accidental leakage when a model is dumped by a debugger or logger. */
    public function __debugInfo(): array
    {
        $values = get_object_vars($this);
        foreach ($values as $name => $value) {
            if (preg_match('/(?:token|secret|password|cvv|cardNo|cardNumber|privateKey|signature)/i', $name)) {
                $values[$name] = '<redacted>';
            }
        }
        return $values;
    }

    /** Hydrates a model from a decoded JSON object. Unknown fields are ignored for forward compatibility. */
    public static function fromArray(array $data): static
    {
        $constructor = (new \ReflectionClass(static::class))->getConstructor();
        if ($constructor === null) {
            return new static();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (array_key_exists($name, $data)) {
                $value = $data[$name];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } else {
                $value = null;
            }
            $arguments[] = self::convertValue($value, $parameter->getType());
        }

        $model = new static(...$arguments);
        $model->validate();
        return $model;
    }

    /** Validates model scalar semantics before serialization or after hydration. */
    final public function validate(): void
    {
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            self::validateValue($property->getValue($this), $property->getType(), $property->getName());
        }
        $this->validateModel();
    }

    /** Hook for model-specific validation. */
    protected function validateModel(): void
    {
    }

    /** Converts ISO local date-time arrays used by request filters. */
    protected static function dateTimeList(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (!is_array($value)) {
            throw new \InvalidArgumentException('Date-time range must be an array.');
        }

        return array_map(static function (mixed $item): DateTimeImmutable {
            if ($item instanceof DateTimeImmutable) {
                return $item;
            }
            if ($item instanceof DateTimeInterface) {
                return DateTimeImmutable::createFromInterface($item);
            }
            if (is_string($item) && trim($item) !== '') {
                return new DateTimeImmutable($item);
            }
            throw new \InvalidArgumentException('Date-time range items must be date-time objects or ISO strings.');
        }, $value);
    }

    protected function validatePage(?int $pageNo, ?int $pageSize): void
    {
        if ($pageNo !== null && $pageNo < 1) {
            throw new \InvalidArgumentException('pageNo must be greater than zero.');
        }
        if ($pageSize !== null && $pageSize < 1) {
            throw new \InvalidArgumentException('pageSize must be greater than zero.');
        }
    }

    protected function requireNonBlank(?string $value, string $path): void
    {
        if ($value === null || trim($value) === '') {
            throw new \InvalidArgumentException($path . ' must not be blank.');
        }
    }

    protected function requirePositiveId(int|string|null $value, string $path): void
    {
        if ($value === null) {
            throw new \InvalidArgumentException($path . ' is required.');
        }
        self::validateInt64($value, $path);
        if ((is_int($value) && $value < 1)
            || (is_string($value) && (str_starts_with($value, '-') || preg_match('/^\+?0+$/D', $value)))) {
            throw new \InvalidArgumentException($path . ' must be greater than zero.');
        }
    }

    protected function validateOptionalId(int|string|null $value, string $path): void
    {
        if ($value !== null) {
            $this->requirePositiveId($value, $path);
        }
    }

    protected function requirePositiveDecimal(int|float|string|null $value, string $path): void
    {
        if ($value === null) {
            throw new \InvalidArgumentException($path . ' is required.');
        }
        self::validateDecimal($value, $path);
        if ((is_int($value) && $value < 1)
            || (is_float($value) && $value <= 0)
            || (is_string($value) && (str_starts_with($value, '-') || self::isZeroDecimal($value)))) {
            throw new \InvalidArgumentException($path . ' must be greater than zero.');
        }
    }

    protected function validateOptionalPositiveDecimal(int|float|string|null $value, string $path): void
    {
        if ($value !== null) {
            $this->requirePositiveDecimal($value, $path);
        }
    }

    /** @param list<int|string>|null $values */
    protected function validateIdentifierList(?array $values, string $path): void
    {
        if ($values === null) {
            return;
        }
        if (!array_is_list($values)) {
            throw new \InvalidArgumentException($path . ' must be a list.');
        }
        foreach ($values as $index => $value) {
            $this->requirePositiveId($value, $path . '[' . $index . ']');
        }
    }

    /** @param list<int|string>|null $values */
    protected function validateInt64Range(?array $values, string $path): void
    {
        if ($values === null) {
            return;
        }
        if (!array_is_list($values) || count($values) !== 2) {
            throw new \InvalidArgumentException($path . ' must contain exactly two values.');
        }
        foreach ($values as $index => $value) {
            self::validateInt64($value, $path . '[' . $index . ']');
        }
    }

    /** @param list<DateTimeInterface>|null $values */
    protected function validateDateTimeRange(?array $values, string $path): void
    {
        if ($values === null) {
            return;
        }
        if (!array_is_list($values) || count($values) !== 2) {
            throw new \InvalidArgumentException($path . ' must contain exactly two values.');
        }
        foreach ($values as $index => $value) {
            if (!$value instanceof DateTimeInterface) {
                throw new \InvalidArgumentException($path . '[' . $index . '] must be a date-time object.');
            }
        }
    }

    private static function convertValue(mixed $value, ?ReflectionType $type): mixed
    {
        if ($value === null || $type === null) {
            return $value;
        }

        $namedTypes = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
        foreach ($namedTypes as $namedType) {
            if (!$namedType instanceof ReflectionNamedType) {
                continue;
            }
            $name = $namedType->getName();
            if (enum_exists($name) && is_a($name, \BackedEnum::class, true)) {
                if ($value instanceof $name) {
                    return $value;
                }
                return $name::from($value);
            }
            if ($namedType->isBuiltin()) {
                if (($name === 'string' && is_string($value))
                    || ($name === 'int' && is_int($value))
                    || ($name === 'float' && is_float($value))
                    || ($name === 'bool' && is_bool($value))
                    || ($name === 'array' && is_array($value))) {
                    return $value;
                }
                continue;
            }
            if ($name === DateTimeImmutable::class || $name === DateTimeInterface::class) {
                if ($value instanceof DateTimeImmutable) {
                    return $value;
                }
                if ($value instanceof DateTimeInterface) {
                    return DateTimeImmutable::createFromInterface($value);
                }
                if (is_array($value)) {
                    return self::dateTimeArray($value);
                }
                if (is_string($value) && trim($value) !== '') {
                    return self::dateTime($value);
                }
                if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
                    return self::dateTime($value);
                }
            }
            if (is_a($name, self::class, true) && is_array($value)) {
                return $name::fromArray($value);
            }
        }

        foreach ($namedTypes as $namedType) {
            if (!$namedType instanceof ReflectionNamedType || !$namedType->isBuiltin()) {
                continue;
            }
            $name = $namedType->getName();
            if ($name === 'string' && is_scalar($value)) {
                return is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
            }
        }

        return $value;
    }

    private static function wireValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }
        if ($value instanceof self) {
            return $value->toArray();
        }
        if (is_array($value)) {
            return array_map([self::class, 'wireValue'], $value);
        }
        return $value;
    }

    private static function dateTime(int|float|string $value): DateTimeImmutable
    {
        if (is_string($value) && !is_numeric($value)) {
            return new DateTimeImmutable($value);
        }

        $milliseconds = (float) $value;
        $seconds = $milliseconds / 1000;
        $wholeSeconds = (int) floor($seconds);
        $microseconds = (int) round(($seconds - $wholeSeconds) * 1_000_000);
        if ($microseconds >= 1_000_000) {
            $wholeSeconds++;
            $microseconds = 0;
        }

        return DateTimeImmutable::createFromFormat(
            '!U.u',
            sprintf('%d.%06d', $wholeSeconds, $microseconds),
            new \DateTimeZone('UTC'),
        ) ?: throw new \UnexpectedValueException('Invalid date-time value.');
    }

    /** Converts Java/Jackson date and local-date-time array representations. */
    private static function dateTimeArray(array $value): DateTimeImmutable
    {
        if (!array_is_list($value)) {
            throw new \InvalidArgumentException('Date-time arrays must be indexed lists.');
        }

        $parts = array_values($value);
        $count = count($parts);
        if (!in_array($count, [3, 6, 7], true)) {
            throw new \InvalidArgumentException(
                'Date-time arrays must contain [year, month, day] or [year, month, day, hour, minute, second, nanosecond].',
            );
        }

        $year = self::dateComponent($parts[0], 'year');
        $month = self::dateComponent($parts[1], 'month');
        $day = self::dateComponent($parts[2], 'day');
        $hour = 0;
        $minute = 0;
        $second = 0;
        $microsecond = 0;

        if ($count >= 6) {
            $hour = self::dateComponent($parts[3], 'hour');
            $minute = self::dateComponent($parts[4], 'minute');
            $second = self::dateComponent($parts[5], 'second');
        }
        if ($count === 7) {
            $nanosecond = self::dateComponent($parts[6], 'nanosecond');
            if ($nanosecond < 0 || $nanosecond > 999_999_999) {
                throw new \InvalidArgumentException('nanosecond must be between 0 and 999999999.');
            }
            $microsecond = intdiv($nanosecond, 1_000);
        }

        if (!checkdate($month, $day, $year)
            || $hour < 0 || $hour > 23
            || $minute < 0 || $minute > 59
            || $second < 0 || $second > 59) {
            throw new \InvalidArgumentException('Date-time array contains an invalid date or time.');
        }

        return DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s.u',
            sprintf('%04d-%02d-%02d %02d:%02d:%02d.%06d', $year, $month, $day, $hour, $minute, $second, $microsecond),
            new \DateTimeZone('UTC'),
        ) ?: throw new \UnexpectedValueException('Invalid date-time array.');
    }

    private static function dateComponent(mixed $value, string $name): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^[0-9]+$/D', $value) === 1) {
            return (int) $value;
        }
        throw new \InvalidArgumentException($name . ' must be an integer in a date-time array.');
    }

    private static function validateValue(mixed $value, ?ReflectionType $type, string $path): void
    {
        if ($value === null) {
            return;
        }
        if ($value instanceof self) {
            $value->validate();
            return;
        }
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                self::validateValue($item, null, $path . '[' . $key . ']');
            }
            return;
        }

        $typeNames = self::typeNames($type);
        sort($typeNames);
        if ($typeNames === ['int', 'string']) {
            self::validateInt64($value, $path);
        } elseif ($typeNames === ['float', 'int', 'string']) {
            self::validateDecimal($value, $path);
        }
    }

    /** @return list<string> */
    private static function typeNames(?ReflectionType $type): array
    {
        if ($type instanceof ReflectionUnionType) {
            return array_values(array_filter(
                array_map(
                    static fn (ReflectionType $item): string => $item instanceof ReflectionNamedType
                        ? $item->getName()
                        : 'mixed',
                    $type->getTypes(),
                ),
                static fn (string $name): bool => $name !== 'null',
            ));
        }
        return $type instanceof ReflectionNamedType ? [$type->getName()] : [];
    }

    private static function validateInt64(mixed $value, string $path): void
    {
        if (is_int($value)) {
            return;
        }
        if (!is_string($value) || !preg_match('/^[+-]?[0-9]+$/D', $value)) {
            throw new \InvalidArgumentException($path . ' must be a signed 64-bit integer.');
        }

        $negative = str_starts_with($value, '-');
        $digits = ltrim($value, '+-0');
        $digits = $digits === '' ? '0' : $digits;
        $limit = $negative ? '9223372036854775808' : '9223372036854775807';
        if (strlen($digits) > strlen($limit)
            || (strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0)) {
            throw new \InvalidArgumentException($path . ' exceeds the signed 64-bit integer range.');
        }
    }

    private static function validateDecimal(mixed $value, string $path): void
    {
        if (is_int($value)) {
            return;
        }
        if (is_float($value)) {
            if (is_finite($value)) {
                return;
            }
            throw new \InvalidArgumentException($path . ' must be a finite decimal value.');
        }
        if (!is_string($value) || !preg_match('/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?$/D', $value)) {
            throw new \InvalidArgumentException($path . ' must be a decimal value.');
        }
    }

    private static function isZeroDecimal(string $value): bool
    {
        return preg_match('/^\+?(?:(?:0+(?:\.0*)?)|(?:\.0+))(?:[eE][+-]?\d+)?$/D', $value) === 1;
    }
}
