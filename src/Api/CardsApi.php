<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Api;

use Luminal\OpenApiSdk\ApiException;
use Luminal\OpenApiSdk\CanonicalJson;
use Luminal\OpenApiSdk\HttpTransport;
use Luminal\OpenApiSdk\TransportInterface;
use Luminal\OpenApiSdk\Model\CardBinResponse;
use Luminal\OpenApiSdk\Model\CardBinsRequest;
use Luminal\OpenApiSdk\Model\CardCvvResponse;
use Luminal\OpenApiSdk\Model\CardIdRequest;
use Luminal\OpenApiSdk\Model\CardLimitResponse;
use Luminal\OpenApiSdk\Model\CardLimitUpdateRequest;
use Luminal\OpenApiSdk\Model\CardTransactionResponse;
use Luminal\OpenApiSdk\Model\CardTransactionsRequest;
use Luminal\OpenApiSdk\Model\IssueCardDetailsRequest;
use Luminal\OpenApiSdk\Model\IssueCardDetailsResponse;
use Luminal\OpenApiSdk\Model\IssueCardRequest;
use Luminal\OpenApiSdk\Model\JsonModel;
use Luminal\OpenApiSdk\Model\MemberCardPageRequest;
use Luminal\OpenApiSdk\Model\MemberCardRechargeRequest;
use Luminal\OpenApiSdk\Model\MemberCardResponse;
use Luminal\OpenApiSdk\Model\MemberCardWithdrawRequest;
use Luminal\OpenApiSdk\Model\PageResult;
use Luminal\OpenApiSdk\Model\RechargeCardOperationRecordRequest;
use Luminal\OpenApiSdk\Model\RechargeCardOperationRecordResponse;
use Luminal\OpenApiSdk\RsaSignatures;

/** Card endpoints, including signed card issuance. */
final class CardsApi
{
    private const PATH = '/open-api/v1/cards';

    public function __construct(private readonly TransportInterface $transport)
    {
    }

    /** Lists available card BIN products, optionally filtered by card pool. */
    public function bins(CardBinsRequest $request): ?PageResult
    {
        return self::page($this->post('/bins', $request), CardBinResponse::class);
    }

    /** Submits a card issuance request with a Base64 signature or RSA private key. */
    public function issue(IssueCardRequest $request, string|\OpenSSLAsymmetricKey $signatureOrPrivateKey): int|string|null
    {
        $body = $this->transport->serialize($request);
        $signature = $signatureOrPrivateKey instanceof \OpenSSLAsymmetricKey
            ? RsaSignatures::sign(CanonicalJson::encodeForSignature($request), $signatureOrPrivateKey)
            : (str_contains($signatureOrPrivateKey, '-----BEGIN')
                ? RsaSignatures::sign(CanonicalJson::encodeForSignature($request), $signatureOrPrivateKey)
                : HttpTransport::requireNonBlank($signatureOrPrivateKey, 'signature'));
        return $this->issueSerialized($body, $signature);
    }

    /** Backward-compatible alias for private-key issuance. */
    public function issueWithPrivateKey(IssueCardRequest $request, string|\OpenSSLAsymmetricKey $privateKey): int|string|null
    {
        return $this->issue($request, $privateKey);
    }

    /** Lists issued cards. */
    public function list(MemberCardPageRequest $request): ?PageResult
    {
        return self::page($this->post('/list', $request), MemberCardResponse::class);
    }

    /** Retrieves sensitive card number, CVV, and expiry data. */
    public function cvv(CardIdRequest $request): ?CardCvvResponse
    {
        return self::objectResult($this->post('/cvv', $request), CardCvvResponse::class);
    }

    /** Lists card transactions. */
    public function transactions(CardTransactionsRequest $request): ?PageResult
    {
        return self::page($this->post('/transactions', $request), CardTransactionResponse::class);
    }

    /** Retrieves the current card limit. */
    public function limit(CardIdRequest $request): ?CardLimitResponse
    {
        return self::objectResult($this->post('/limit', $request), CardLimitResponse::class);
    }

    /** Updates a card limit. */
    public function modifyLimit(CardLimitUpdateRequest $request): bool
    {
        return $this->action('/limit/modify', $request);
    }

    /** Updates a card limit asynchronously and returns its operation-record identifier. */
    public function modifyLimitAsync(CardLimitUpdateRequest $request): int|string|null
    {
        return $this->post('/limit/modify/operation-record', $request);
    }

    /** Freezes a card. */
    public function freeze(CardIdRequest $request): bool
    {
        return $this->action('/freeze', $request);
    }

    /** Unfreezes a card. */
    public function unfreeze(CardIdRequest $request): bool
    {
        return $this->action('/unfreeze', $request);
    }

    /** Cancels a card. */
    public function cancel(CardIdRequest $request): bool
    {
        return $this->action('/cancel', $request);
    }

    /** Submits a recharge-card funding request without a signature. */
    public function recharge(MemberCardRechargeRequest $request): int|string|null
    {
        return $this->post('/recharge', $request);
    }

    /** Submits a recharge-card withdrawal request without a signature. */
    public function withdraw(MemberCardWithdrawRequest $request): int|string|null
    {
        return $this->post('/withdraw', $request);
    }

    /** Returns the first matching card operation record, or null when none exists. */
    public function operationRecord(RechargeCardOperationRecordRequest $request): ?RechargeCardOperationRecordResponse
    {
        $page = $this->operationRecords($request);
        if ($page === null || $page->list === null || $page->list === []) {
            return null;
        }
        return $page->list[0];
    }

    /** Lists SHARED or RECHARGE card operation records with pagination. */
    public function operationRecords(RechargeCardOperationRecordRequest $request): ?PageResult
    {
        return self::page($this->post('/operation-record', $request), RechargeCardOperationRecordResponse::class);
    }

    /** Retrieves per-card results for a card issuance task. */
    public function issueDetails(IssueCardDetailsRequest $request): ?array
    {
        $data = $this->post('/issue/detail', $request);
        if ($data === null) {
            return null;
        }
        if (!is_array($data) || !array_is_list($data)) {
            throw new ApiException('Card issue-details response data must be an array.');
        }
        return array_map(
            static fn (mixed $item): IssueCardDetailsResponse => is_array($item)
                ? IssueCardDetailsResponse::fromArray($item)
                : throw new ApiException('Card issue-details items must be objects.'),
            $data,
        );
    }

    private function issueSerialized(string $body, string $signature): int|string|null
    {
        return $this->transport->postSerializedAuthorized(self::PATH . '/issue', $body, ['sign' => $signature]);
    }

    private function post(string $path, JsonModel $request): mixed
    {
        return $this->transport->postAuthorized(self::PATH . $path, $request);
    }

    private function action(string $path, JsonModel $request): bool
    {
        return $this->transport->postAuthorizedBoolean(self::PATH . $path, $request);
    }

    /** @param class-string<JsonModel> $itemClass */
    private static function page(mixed $data, string $itemClass): ?PageResult
    {
        if ($data === null) {
            return null;
        }
        if (!is_array($data)) {
            throw new ApiException('Card response data must be a JSON object.');
        }
        return PageResult::hydrate($data, $itemClass);
    }

    /** @param class-string<JsonModel> $class */
    private static function objectResult(mixed $data, string $class): ?JsonModel
    {
        return $data === null ? null : $class::fromArray(self::object($data));
    }

    private static function object(mixed $data): array
    {
        if (!is_array($data)) {
            throw new ApiException('Card response data must be a JSON object.');
        }
        return $data;
    }
}
