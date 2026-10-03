<?php

declare(strict_types=1);

use CoffeeMail\CoffeeMail;
use CoffeeMail\Exceptions\AuthenticationError;
use CoffeeMail\Exceptions\NotFoundError;
use CoffeeMail\Exceptions\RateLimitError;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

test('client initialization throws AuthenticationError when no api key is provided', function (): void {
    putenv('COFFEEMAIL_API_KEY'); // limpa env
    unset($_ENV['COFFEEMAIL_API_KEY'], $_SERVER['COFFEEMAIL_API_KEY']);

    expect(fn () => new CoffeeMail(''))
        ->toThrow(AuthenticationError::class, 'Chave de API não informada');
});

test('client initialization loads api key from environment variable when omitted', function (): void {
    putenv('COFFEEMAIL_API_KEY=cm_live_env_test_key_123');

    $client = new CoffeeMail();
    expect($client->getApiKey())->toBe('cm_live_env_test_key_123');

    putenv('COFFEEMAIL_API_KEY'); // limpa
});

test('client maps 404 response to NotFoundError in envelope', function (): void {
    $mockTransport = new class implements HttpTransportInterface {
        public function request(string $method, string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
        {
            return new CoffeeMailResponse(
                data: null,
                error: new NotFoundError('E-mail não encontrado'),
                statusCode: 404,
            );
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

        public function getLocale(): string
        {
            return 'pt-BR';
        }
    };

    $client = new CoffeeMail('cm_live_dummy', transport: $mockTransport);
    $response = $client->getTransport()->get('/v1/product/emails/eml_nao_existe');

    expect($response->isSuccess())->toBeFalse()
        ->and($response->isError())->toBeTrue()
        ->and($response->error)->toBeInstanceOf(NotFoundError::class)
        ->and($response->statusCode)->toBe(404);
});

test('client introspection caches successful response and can be invalidated', function (): void {
    $callCount = 0;

    $mockTransport = new class($callCount) implements HttpTransportInterface {
        public function __construct(public int &$calls) {}

        public function request(string $method, string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
        {
            $this->calls++;
            return new CoffeeMailResponse(
                data: ['id' => 'key_123', 'name' => 'Producao', 'scopes' => ['emails:send']],
                error: null,
                statusCode: 200,
            );
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

        public function getLocale(): string
        {
            return 'pt-BR';
        }
    };

    $client = new CoffeeMail('cm_live_dummy', transport: $mockTransport);

    // 1ª chamada: vai no transporte
    $res1 = $client->introspect();
    expect($res1->isSuccess())->toBeTrue()
        ->and($callCount)->toBe(1);

    // 2ª chamada: usa o cache em memória (não incrementa chamadas)
    $res2 = $client->introspect();
    expect($res2->isSuccess())->toBeTrue()
        ->and($callCount)->toBe(1);

    // Invalidação manual do cache
    $client->invalidateApiKeyCache();

    // 3ª chamada: busca novamente
    $res3 = $client->introspect();
    expect($res3->isSuccess())->toBeTrue()
        ->and($callCount)->toBe(2);
});

test('client respects custom locale and reflects it in getLocale', function (): void {
    $clientDefault = new CoffeeMail('cm_live_test');
    expect($clientDefault->getLocale())->toBe('pt-BR');

    $clientEn = new CoffeeMail('cm_live_test', locale: 'en');
    expect($clientEn->getLocale())->toBe('en');
});

test('client formats error messages according to selected locale', function (): void {
    putenv('COFFEEMAIL_API_KEY');
    unset($_ENV['COFFEEMAIL_API_KEY'], $_SERVER['COFFEEMAIL_API_KEY']);

    expect(fn () => new CoffeeMail('', locale: 'pt-BR'))
        ->toThrow(AuthenticationError::class, 'Chave de API não informada');

    expect(fn () => new CoffeeMail('', locale: 'en'))
        ->toThrow(AuthenticationError::class, 'API key was not provided');
});

test('I18n utility resolves keys, handles fallbacks and parameter replacements', function (): void {
    expect(\CoffeeMail\I18n\I18n::getMessage('missing_api_key', 'pt-BR'))
        ->toContain('Chave de API não informada')
        ->and(\CoffeeMail\I18n\I18n::getMessage('missing_api_key', 'en'))
        ->toContain('API key was not provided')
        ->and(\CoffeeMail\I18n\I18n::getMessage('timeout_error', 'en', ['timeoutSeconds' => 15]))
        ->toBe('The request timed out after 15s.')
        ->and(\CoffeeMail\I18n\I18n::getMessage('chave_inexistente', 'pt-BR'))
        ->toBe('chave_inexistente');
});
