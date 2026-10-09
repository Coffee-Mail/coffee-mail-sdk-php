<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

final class Suppressions
{
    public function __construct(
        private readonly HttpTransportInterface $http,
    ) {
    }

    /**
     * Lista a relação de e-mails suprimidos (unsubscribes, hard bounces, reclamações).
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function list(array $query = []): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/suppressions', $query);
    }

    /**
     * Consulta se um endereço de e-mail específico está na lista de supressão.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function get(string $email): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/suppressions/' . rawurlencode($email));
    }

    /**
     * Insere manualmente um e-mail na lista de supressão.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function create(array $payload): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/suppressions', $payload);
    }

    /**
     * Remove um endereço de e-mail da lista de supressão.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function delete(string $email): CoffeeMailResponse
    {
        return $this->http->delete('/v1/product/suppressions/' . rawurlencode($email));
    }

    /**
     * Reativa um endereço previamente suprimido.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function reactivate(string $id): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/suppressions/' . rawurlencode($id) . '/reactivate');
    }

    /**
     * Cria supressões em lote.
     *
     * @param array<int, array<string, mixed>> $suppressions
     * @return CoffeeMailResponse<mixed>
     */
    public function bulkCreate(array $suppressions): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/suppressions/bulk', ['suppressions' => $suppressions]);
    }
}
