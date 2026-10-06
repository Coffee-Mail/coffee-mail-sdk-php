<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

final readonly class Broadcasts
{
    public function __construct(
        private HttpTransportInterface $http,
    ) {}

    /**
     * Cria uma nova campanha (broadcast) para disparo em massa.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function create(array $payload): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/broadcasts', $payload);
    }

    /**
     * Lista campanhas criadas na organização.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function list(array $query = []): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/broadcasts', $query);
    }

    /**
     * Obtém os detalhes de uma campanha pelo ID.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function get(string $id): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/broadcasts/' . rawurlencode($id));
    }

    /**
     * Dispara o envio imediato da campanha para a audiência vinculada.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function send(string $id): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/broadcasts/' . rawurlencode($id) . '/send');
    }

    /**
     * Cancela o envio de uma campanha em andamento ou agendada.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function cancel(string $id): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/broadcasts/' . rawurlencode($id) . '/cancel');
    }

    /**
     * Exclui uma campanha.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function delete(string $id): CoffeeMailResponse
    {
        return $this->http->delete('/v1/product/broadcasts/' . rawurlencode($id));
    }
}
