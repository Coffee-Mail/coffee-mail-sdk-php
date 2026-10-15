<?php

declare(strict_types=1);

namespace CoffeeMail\Http;

use CoffeeMail\Exceptions\CoffeeMailException;
use CoffeeMail\Exceptions\ErrorCodes;
use CoffeeMail\I18n\I18n;

/**
 * Transforma status, cabeçalhos e corpo HTTP no envelope `CoffeeMailResponse`.
 *
 * @internal
 */
final class ResponseParser
{
    /**
     * @param array<string, string> $headers cabeçalhos com nomes em minúsculas
     * @return CoffeeMailResponse<mixed>
     */
    public static function parse(int $status, string $rawBody, array $headers, string $locale): CoffeeMailResponse
    {
        if ($status < 200 || $status >= 300) {
            return new CoffeeMailResponse(
                data: null,
                error: ErrorMapper::fromResponse($status, $rawBody, $headers, $locale),
                statusCode: $status,
            );
        }

        if ($status === 204 || trim($rawBody) === '') {
            return new CoffeeMailResponse(data: null, error: null, statusCode: $status);
        }

        $decoded = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new CoffeeMailResponse(
                data: null,
                error: new CoffeeMailException(
                    message: I18n::getMessage('invalid_json', $locale),
                    status: $status,
                    errorCode: ErrorCodes::INVALID_RESPONSE,
                    requestId: $headers['x-request-id'] ?? null,
                ),
                statusCode: $status,
            );
        }

        return new CoffeeMailResponse(data: $decoded, error: null, statusCode: $status);
    }
}
