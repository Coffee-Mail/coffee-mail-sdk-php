<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class InternalServerError extends CoffeeMailException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message = 'Erro interno do servidor da API.',
        array $details = [],
        ?Throwable $previous = null,
        ?string $apiCode = null,
        ?string $requestId = null,
        int $status = 500,
    ) {
        parent::__construct(
            message: $message,
            status: $status,
            errorCode: ErrorCodes::INTERNAL_SERVER_ERROR,
            details: $details,
            previous: $previous,
            apiCode: $apiCode,
            requestId: $requestId,
        );
    }
}
