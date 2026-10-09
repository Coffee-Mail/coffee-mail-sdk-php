<?php

declare(strict_types=1);

namespace CoffeeMail\Http;

use CoffeeMail\Exceptions\AuthenticationError;
use CoffeeMail\Exceptions\CoffeeMailException;
use CoffeeMail\Exceptions\ConflictError;
use CoffeeMail\Exceptions\ForbiddenError;
use CoffeeMail\Exceptions\InternalServerError;
use CoffeeMail\Exceptions\NetworkException;
use CoffeeMail\Exceptions\NotFoundError;
use CoffeeMail\Exceptions\PaymentRequiredError;
use CoffeeMail\Exceptions\RateLimitError;
use CoffeeMail\Exceptions\ValidationError;
use CoffeeMail\I18n\I18n;

final class CurlTransport implements HttpTransportInterface
{
    private const BASE_URL = 'https://api.coffeemail.com.br';
    private const SDK_VERSION = '0.1.0';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $locale = 'pt-BR',
        private readonly int $timeoutSeconds = 10,
    ) {
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param array<string, mixed>|list<mixed>|null $body
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @return CoffeeMailResponse<mixed>
     */
    public function request(
        string $method,
        string $path,
        ?array $body = null,
        array $query = [],
        array $headers = [],
    ): CoffeeMailResponse {
        $cleanPath = '/' . ltrim($path, '/');
        $url = self::BASE_URL . $cleanPath;

        if ($query !== []) {
            $queryString = http_build_query($query);
            if ($queryString !== '') {
                $url .= (str_contains($url, '?') ? '&' : '?') . $queryString;
            }
        }

        $formattedHeaders = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
            'Accept-Language: ' . $this->locale,
            'User-Agent: coffeemail-php/' . self::SDK_VERSION,
        ];

        foreach ($headers as $key => $value) {
            $formattedHeaders[] = $key . ': ' . $value;
        }

        $ch = curl_init();
        if ($ch === false) {
            return new CoffeeMailResponse(
                data: null,
                error: new NetworkException(I18n::getMessage('curl_init_failed', $this->locale)),
                statusCode: 0,
            );
        }

        $upper = strtoupper(trim($method));
        $cleanMethod = $upper !== '' ? $upper : 'GET';

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $cleanMethod,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $formattedHeaders,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeoutSeconds),
            CURLOPT_HEADER => true,
        ];

        if ($body !== null) {
            $jsonPayload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($jsonPayload !== false) {
                $options[CURLOPT_POSTFIELDS] = $jsonPayload;
            }
        }

        curl_setopt_array($ch, $options);

        /** @var string|false $rawResponse */
        $rawResponse = curl_exec($ch);

        if ($rawResponse === false) {
            $errorMsg = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            $msg = ($errno === CURLE_OPERATION_TIMEDOUT)
                ? I18n::getMessage('timeout_error', $this->locale, ['timeoutSeconds' => $this->timeoutSeconds])
                : I18n::getMessage('network_error', $this->locale, ['details' => $errorMsg]);

            return new CoffeeMailResponse(
                data: null,
                error: new NetworkException($msg),
                statusCode: 0,
            );
        }

        /** @var int $statusCode */
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        /** @var int $headerSize */
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaderString = substr($rawResponse, 0, $headerSize);
        $rawBodyString = substr($rawResponse, $headerSize);

        $parsedHeaders = $this->parseHeaders($rawHeaderString);

        if ($statusCode === 204 || trim($rawBodyString) === '') {
            return new CoffeeMailResponse(data: null, error: null, statusCode: $statusCode);
        }

        /** @var mixed $parsedJson */
        $parsedJson = json_decode($rawBodyString, true);
        if ($parsedJson === null && json_last_error() !== JSON_ERROR_NONE) {
            $parsedJson = ['message' => $rawBodyString];
        }

        if ($statusCode >= 400) {
            $error = $this->createErrorFromResponse($statusCode, $parsedJson, $parsedHeaders);

            return new CoffeeMailResponse(data: null, error: $error, statusCode: $statusCode);
        }

        return new CoffeeMailResponse(data: $parsedJson, error: null, statusCode: $statusCode);
    }

    public function get(string $path, array $query = [], array $headers = []): CoffeeMailResponse
    {
        return $this->request('GET', $path, null, $query, $headers);
    }

    public function post(string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
    {
        return $this->request('POST', $path, $body, $query, $headers);
    }

    public function put(string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
    {
        return $this->request('PUT', $path, $body, $query, $headers);
    }

    public function patch(string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
    {
        return $this->request('PATCH', $path, $body, $query, $headers);
    }

    public function delete(string $path, array $query = [], array $headers = []): CoffeeMailResponse
    {
        return $this->request('DELETE', $path, null, $query, $headers);
    }

    /**
     * @param array<string, string> $headers
     */
    private function createErrorFromResponse(int $status, mixed $payload, array $headers): CoffeeMailException
    {
        $message = I18n::getMessage('unexpected_error', $this->locale);
        $details = null;

        if (is_array($payload)) {
            if (isset($payload['error']) && is_array($payload['error'])) {
                /** @var array<string, mixed> $errObj */
                $errObj = $payload['error'];
                if (isset($errObj['message']) && is_string($errObj['message'])) {
                    $message = $errObj['message'];
                }
                $details = $errObj['details'] ?? null;
            }
            if (!isset($payload['error']) && isset($payload['message']) && is_string($payload['message'])) {
                $message = $payload['message'];
                $details = $payload['details'] ?? null;
            }
        }

        return match ($status) {
            400 => new ValidationError($message, $details),
            401 => new AuthenticationError($message, $details),
            402 => new PaymentRequiredError($message, $details),
            403 => new ForbiddenError($message, $details),
            404 => new NotFoundError($message, $details),
            409 => new ConflictError($message, $details),
            429 => new RateLimitError(
                message: $message,
                retryAfterSeconds: isset($headers['retry-after']) ? (int) $headers['retry-after'] : null,
                details: $details,
            ),
            default => new InternalServerError($message, $details),
        };
    }

    /**
     * @return array<string, string>
     */
    private function parseHeaders(string $headerContent): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerContent);

        foreach ($lines as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($key))] = trim($value);
        }

        return $headers;
    }
}
