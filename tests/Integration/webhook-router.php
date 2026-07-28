<?php

declare(strict_types=1);

use Luminal\OpenApiSdk\Model\CardOpenStatusWebhook;
use Luminal\OpenApiSdk\Model\CardStatusWebhook;
use Luminal\OpenApiSdk\Model\SharedAccountOpenStatusWebhook;
use Luminal\OpenApiSdk\Model\TransactionWebhook;
use Luminal\OpenApiSdk\Webhook\WebhookEventType;
use Luminal\OpenApiSdk\Webhook\WebhookVerificationException;
use Luminal\OpenApiSdk\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$webhookClient = (new Client((string)($_ENV['LUMINAL_OPEN_API_BASE_URL'] ?? 'http://127.0.0.1')))
    ->withLogger(new Logger('luminal-open-api', [
        new StreamHandler('php://stdout', Logger::INFO),
    ]));

$respond = static function (int $status, string $body = ''): never {
    http_response_code($status);
    echo $body;
    exit;
};
$directory = (string)getenv('LUMINAL_OPEN_API_WEBHOOK_EVENT_DIR');
$path = (string)getenv('LUMINAL_OPEN_API_WEBHOOK_PATH');
$keyFile = (string)getenv('LUMINAL_OPEN_API_WEBHOOK_PUBLIC_KEY_FILE');
if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) !== $path) {
    $respond(404);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $respond(405);
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 1048576) {
    $respond(413);
}
$body = file_get_contents('php://input', false, null, 0, 1048577);
if (!is_string($body) || strlen($body) > 1048576) {
    $respond(413);
}
$event = trim((string)($_SERVER['HTTP_EVENT'] ?? ''));
$targets = [
    WebhookEventType::CARD_OPEN_STATUS,
    WebhookEventType::CARD_STATUS,
    WebhookEventType::SHARED_ACCOUNT_OPEN_STATUS,
    WebhookEventType::SHARE_ACCOUNT_FUND_TRANSACTIONS,
];
if (!in_array($event, $targets, true)) {
    $respond(200);
}

try {
    $verified = $webhookClient->parseWebhook(
        $event,
        (string)($_SERVER['HTTP_EVENT_ID'] ?? ''),
        $body,
        (string)($_SERVER['HTTP_SIGN'] ?? ''),
        (string)file_get_contents($keyFile),
    );
    $payload = $verified->payload;
    $correlationId = null;
    $status = null;
    if ($payload instanceof CardOpenStatusWebhook) {
        $correlationId = $payload->cardApplyTaskId;
        foreach ($payload->list ?? [] as $result) {
            $candidate = strtoupper(trim((string)$result->cardStatus));
            if ($candidate === 'SUCCESS' || $candidate === 'FAIL') {
                $status = $candidate;
                break;
            }
        }
    } elseif ($payload instanceof SharedAccountOpenStatusWebhook) {
        $correlationId = $payload->memberSharedAccountId;
        $status = strtoupper(trim((string)$payload->status));
    } elseif ($payload instanceof TransactionWebhook) {
        $correlationId = $payload->sharedAccountTransactionId;
        $status = strtoupper(trim((string)$payload->status));
    } elseif ($payload instanceof CardStatusWebhook) {
        $correlationId = $payload->memberCardId;
        $status = strtoupper(trim((string)$payload->cardStatus));
    }
    if ($correlationId === null || trim((string)$correlationId) === '' || $status === '') {
        throw new UnexpectedValueException('Webhook correlation ID or status is missing.');
    }
    if (!$payload instanceof CardStatusWebhook && $status !== 'SUCCESS' && $status !== 'FAIL') {
        $respond(200);
    }
    $file = $directory . DIRECTORY_SEPARATOR . hash('sha256', $event . "\0" . (string)$correlationId) . '.json';
    $temporary = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    file_put_contents($temporary, json_encode([
        'event' => $verified->type,
        'eventId' => $verified->eventId,
        'payload' => $verified->payload,
    ], JSON_THROW_ON_ERROR), LOCK_EX);
    rename($temporary, $file);
    $respond(200);
} catch (WebhookVerificationException $exception) {
    file_put_contents($directory . DIRECTORY_SEPARATOR . 'error.json', json_encode(['message' => $exception->getMessage()], JSON_THROW_ON_ERROR), LOCK_EX);
    $respond(401);
} catch (Throwable $exception) {
    file_put_contents($directory . DIRECTORY_SEPARATOR . 'error.json', json_encode(['message' => $exception->getMessage()], JSON_THROW_ON_ERROR), LOCK_EX);
    $respond(400);
}
