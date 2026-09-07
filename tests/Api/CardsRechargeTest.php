<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Tests\Api;

use Luminal\OpenApiSdk\Api\CardsApi;
use Luminal\OpenApiSdk\Model\MemberCardRechargeRequest;
use Luminal\OpenApiSdk\Model\MemberCardWithdrawRequest;
use Luminal\OpenApiSdk\Model\RechargeCardOperationRecordRequest;
use Luminal\OpenApiSdk\Tests\Support\EndpointTestCase;

final class CardsRechargeTest extends EndpointTestCase
{
    public function testRechargesWithoutASignature(): void
    {
        $request = new MemberCardRechargeRequest(5, 25.00, 'funding');
        $result = (new CardsApi($this->transport(601)))->recharge($request);

        self::assertSame(601, $result);
        $this->assertRequest('/open-api/v1/cards/recharge', $request);
        self::assertArrayNotHasKey('sign', $this->request['headers']);
    }

    public function testWithdrawsWithoutASignature(): void
    {
        $request = new MemberCardWithdrawRequest(5, 10.00, 'withdrawal');
        $result = (new CardsApi($this->transport(602)))->withdraw($request);

        self::assertSame(602, $result);
        $this->assertRequest('/open-api/v1/cards/withdraw', $request);
        self::assertArrayNotHasKey('sign', $this->request['headers']);
    }

    public function testGetsTheFirstOperationRecord(): void
    {
        $result = (new CardsApi($this->transport([
            'total' => 1,
            'list' => [[
                'memberCardOperationRecordId' => 601,
                'memberCardId' => 5,
                'cardType' => 'RECHARGE',
                'operationType' => 'RECHARGE',
                'amount' => 25,
                'balance' => 75,
                'status' => 'SUCCESS',
                'message' => 'completed',
                'createTime' => 1788171330000,
                'updateTime' => 1788171390000,
            ]],
        ])))->operationRecord(new RechargeCardOperationRecordRequest(601));

        self::assertSame(601, $result?->memberCardOperationRecordId);
        self::assertSame('RECHARGE', $result?->operationType);
        self::assertSame('SUCCESS', $result?->status);
        self::assertSame(1788171390000, $result?->updateTime);
        $this->assertJsonBodyContains('"memberCardOperationRecordId":601');
    }

    public function testListsOperationRecordsByMemberCard(): void
    {
        $request = new RechargeCardOperationRecordRequest(2, 20, null, 5);
        $result = (new CardsApi($this->transport([
            'total' => 1,
            'list' => [['memberCardId' => 5, 'operationType' => 'MODIFY_LIMITS', 'status' => 'PROCESSING']],
        ])))->operationRecords($request);

        self::assertSame(1, $result?->total);
        self::assertSame('MODIFY_LIMITS', $result?->list[0]->operationType);
        $this->assertRequest('/open-api/v1/cards/operation-record', $request);
        $this->assertJsonBodyContains('"pageNo":2');
        $this->assertJsonBodyContains('"memberCardId":5');
    }
}
