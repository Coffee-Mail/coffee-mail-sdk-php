<?php

declare(strict_types=1);

namespace CoffeeMail\Http;

use CoffeeMail\Exceptions\NetworkException;
use CoffeeMail\I18n\I18n;
use CoffeeMail\Version;

final class CurlTransport implements HttpTransportInterface
{
    private const BASE_URL = 'https://api.coffeemail.com.br';

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
            'Accept: application/json',
            'Accept-Language: ' . $this->locale,
            'User-Agent: coffeemail-php/' . Version::VALUE,
        ];

        if ($body !== null) {
            $formattedHeaders[] = 'Content-Type: application/json';
        }

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

        return ResponseParser::parse($statusCode, $rawBodyString, $this->parseHeaders($rawHeaderString), $this->locale);
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
