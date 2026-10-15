<?php

declare(strict_types=1);

namespace CoffeeMail\Http;

use CoffeeMail\Exceptions\AuthenticationError;
use CoffeeMail\Exceptions\CoffeeMailException;
use CoffeeMail\Exceptions\ConflictError;
use CoffeeMail\Exceptions\ErrorCodes;
use CoffeeMail\Exceptions\ForbiddenError;
use CoffeeMail\Exceptions\InternalServerError;
use CoffeeMail\Exceptions\NotFoundError;
use CoffeeMail\Exceptions\PaymentRequiredError;
use CoffeeMail\Exceptions\RateLimitError;
use CoffeeMail\Exceptions\ValidationError;
use CoffeeMail\I18n\I18n;

/**
 * Converte uma resposta HTTP de erro da API em um erro tipado do SDK.
 *
 * @internal
 */
final class ErrorMapper
{
    private const REQUEST_ID_HEADER = 'x-request-id';
    private const RETRY_AFTER_HEADER = 'retry-after';
    private const RETRY_AFTER_PATTERN = '/^\d{1,9}$/';

    /**
     * @param array<string, string> $headers cabeçalhos com nomes em minúsculas
     */
    public static function fromResponse(
        int $status,
        string $rawBody,
        array $headers,
        string $locale,
    ): CoffeeMailException {
        $decoded = json_decode($rawBody, true);
        $root = is_array($decoded) ? $decoded : [];
        $error = isset($root['error']) && is_array($root['error']) ? $root['error'] : [];

        $apiCode = isset($error['code']) && is_string($error['code']) && $error['code'] !== '' ? $error['code'] : null;
        $message = self::message($status, $error, $root, is_array($decoded) ? '' : $rawBody, $locale);
        $details = array_merge(self::map($error['meta'] ?? null), self::map($error['details'] ?? null));
        $requestId = $headers[self::REQUEST_ID_HEADER] ?? null;

        return match ($status) {
            400 => new ValidationError($message, $details, null, $apiCode, $requestId),
            401 => new AuthenticationError($message, $details, null, $apiCode, $requestId),
            402 => new PaymentRequiredError($message, $details, null, $apiCode, $requestId),
            403 => new ForbiddenError($message, $details, null, $apiCode, $requestId),
            404 => new NotFoundError($message, $details, null, $apiCode, $requestId),
            409 => new ConflictError($message, $details, null, $apiCode, $requestId),
            429 => new RateLimitError(
                $message,
                self::retryAfter($headers[self::RETRY_AFTER_HEADER] ?? null),
                $details,
                null,
                $apiCode,
                $requestId,
            ),
            500, 502, 503, 504 => new InternalServerError($message, $details, null, $apiCode, $requestId, $status),
            default => new CoffeeMailException(
                message: $message,
                status: $status,
                errorCode: $apiCode ?? ErrorCodes::API_ERROR,
                details: $details,
                apiCode: $apiCode,
                requestId: $requestId,
            ),
        };
    }

    /**
     * @param array<mixed> $error
     * @param array<mixed> $root
     */
    private static function message(int $status, array $error, array $root, string $rawBody, string $locale): string
    {
        $candidates = [$error['message'] ?? null, $root['message'] ?? null, $rawBody];
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }
        }

        return I18n::getMessage($status === 429 ? 'rate_limit_exceeded' : 'unexpected_error', $locale);
    }

    /**
     * @return array<string, mixed>
     */
    private static function map(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $key => $item) {
            $result[(string) $key] = $item;
        }

        return $result;
    }

    private static function retryAfter(?string $value): ?int
    {
        $trimmed = trim((string) $value);

        return preg_match(self::RETRY_AFTER_PATTERN, $trimmed) === 1 ? (int) $trimmed : null;
    }
}
