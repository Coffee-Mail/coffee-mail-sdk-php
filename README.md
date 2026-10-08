# CoffeeMail PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/coffeemail/coffeemail-php.svg?style=flat-square&color=c27803)](https://packagist.org/packages/coffeemail/coffeemail-php)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.2-777BB4.svg?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-brightgreen.svg?style=flat-square)](https://phpstan.org)
[![Zero Dependencies](https://img.shields.io/badge/Dependencies-Zero%20(ext--curl)-blue.svg?style=flat-square)](#requisitos)
[![Software License](https://img.shields.io/badge/license-MIT-green.svg?style=flat-square)](LICENSE)

O **SDK oficial do CoffeeMail para PHP** fornece uma camada fortemente tipada, moderna (PHP 8.2+) e de alta performance para envio de e-mails transacionais e gerenciamento completo da plataforma CoffeeMail.

Projetado sob princípios de **Clean Code**, **SOLID** e **Zero Dependencies**, o pacote utiliza exclusivamente as extensões nativas `ext-curl` e `ext-json`, evitando conflitos de dependências em projetos Laravel, Symfony, WordPress ou aplicações legadas.

---

## ⚡ Diferenciais do SDK

* **Retorno Seguro em Envelope `{ data, error }`**: Sem disparos acidentais de exceções para status HTTP 4xx/5xx — você escolhe entre desestruturação funcional em tuplas ou lançamento defensivo via `->unwrap()`.
* **Zero Dependências de Produção**: Sem Guzzle ou PSR-7/18 arrastando dezenas de pacotes secundários.
* **Segurança Criptográfica**: Verificação de assinaturas HMAC SHA-256 de webhooks com comparação em tempo constante (`hash_equals`).
* **Qualidade Rigorosa**: Tipagem estrita com `declare(strict_types=1)` em todos os arquivos e conformidade máxima com o **PHPStan (Nível 9)**.
* **DTOs & Payloads Opcionais**: Suporte nativo a classes de payload tipadas (`EmailPayload`, `AttachmentPayload`) ou arrays associativos tradicionais.
* **Localização Nativa (`i18n`)**: Tratamento de timeouts, erros de conexão e header `Accept-Language` configuráveis em `pt-BR` ou `en`.

---

## 📦 Requisitos

* **PHP 8.2** ou superior.
* Extensões PHP habilitadas: `ext-curl` e `ext-json`.

---

## 🚀 Instalação

Instale o pacote oficial via Composer:

```bash
composer require coffeemail/coffeemail-php
```

---

## 🔑 Inicialização do Cliente

O cliente resolve sua API Key automaticamente a partir da variável de ambiente `COFFEEMAIL_API_KEY`:

```php
use CoffeeMail\CoffeeMail;

// 1. Resolução automática via variável de ambiente (COFFEEMAIL_API_KEY):
$client = new CoffeeMail();

// 2. Ou informando a chave explicitamente no construtor:
$client = new CoffeeMail(
    apiKey: 'cm_live_sua_chave_aqui',
    locale: 'pt-BR',           // 'pt-BR' (padrão) ou 'en'
    timeoutSeconds: 10          // Tempo limite de conexão
);
```

---

## 🎯 Padrões de Retorno: Tuplas vs. Exceções

Todas as chamadas à API retornam uma instância imutável de `CoffeeMailResponse<T>`.

### Estilo 1: Desestruturação Nativa de Tupla (Recomendado)

Inspirado em padrões modernos funcionais, permite controle de fluxo limpo sem blocos `try/catch` aninhados:

```php
[$data, $error] = $client->emails->send([
    'from' => 'contato@seudominio.com.br',
    'to' => 'cliente@gmail.com',
    'subject' => 'Seu pedido foi aprovado!',
    'html' => '<h1>Pedido #1234</h1><p>Obrigado pela compra.</p>',
]);

if ($error !== null) {
    echo "Falha ao enviar [{$error->code}]: {$error->message}\n";
    exit(1);
}

echo "E-mail enviado! ID: {$data['id']}\n";
```

### Estilo 2: Método `->unwrap()` (Estilo Imperativo / Try-Catch)

Para desenvolvedores ou arquiteturas que preferem capturar exceções tipadas:

```php
use CoffeeMail\Exceptions\RateLimitError;
use CoffeeMail\Exceptions\ValidationError;
use CoffeeMail\Exceptions\CoffeeMailException;

try {
    $email = $client->emails->send([
        'from' => 'contato@seudominio.com.br',
        'to' => 'cliente@gmail.com',
        'subject' => 'Aviso de Cobrança',
        'html' => '<p>Fatura disponível.</p>',
    ])->unwrap();

    echo "Sucesso: {$email['id']}\n";
} catch (RateLimitError $e) {
    echo "Limite de requisições excedido. Aguarde {$e->retryAfterSeconds}s.\n";
} catch (ValidationError $e) {
    echo "Dados inválidos: {$e->getMessage()}\n";
} catch (CoffeeMailException $e) {
    echo "Erro da API CoffeeMail: {$e->getMessage()}\n";
}
```

---

## 📨 Envio de E-mails (`$client->emails`)

### Envio com DTOs Tipados (`EmailPayload`)

O uso de DTOs garante autocompletion completo na IDE e evita erros de digitação:

```php
use CoffeeMail\Payloads\EmailPayload;
use CoffeeMail\Payloads\AttachmentPayload;

$payload = new EmailPayload(
    from: 'contato@seudominio.com.br',
    to: 'cliente@gmail.com',
    subject: 'Boleto de Renovação',
    html: '<p>Segue seu boleto em anexo.</p>',
    attachments: [
        AttachmentPayload::fromPath('/var/docs/boleto_123.pdf', 'boleto.pdf', 'application/pdf'),
    ],
    idempotencyKey: 'pedido_renovacao_123', // Garante disparo único
    isSandbox: false,
);

[$data, $error] = $client->emails->send($payload);
```

### Anexos Descomplicados (`AttachmentPayload`)

Você pode anexar arquivos físicos diretamente do disco ou dados brutos em memória:

```php
use CoffeeMail\Payloads\AttachmentPayload;

// 1. Arquivo do sistema de arquivos (converte para Base64 e detecta MIME automaticamente):
$anexo1 = AttachmentPayload::fromPath('/storage/fatura.pdf');

// 2. Bytes brutos em memória (ex: PDF gerado via Dompdf / TCPDF / Snappy):
$anexo2 = AttachmentPayload::fromRaw('relatorio.pdf', $pdfBytes, 'application/pdf');

// 3. String Base64 pré-codificada:
$anexo3 = AttachmentPayload::fromBase64('comprovante.png', $base64String, 'image/png');
```

### Envio em Lote (`sendBatch`)

Envie múltiplos e-mails transacionais otimizados em uma única requisição HTTP:

```php
use CoffeeMail\Payloads\EmailPayload;

$lote = [
    new EmailPayload(
        from: 'financeiro@empresa.com.br',
        to: 'cliente1@gmail.com',
        subject: 'Atualização Cadastral',
        html: '<p>Olá João!</p>',
    ),
    new EmailPayload(
        from: 'financeiro@empresa.com.br',
        to: 'cliente2@gmail.com',
        subject: 'Atualização Cadastral',
        html: '<p>Olá Maria!</p>',
    ),
];

[$data, $error] = $client->emails->sendBatch($lote);
```

### Consulta e Rastreamento

```php
// Consultar status de entrega de um e-mail
[$email, $error] = $client->emails->get('eml_8f92b7c4');

// Linha do tempo de eventos (enfileirado -> enviado -> entregue -> aberto -> clicado)
[$eventos, $error] = $client->emails->getEvents('eml_8f92b7c4');

// Listar envios recentes filtrados por status
[$lista, $error] = $client->emails->list([
    'status' => 'delivered',
    'limit' => 50,
]);

// Cancelar envio agendado antes do disparo
[$cancelado, $error] = $client->emails->cancel('eml_8f92b7c4');
```

---

## 🔒 Webhooks e Validação Criptográfica HMAC

O CoffeeMail assina todas as requisições de webhook enviando um hash HMAC SHA-256 no header `X-CoffeeMail-Signature`. O método `Webhooks::verifySignature` executa a validação em tempo constante contra *timing attacks*:

### Exemplo em Laravel

No seu Controller (`app/Http/Controllers/CoffeeMailWebhookController.php`):

```php
namespace App\Http\Controllers;

use CoffeeMail\Resources\Webhooks;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CoffeeMailWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('x-coffeemail-signature', '');
        $secret = config('services.coffeemail.webhook_secret');

        if (! Webhooks::verifySignature($payload, $signature, $secret)) {
            return response('Assinatura inválida', 401);
        }

        $event = $request->json()->all();

        match ($event['event'] ?? '') {
            'email.delivered' => Log::info("E-mail entregue: {$event['data']['id']}"),
            'email.bounced'   => Log::warning("Bounce detectado: {$event['data']['to'][0]['email']}"),
            default           => null,
        };

        return response('OK', 200);
    }
}
```

> **Atenção**: No Laravel 11+, certifique-se de excluir a rota do webhook da verificação do middleware CSRF em `bootstrap/app.php`:
> ```php
> ->withMiddleware(function (Middleware $middleware) {
>     $middleware->validateCsrfTokens(except: ['webhooks/coffeemail']);
> })
> ```

### Exemplo em Vanilla PHP / PSR-7

```php
use CoffeeMail\Resources\Webhooks;

$payload = file_get_contents('php://input') ?: '';
$signature = $_SERVER['HTTP_X_COFFEEMAIL_SIGNATURE'] ?? '';
$secret = getenv('COFFEEMAIL_WEBHOOK_SECRET') ?: '';

if (! Webhooks::verifySignature($payload, $signature, $secret)) {
    http_response_code(401);
    echo "Assinatura do webhook inválida.";
    exit;
}

$evento = json_decode($payload, true);
// Processar evento com segurança...
http_response_code(200);
```

---

## 🛠️ Integração com Laravel

### 1. Configuração de Credenciais

Adicione no seu arquivo `config/services.php`:

```php
'coffeemail' => [
    'key' => env('COFFEEMAIL_API_KEY'),
    'webhook_secret' => env('COFFEEMAIL_WEBHOOK_SECRET'),
],
```

E no seu `.env`:

```dotenv
COFFEEMAIL_API_KEY=cm_live_sua_chave_aqui
COFFEEMAIL_WEBHOOK_SECRET=whsec_seu_segredo_aqui
```

### 2. Registro no Service Container

No `app/Providers/AppServiceProvider.php`:

```php
namespace App\Providers;

use CoffeeMail\CoffeeMail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CoffeeMail::class, function () {
            return new CoffeeMail(
                apiKey: config('services.coffeemail.key'),
                locale: app()->getLocale() === 'en' ? 'en' : 'pt-BR',
            );
        });
    }
}
```

### 3. Injeção de Dependência em Jobs ou Controllers

```php
namespace App\Jobs;

use CoffeeMail\CoffeeMail;
use CoffeeMail\Payloads\EmailPayload;
use Illuminate\Contracts\Queue\ShouldQueue;

class EnviarConfirmacaoJob implements ShouldQueue
{
    public function handle(CoffeeMail $coffeeMail): void
    {
        [$data, $error] = $coffeeMail->emails->send(new EmailPayload(
            from: 'noreply@empresa.com.br',
            to: 'cliente@exemplo.com',
            subject: 'Conta criada!',
            html: '<p>Seja bem-vindo!</p>',
        ));

        if ($error) {
            throw $error; // Aciona política de retry do Queue Worker do Laravel
        }
    }
}
```

---

## 🌐 Recursos Complementares

| Recurso | Exemplo de Operação | Descrição |
| --- | --- | --- |
| **Domínios** | `$client->domains->create(['name' => 'dominio.com.br'])` | Registra domínio e retorna registros DNS (SPF, DKIM, DMARC). |
| **Verificação** | `$client->domains->verify('dom_123')` | Dispara checagem ativa de propagação DNS. |
| **Templates** | `$client->templates->preview('tpl_123', ['nome' => 'Ana'])` | Renderiza prévia HTML com variáveis simuladas. |
| **Audiências** | `$client->audiences->addContact('aud_123', ['email' => 'joao@empresa.com'])` | Insere ou atualiza contato em uma lista de audiência. |
| **Campanhas** | `$client->broadcasts->send('bc_123')` | Dispara envio de broadcast em massa para todos os inscritos. |
| **Supressões** | `$client->suppressions->list(['reason' => 'bounce'])` | Consulta e gerencia e-mails suprimidos para proteger sua reputação. |
| **Métricas** | `$client->stats->get(['from' => '2026-01-01', 'to' => '2026-01-31'])` | Consulta taxas de entrega, abertura, cliques e rejeições. |

---

## ⚠️ Tratamento de Erros e Exceções Tipadas

Todas as exceções herdam de `CoffeeMail\Exceptions\CoffeeMailException`:

| Status HTTP | Classe de Exceção | Quando Ocorre |
| :---: | :--- | :--- |
| **400** | `ValidationError` | Parâmetros obrigatórios ausentes ou payload em formato inválido. |
| **401** | `AuthenticationError` | Chave de API inválida, revogada ou ausente. |
| **402** | `PaymentRequiredError` | Fatura em atraso ou cota de envios do plano esgotada. |
| **403** | `ForbiddenError` | Escopo de permissão insuficiente para a operação. |
| **404** | `NotFoundError` | Recurso solicitado não encontrado na organização. |
| **409** | `ConflictError` | Conflito de estado ou registro duplicado (ex: domínio já existente). |
| **429** | `RateLimitError` | Limite de requisições por minuto atingido (exibe `$e->retryAfterSeconds`). |
| **500** | `InternalServerError` | Falha temporária no processamento da API do CoffeeMail. |
| **0** | `NetworkException` | Falha de resolução DNS, conexão recusada ou timeout cURL. |

---

## 🧪 Testes e Qualidade

O SDK conta com suíte de testes automatizados com **Pest PHP** e auditoria estática com **PHPStan Nível 9**:

```bash
# Executar a suíte de testes unitários (Pest PHP)
composer test

# Executar a análise estática em nível estrito (Nível 9)
composer analyse

# Checagem completa de padrões
composer check
```

---

## 📄 Licença

Distribuído sob a licença **MIT**. Consulte o arquivo [LICENSE](LICENSE) para mais detalhes.
