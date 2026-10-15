<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class PaymentRequiredError extends CoffeeMailException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message = 'Cota do plano esgotada.',
        array $details = [],
        ?Throwable $previous = null,
        ?string $apiCode = null,
        ?string $requestId = null,
    ) {
        parent::__construct(
            message: $message,
            status: 402,
            errorCode: ErrorCodes::PAYMENT_REQUIRED,
            details: $details,
            previous: $previous,
            apiCode: $apiCode,
            requestId: $requestId,
        );
    }
}
