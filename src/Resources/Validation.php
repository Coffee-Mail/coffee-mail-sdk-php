<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

final class Validation
{
    public function __construct(
        private readonly HttpTransportInterface $http,
    ) {
    }

    /**
     * Valida de 1 a 100 endereços (formato, MX e domínio descartável), consumindo créditos do mês.
     *
     * A resposta traz `items` (`email`, `formatValid`, `mxValid`, `isDisposable`, `reason`),
     * `validCount`, `invalidCount` e `credits` (`limit`, `used`, `remaining`). Com os créditos
     * esgotados, o erro é `RateLimitError` com `apiCode` `VALIDATION_CREDITS_EXHAUSTED`: não
     * adianta tentar de novo antes da virada do mês.
     *
     * @param list<string> $emails
     * @return CoffeeMailResponse<mixed>
     */
    public function validate(array $emails): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/validate', ['emails' => $emails]);
    }
}
