<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use Luminal\OpenApiSdk\Model\AuthTokenRequest;
use Luminal\OpenApiSdk\Model\CardGroupCreateRequest;
use Luminal\OpenApiSdk\Model\CardIdRequest;
use Luminal\OpenApiSdk\Model\CardLimitUpdateRequest;
use Luminal\OpenApiSdk\Model\CardTransactionsRequest;
use Luminal\OpenApiSdk\Model\CreateSharedAccountRequest;
use Luminal\OpenApiSdk\Model\IssueCardDetailsRequest;
use Luminal\OpenApiSdk\Model\IssueCardRequest;
use Luminal\OpenApiSdk\Model\MemberCardPageRequest;
use Luminal\OpenApiSdk\Model\SharedAccountBalanceRequest;
use Luminal\OpenApiSdk\Model\SharedAccountResponse;
use Luminal\OpenApiSdk\Model\WalletInfoRequest;
use Luminal\OpenApiSdk\Model\WalletTransactionRequest;
use PHPUnit\Framework\TestCase;

final class ModelValidationTest extends TestCase
{
    public function testRejectsBlankRequiredText(): void
    {
        $this->expectExceptionMessage('appId must not be blank.');
        (new AuthTokenRequest('  ', 'secret'))->toArray();
    }

    public function testRejectsMissingRequiredRequestText(): void
    {
        $this->expectExceptionMessage('cardType must not be blank.');
        (new CardGroupCreateRequest('Travel', null))->toArray();
    }

    public function testRejectsInvalidPagination(): void
    {
        $this->expectExceptionMessage('pageNo must be greater than zero.');
        (new WalletInfoRequest(0, 20))->toArray();
    }

    public function testRejectsNonPositiveIdentifiers(): void
    {
        $this->expectExceptionMessage('memberCardId must be greater than zero.');
        (new CardIdRequest('0'))->toArray();
    }

    public function testRejectsNonPositiveAmounts(): void
    {
        $this->expectExceptionMessage('amount must be greater than zero.');
        (new SharedAccountBalanceRequest(1001, '0.00'))->toArray();
    }

    public function testRejectsInvalidIdentifierListsAndRanges(): void
    {
        try {
            (new MemberCardPageRequest(cardGroups: ['0']))->toArray();
            self::fail('Expected invalid identifier list.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('cardGroups[0] must be greater than zero.', $exception->getMessage());
        }

        $this->expectExceptionMessage('tradeTime must contain exactly two values.');
        (new CardTransactionsRequest(tradeTime: [1]))->toArray();
    }

    public function testConvertsAndValidatesDateTimeRanges(): void
    {
        $request = WalletTransactionRequest::fromArray([
            'createTime' => ['2025-01-01T00:00:00', '2025-01-02T00:00:00'],
        ]);

        self::assertInstanceOf(DateTimeImmutable::class, $request->createTime[0]);
        self::assertCount(2, $request->createTime);

        $this->expectExceptionMessage('createTime must contain exactly two values.');
        (new WalletTransactionRequest(createTime: [new DateTimeImmutable('2025-01-01')]))->toArray();
    }

    public function testHydratesMillisecondEpochDateTimes(): void
    {
        $response = SharedAccountResponse::fromArray(['createTime' => 1784281449000]);

        self::assertNotNull($response->createTime);
        self::assertSame('1784281449000', $response->createTime?->format('Uv'));
    }
    public function testOptionalFiltersRemainOptionalAndValidRequestsSerialize(): void
    {
        self::assertSame(
            ['pageNo' => null, 'pageSize' => null, 'currency' => null],
            (new WalletInfoRequest())->toArray(),
        );
        self::assertSame(
            [
                'applyCount' => 1,
                'cardBinId' => 1001,
                'cardGroupId' => null,
                'cardName' => null,
                'cardType' => null,
                'memberSharedAccountId' => 77,
                'monthLimit' => '100.00',
                'rechargeAmount' => 100,
            ],
            (new IssueCardRequest(
                applyCount: 1,
                cardBinId: 1001,
                memberSharedAccountId: 77,
                monthLimit: '100.00',
                rechargeAmount: 100,
            ))->toArray(),
        );
        self::assertSame(
            [
                'memberCardId' => 1001,
                'totalLimit' => 100,
            ],
            (new CardLimitUpdateRequest(1001, 100))->toArray(),
        );
        self::assertSame(
            ['taskId' => 1001],
            (new IssueCardDetailsRequest(1001))->toArray(),
        );
        self::assertSame(
            ['cardBinId' => 1001, 'rechargeAmount' => 100, 'accountName' => 'Travel'],
            (new CreateSharedAccountRequest(1001, 100, 'Travel'))->toArray(),
        );
    }
}


