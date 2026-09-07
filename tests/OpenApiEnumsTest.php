<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests;

use Luminal\OpenApiSdk\Model\OpenApiScope;
use Luminal\OpenApiSdk\Model\RechargeCardOperationStatus;
use Luminal\OpenApiSdk\Model\RechargeCardOperationType;
use Luminal\OpenApiSdk\Model\SettleStatus;
use PHPUnit\Framework\TestCase;

final class OpenApiEnumsTest extends TestCase
{
    public function testExposesNewScopeWireCodes(): void
    {
        self::assertSame('openapi:card:read', OpenApiScope::CARD_READ->code());
    }

    public function testExposesSettlementAndRechargeOperationValues(): void
    {
        self::assertSame('SETTLED', SettleStatus::SETTLED->value);
        self::assertSame('MODIFY_LIMITS', RechargeCardOperationType::MODIFY_LIMITS->value);
        self::assertSame('FAIL', RechargeCardOperationStatus::FAIL->value);
    }
}
