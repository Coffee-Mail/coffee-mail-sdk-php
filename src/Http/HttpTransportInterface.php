<?php

declare(strict_types=1);

namespace CoffeeMail\Http;

interface HttpTransportInterface
{
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
    ): CoffeeMailResponse;

    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @return CoffeeMailResponse<mixed>
     */
    public function get(string $path, array $query = [], array $headers = []): CoffeeMailResponse;

    /**
     * @param array<string, mixed>|list<mixed>|null $body
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @return CoffeeMailResponse<mixed>
     */
    public function post(string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse;

    /**
     * @param array<string, mixed>|list<mixed>|null $body
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @return CoffeeMailResponse<mixed>
     */
    public function put(string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse;

    /**
     * @param array<string, mixed>|list<mixed>|null $body
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @return CoffeeMailResponse<mixed>
     */
    public function patch(
        string $path,
        ?array $body = null,
        array $query = [],
        array $headers = [],
    ): CoffeeMailResponse;

    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @return CoffeeMailResponse<mixed>
     */
    public function delete(string $path, array $query = [], array $headers = []): CoffeeMailResponse;

    public function getLocale(): string;
}
