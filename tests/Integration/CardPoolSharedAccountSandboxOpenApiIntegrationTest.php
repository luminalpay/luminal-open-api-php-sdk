<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;

/** Full shared-card Sandbox flow backed by a selected card pool. */
#[Group('integration')]
final class CardPoolSharedAccountSandboxOpenApiIntegrationTest extends ShareCardSandboxOpenApiIntegrationTest
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        parent::useCardPoolFlow();
    }

    public function testListsCardPoolsFromSandbox(): void
    {
        self::assertNotNull(self::selectedCardPool());
    }

    public function testCreatesSharedAccountWithCardBinAndCardPoolFromSandbox(): void
    {
        self::assertNotNull(self::createSharedAccountWithCardBinAndCardPool());
    }
}
