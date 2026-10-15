<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class NetworkException extends CoffeeMailException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message = 'Falha de comunicação ou timeout na rede.',
        array $details = [],
        ?Throwable $previous = null,
        ?string $apiCode = null,
        ?string $requestId = null,
    ) {
        parent::__construct(
            message: $message,
            status: 0,
            errorCode: ErrorCodes::NETWORK_ERROR,
            details: $details,
            previous: $previous,
            apiCode: $apiCode,
            requestId: $requestId,
        );
    }
}
