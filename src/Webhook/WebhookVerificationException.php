<?php

declare(strict_types=1);

namespace Luminal\OpenApiSdk\Webhook;

use RuntimeException;

/** Raised when a webhook cannot be authenticated or decoded. */
final class WebhookVerificationException extends RuntimeException
{
}
