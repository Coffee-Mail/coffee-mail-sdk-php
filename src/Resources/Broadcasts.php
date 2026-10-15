<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Exceptions\ForbiddenError;
use CoffeeMail\Exceptions\PermissionError;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;
use CoffeeMail\I18n\I18n;

/**
 * Campanhas. Toda operação exige chave `full_access`; sem ela, o erro é `PermissionError`.
 */
final class Broadcasts
{
    private const FULL_ACCESS = 'full_access';
    private const BASE_PATH = '/v1/product/broadcasts';

    public function __construct(
        private readonly HttpTransportInterface $http,
    ) {
    }

    /**
     * Cria uma nova campanha (broadcast) para disparo em massa.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function create(array $payload): CoffeeMailResponse
    {
        return $this->guard('broadcasts.create', $this->http->post(self::BASE_PATH, $payload));
    }

    /**
     * Lista campanhas criadas na organização.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function list(array $query = []): CoffeeMailResponse
    {
        return $this->guard('broadcasts.list', $this->http->get(self::BASE_PATH, $query));
    }

    /**
     * Obtém os detalhes de uma campanha pelo ID.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function get(string $id): CoffeeMailResponse
    {
        return $this->guard('broadcasts.get', $this->http->get(self::BASE_PATH . '/' . rawurlencode($id)));
    }

    /**
     * Dispara o envio imediato da campanha para a audiência vinculada.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function send(string $id): CoffeeMailResponse
    {
        return $this->guard('broadcasts.send', $this->http->post(self::BASE_PATH . '/' . rawurlencode($id) . '/send'));
    }

    /**
     * Cancela o envio de uma campanha em andamento ou agendada.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function cancel(string $id): CoffeeMailResponse
    {
        return $this->guard(
            'broadcasts.cancel',
            $this->http->post(self::BASE_PATH . '/' . rawurlencode($id) . '/cancel'),
        );
    }

    /**
     * Dispara um envio de teste da campanha.
     *
     * @param array<string, mixed> $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function testSend(string $id, array $payload): CoffeeMailResponse
    {
        return $this->guard(
            'broadcasts.testSend',
            $this->http->post(self::BASE_PATH . '/' . rawurlencode($id) . '/test-send', $payload),
        );
    }

    /**
     * @param CoffeeMailResponse<mixed> $response
     * @return CoffeeMailResponse<mixed>
     */
    private function guard(string $operation, CoffeeMailResponse $response): CoffeeMailResponse
    {
        $error = $response->error;
        if (!$error instanceof ForbiddenError) {
            return $response;
        }

        $message = I18n::getMessage('permission_denied', $this->http->getLocale(), [
            'requiredPermission' => self::FULL_ACCESS . ' (called by ' . $operation . ')',
        ]);

        return new CoffeeMailResponse(
            data: null,
            error: new PermissionError(
                message: $message,
                requiredPermission: self::FULL_ACCESS,
                details: array_merge($error->details, ['operation' => $operation]),
                previous: $error,
                apiCode: $error->apiCode,
                requestId: $error->requestId,
            ),
            statusCode: $response->statusCode,
        );
    }
}
