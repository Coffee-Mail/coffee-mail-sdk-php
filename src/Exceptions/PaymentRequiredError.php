<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class PaymentRequiredError extends CoffeeMailException
{
    public function __construct(
        string $message = 'Limite de cota atingido ou pagamento pendente.',
        mixed $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            status: 402,
            errorCode: 'PAYMENT_REQUIRED',
            details: $details,
            previous: $previous,
        );
    }
}
