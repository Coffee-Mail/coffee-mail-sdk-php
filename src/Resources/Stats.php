<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

final readonly class Stats
{
    public function __construct(
        private HttpTransportInterface $http,
    ) {
    }

    /**
     * Consulta as métricas agregadas de envio e entregabilidade da organização.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function get(array $query = []): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/stats', $query);
    }
}
