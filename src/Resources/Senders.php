<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

/**
 * Remetentes verificados por endereço, alternativa ao domínio completo.
 */
final class Senders
{
    public function __construct(private readonly HttpTransportInterface $http)
    {
    }

    /**
     * Cadastra um remetente e dispara o e-mail de verificação.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function create(string $email, string $displayName): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/senders', [
            'email' => $email,
            'displayName' => $displayName,
        ]);
    }

    /**
     * Lista os remetentes da organização.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function list(): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/senders');
    }

    /**
     * Confirma a verificação de um remetente com o token recebido por e-mail.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function verify(string $token): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/senders/verify', ['token' => $token]);
    }

    /**
     * Remove um remetente verificado.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function delete(string $id): CoffeeMailResponse
    {
        return $this->http->delete('/v1/product/senders/' . rawurlencode($id));
    }
}
