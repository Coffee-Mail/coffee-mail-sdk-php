<div align="center">

# ☕ CoffeeMail PHP SDK

**SDK oficial do CoffeeMail para PHP 8.2+: envio transacional e gestão da plataforma com retorno em envelope, sem dependências de produção além de `ext-curl` e `ext-json`.**

[![Packagist](https://img.shields.io/packagist/v/coffeemail/coffeemail-php.svg?style=flat-square)](https://packagist.org/packages/coffeemail/coffeemail-php)
[![PHP](https://img.shields.io/badge/PHP-%5E8.2-777BB4.svg?style=flat-square&logo=php&logoColor=white)](composer.json)
[![CI](https://github.com/Coffee-Mail/coffee-mail-sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/Coffee-Mail/coffee-mail-sdk-php/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-n%C3%ADvel%209-brightgreen.svg?style=flat-square)](phpstan.neon)
[![Licença](https://img.shields.io/badge/licen%C3%A7a-MIT-green.svg?style=flat-square)](LICENSE)

[Instalação](#-instalação) · [Início rápido](#-início-rápido) · [Erros](#️-tratamento-de-erros) · [Referência](#-referência) · [Webhooks](#-webhooks) · [Versionamento](#-versionamento-e-releases)

</div>

---

## 📑 Sumário

- [✨ Visão geral](#-visão-geral)
- [🚦 Status do projeto](#-status-do-projeto)
- [📋 Requisitos](#-requisitos)
- [📦 Instalação](#-instalação)
- [🚀 Início rápido](#-início-rápido)
- [⚙️ Configuração](#️-configuração)
- [🛡️ Tratamento de erros](#️-tratamento-de-erros)
- [📚 Referência](#-referência)
- [🔔 Webhooks](#-webhooks)
- [🔖 Versionamento e releases](#-versionamento-e-releases)
- [🧪 Testes e qualidade](#-testes-e-qualidade)
- [🗂️ Estrutura do repositório](#️-estrutura-do-repositório)
- [🤝 Contribuição](#-contribuição)
- [🔗 Projetos relacionados](#-projetos-relacionados)
- [📄 Licença](#-licença)

## ✨ Visão geral

- **Envelope em vez de exceção.** Toda chamada à API devolve um `CoffeeMailResponse` imutável. Erros HTTP e de rede viram uma exceção tipada **dentro** do envelope; você escolhe entre desestruturar `[$data, $error]` ou chamar `->unwrap()` para lançar.
- **Sem dependências de produção.** Usa só `ext-curl` e `ext-json` (sem Guzzle, sem PSR-7/18), o que evita conflito de versões em Laravel, Symfony, WordPress ou código legado.
- **Payloads tipados opcionais.** `EmailPayload`, `AttachmentPayload`, `DomainPayload` e `WebhookPayload`, ou arrays associativos.
- **Normalização de participantes.** `from`, `to`, `cc`, `bcc` e `replyTo` aceitam `'email@x.com'`, `'Nome <email@x.com>'` ou `['email' => ..., 'name' => ...]`, convertidos para o formato `{ email, name? }` exigido pela API.
- **Mensagens localizadas** (`pt-BR` e `en`) para erros gerados pelo próprio SDK, e header `Accept-Language` enviado à API.
- **Transporte substituível** via `HttpTransportInterface`, útil para testes e para customizar o cliente HTTP.
- `declare(strict_types=1)` em todos os arquivos e PHPStan nível 9 sobre `src` e `tests`.

## 🚦 Status do projeto

Pré-1.0 (`0.2.x` em `main`, ainda não publicada). Leia antes de usar em produção:

- **`main` está à frente da versão publicada.** O Packagist tem só a `v0.1.0` (publicada em 2026-10-08, commit `1c7e38a`). Tudo o que está marcado como _não publicado_ na [Referência](#-referência) só existe em `main` (`0.1.x-dev`). A `v0.1.0` também foi registrada no Packagist como licença `proprietary`; a adoção da MIT veio depois e entra no próximo release.
- **A `0.2.0` quebra a API da `0.1.0`** (ainda não havia usuários ativos): `Webhooks::verifySignature()` passou a usar o algoritmo da API, `broadcasts->delete()`, `audiences->addContact()` e `removeContact()` saíram, e os erros mudaram de forma. Veja o [CHANGELOG](CHANGELOG.md).
- **Sem retry automático.** `RateLimitError` expõe `retryAfterSeconds`, mas a nova tentativa é por sua conta.
- **URL base fixa** em `https://api.coffeemail.com.br`. Não há opção para apontar a outro host; para isso, injete um `HttpTransportInterface` próprio.

## 📋 Requisitos

| Item | Versão |
| --- | --- |
| PHP | `^8.2` (CI roda 8.2, 8.3 e 8.4) |
| Extensões | `ext-curl`, `ext-json` |
| Composer | 2.x |

## 📦 Instalação

```bash
composer require coffeemail/coffeemail-php
```

Para usar o que já está em `main` e ainda não foi publicado:

```bash
composer require coffeemail/coffeemail-php:0.1.x-dev
```

## 🚀 Início rápido

```php
use CoffeeMail\CoffeeMail;

$client = new CoffeeMail();

[$data, $error] = $client->emails->send([
    'from' => 'Loja Exemplo <contato@seudominio.com.br>',
    'to' => 'cliente@exemplo.com',
    'subject' => 'Seu pedido foi aprovado',
    'html' => '<h1>Pedido #1234</h1><p>Obrigado pela compra.</p>',
]);

if ($error !== null) {
    echo "Falha [{$error->code}]: {$error->getMessage()}\n";
    exit(1);
}

echo "Enfileirado: {$data['id']} ({$data['status']})\n";
```

Sem argumentos, o cliente lê a chave de `COFFEEMAIL_API_KEY`. A API responde `202` com `id`, `status` (`queued` ou `scheduled`) e `queuedAt`: o envio é assíncrono, e o resultado final chega por [webhook](#-webhooks) ou por `emails->get()` / `emails->getEvents()`.

## ⚙️ Configuração

```php
use CoffeeMail\CoffeeMail;

$client = new CoffeeMail(
    apiKey: 'cm_live_...',
    locale: 'en',
    timeoutSeconds: 15,
);
```

| Parâmetro | Tipo | Default | Env var | Descrição |
| --- | --- | --- | --- | --- |
| `apiKey` | `?string` | `null` | `COFFEEMAIL_API_KEY` | Chave de API. Se `null` ou vazia, usa a variável de ambiente. Sem nenhuma das duas, o construtor **lança** `AuthenticationError`. |
| `locale` | `string` | `'pt-BR'` | — | `pt-BR` ou `en`. Define as mensagens geradas pelo SDK e o header `Accept-Language`. Valores fora da lista caem em `pt-BR` nas mensagens locais. |
| `timeoutSeconds` | `int` | `10` | — | Timeout total da requisição (`CURLOPT_TIMEOUT`). O timeout de conexão é `min(5, timeoutSeconds)`. |
| `transport` | `?HttpTransportInterface` | `null` | — | Transporte HTTP customizado. Quando informado, `locale` e `timeoutSeconds` são ignorados (o transporte decide). |

Headers enviados pelo `CurlTransport` em toda requisição: `Authorization: Bearer <apiKey>`, `Content-Type: application/json`, `Accept: application/json`, `Accept-Language: <locale>`, `User-Agent: coffeemail-php/0.1.0`.

Métodos do cliente:

| Método | Retorno | Descrição |
| --- | --- | --- |
| `introspect(bool $forceRefresh = false)` | `CoffeeMailResponse` | `GET /v1/product/auth/me/api-key`. Detalhes da chave em uso, com cache local de 5 minutos (só respostas de sucesso entram no cache). |
| `invalidateApiKeyCache()` | `void` | Limpa o cache de `introspect()`. |
| `getApiKey()` | `string` | Chave resolvida. |
| `getLocale()` | `string` | Locale do transporte. |
| `getTransport()` | `HttpTransportInterface` | Transporte em uso. |

## 🛡️ Tratamento de erros

### O envelope `CoffeeMailResponse`

| Membro | Descrição |
| --- | --- |
| `$data` | Corpo JSON decodificado (array associativo) ou `null`. `null` também em `204` e corpo vazio. |
| `$error` | `?CoffeeMailException`. |
| `$statusCode` | Status HTTP real. `0` em falha de rede e em erros gerados localmente. |
| `isSuccess()` / `isError()` | Atalhos para `$error === null` / `!== null`. |
| `unwrap()` | Devolve `$data` ou lança `$error`. |
| `toTuple()` | `[$data, $error]`. O envelope também implementa `ArrayAccess`, por isso `[$data, $error] = $response` funciona direto. É imutável: escrever nos índices lança `LogicException`. |

Desestruturação:

```php
[$email, $error] = $client->emails->get($emailId);

if ($error !== null) {
    return;
}
```

Estilo com exceção:

```php
use CoffeeMail\Exceptions\CoffeeMailException;
use CoffeeMail\Exceptions\RateLimitError;
use CoffeeMail\Exceptions\ValidationError;

try {
    $email = $client->emails->send($payload)->unwrap();
} catch (RateLimitError $e) {
    $wait = $e->retryAfterSeconds ?? 1;
} catch (ValidationError $e) {
    $details = $e->details;
} catch (CoffeeMailException $e) {
    $code = $e->errorCode;
    $apiCode = $e->apiCode;
    $requestId = $e->requestId;
}
```

### Classes de erro

Todas ficam em `CoffeeMail\Exceptions` e estendem `CoffeeMailException` (que estende `RuntimeException`).

| Classe | Status HTTP | `errorCode` | Quando ocorre |
| --- | --- | --- | --- |
| `ValidationError` | 400 | `VALIDATION_ERROR` | Payload rejeitado pela API; também gerado localmente (ver abaixo). |
| `AuthenticationError` | 401 | `UNAUTHORIZED` | Chave inválida, revogada ou ausente. |
| `PaymentRequiredError` | 402 | `PAYMENT_REQUIRED` | Cota do plano esgotada ou recurso bloqueado pelo plano. |
| `ForbiddenError` | 403 | `FORBIDDEN` | Chave sem a permissão exigida pela rota. |
| `PermissionError` | 403 | `PERMISSION_DENIED` | Chave sem `full_access` em operações de campanha (`requiredPermission`). |
| `NotFoundError` | 404 | `NOT_FOUND` | Recurso inexistente na organização. |
| `ConflictError` | 409 | `CONFLICT` | Conflito de estado ou duplicidade. |
| `RateLimitError` | 429 | `RATE_LIMIT_EXCEEDED` | Limite de requisições. `retryAfterSeconds` vem do header `Retry-After` (`null` se ausente). |
| `InternalServerError` | 500, 502, 503, 504 | `INTERNAL_SERVER_ERROR` | Erro do servidor; `status` traz o status real. |
| `CoffeeMailException` | outro (ex.: 422) | código da API ou `API_ERROR` | Status sem classe própria. |
| `CoffeeMailException` | 2xx | `INVALID_RESPONSE` | Resposta de sucesso com JSON ilegível. |
| `NetworkException` | 0 | `NETWORK_ERROR` | Falha de DNS/conexão, timeout ou `curl_init` indisponível. |

Propriedades comuns:

- `errorCode`: código canônico, igual em todos os SDKs oficiais (constantes em `ErrorCodes`; também acessível como `$e->code` e `getErrorCode()`).
- `apiCode`: código original da API (ex.: um `429` pode ser `RATE_LIMIT_EXCEEDED` ou `PLAN_QUOTA_EXCEEDED`).
- `status`: status HTTP. `getCode()` devolve o mesmo inteiro.
- `details`: `array` com `meta` e `details` do corpo de erro da API (em validação, `details['issues']` lista os campos inválidos).
- `requestId`: valor do header `x-request-id`, quando a API envia.
- `getMessage()`: mensagem da API ou, na falta dela, a mensagem localizada do SDK.

### O que lança fora do envelope

| Situação | Exceção |
| --- | --- |
| `new CoffeeMail()` sem chave e sem `COFFEEMAIL_API_KEY` | `AuthenticationError` |
| `emails->send()` / `sendBatch()` sem `from` ou `to`, sem `subject` quando não há `templateId`, com `templateId` e `html`/`text` juntos (ou sem nenhum dos dois), ou com participante em formato inválido | `ValidationError` |
| `AttachmentPayload::fromPath()` com arquivo inexistente ou ilegível | `InvalidArgumentException` |

## 📚 Referência

Todos os métodos devolvem `CoffeeMailResponse`. Parâmetros `array $query` viram query string. A coluna **Desde** indica `0.1.0` (publicado) ou `main` (_não publicado_).

### Emails — `$client->emails`

| Método | Endpoint | Desde |
| --- | --- | --- |
| `send(array\|EmailPayload $payload)` | `POST /v1/product/emails` | 0.1.0 |
| `sendBatch(array $items)` | `POST /v1/product/emails/batch` | 0.1.0 |
| `get(string $id)` | `GET /v1/product/emails/{id}` | 0.1.0 |
| `list(array $query = [])` | `GET /v1/product/emails` | 0.1.0 |
| `getEvents(string $id)` | `GET /v1/product/emails/{id}/events` | 0.1.0 |
| `getTags()` | `GET /v1/product/emails/tags` | 0.1.0 |
| `cancel(string $id)` | `POST /v1/product/emails/{id}/cancel` | 0.1.0 |
| `resend(string $id)` | `POST /v1/product/emails/{id}/resend` | 0.1.0 |

Campos de `send()` (array ou `EmailPayload`):

| Campo | Tipo | Observação |
| --- | --- | --- |
| `from` | `string\|array{email, name?}` | Obrigatório. |
| `to` | `string\|list<string>\|list<array{email, name?}>` | Obrigatório. A API aceita de 1 a 50. |
| `subject` | `?string` | Obrigatório quando não há `templateId`. |
| `html`, `text` | `?string` | Até 100 KB cada. Use `html`/`text` com `subject` **ou** `templateId`, nunca os dois. |
| `cc`, `bcc` | como `to` | Até 50 cada. |
| `replyTo` | `string\|array{email, name?}` | A API aceita um único endereço. |
| `templateId`, `variables` | `string`, `array` | Envio por template salvo. |
| `metadata` | `array<string, mixed>` | Metadados livres, devolvidos nos webhooks. |
| `emailType` | `'transactional'\|'broadcast'` | Categoria do envio para rastreio e supressão. |
| `suppressionCategory` | `string` | Categoria usada na checagem da lista de supressão (até 100 caracteres). |
| `headers` | `array<string, string>` | Cabeçalhos SMTP customizados. |
| `tags` | `list<array{name, value}>` | Até 10. É o formato exigido pela API; o SDK repassa sem converter. |
| `attachments` | `list<AttachmentPayload\|array>` | Até 10. |
| `scheduledAt` | `string` (ISO 8601) | No formato array também aceita `DateTimeInterface`. |
| `idempotencyKey` | `string` | Enviado como header `x-idempotency-key`. |
| `isSandbox` | `bool` | `true` envia o header `x-coffeemail-sandbox: true`. |

```php
use CoffeeMail\Payloads\AttachmentPayload;
use CoffeeMail\Payloads\EmailPayload;

[$data, $error] = $client->emails->send(new EmailPayload(
    from: 'Financeiro <financeiro@seudominio.com.br>',
    to: ['cliente@exemplo.com'],
    subject: 'Boleto de renovação',
    html: '<p>Segue seu boleto em anexo.</p>',
    attachments: [
        AttachmentPayload::fromPath('/var/docs/boleto_123.pdf', 'boleto.pdf', 'application/pdf'),
    ],
    tags: [['name' => 'categoria', 'value' => 'cobranca']],
    idempotencyKey: 'renovacao-123',
));

[$data, $error] = $client->emails->send([
    'from' => 'contato@seudominio.com.br',
    'to' => 'cliente@exemplo.com',
    'templateId' => $templateId,
    'variables' => ['nome' => 'Ana'],
    'metadata' => ['pedido' => 123],
    'scheduledAt' => new DateTimeImmutable('+1 hour'),
]);
```

`sendBatch()` aceita uma lista de arrays ou `EmailPayload` (a API aceita de 1 a 100). Os headers de `idempotencyKey`/`isSandbox` dos itens são mesclados em uma única requisição, então só o último `idempotencyKey` vale para o lote inteiro.

Anexos (`AttachmentPayload`):

| Construtor | Descrição |
| --- | --- |
| `new AttachmentPayload(string $filename, string $content, ?string $contentType = null)` | `content` já em Base64. |
| `AttachmentPayload::fromPath(string $filePath, ?string $filename = null, ?string $contentType = null)` | Lê o arquivo e codifica em Base64. Sem `contentType`, tenta `mime_content_type()`. |
| `AttachmentPayload::fromRaw(string $filename, string $rawBytes, ?string $contentType = null)` | Bytes em memória (ex.: PDF gerado na hora). |
| `AttachmentPayload::fromBase64(string $filename, string $base64Content, ?string $contentType = null)` | String já codificada. |

No formato array, um anexo aceita `filename`, `content`, `contentType` (default `application/octet-stream`), `disposition` (default `attachment`) e `cid`. Conteúdo que não seja Base64 válido é codificado automaticamente; itens sem `filename` ou `content` são descartados em silêncio.

### Domínios — `$client->domains`

| Método | Endpoint | Desde |
| --- | --- | --- |
| `create(array\|DomainPayload $payload)` | `POST /v1/product/domains` | 0.1.0 |
| `list(array $query = [])` | `GET /v1/product/domains` | 0.1.0 |
| `get(string $id)` | `GET /v1/product/domains/{id}` | 0.1.0 |
| `verify(string $id)` | `POST /v1/product/domains/{id}/verify` | 0.1.0 |
| `delete(string $id)` | `DELETE /v1/product/domains/{id}` | 0.1.0 |
| `getHealth(string $id)` | `POST /v1/product/domains/{id}/health` | main |
| `getWarmupStatus(string $id)` | `GET /v1/product/domains/{id}/warmup` | main |
| `rotateDkim(string $id)` | `POST /v1/product/domains/{id}/dkim/rotate` | main |
| `setTrackingDomain(string $id, string $host)` | `PUT /v1/product/domains/{id}/tracking-domain` | main |
| `removeTrackingDomain(string $id)` | `DELETE /v1/product/domains/{id}/tracking-domain` | main |

```php
use CoffeeMail\Payloads\DomainPayload;

[$domain, $error] = $client->domains->create(DomainPayload::create('seudominio.com.br'));
[$check, $error] = $client->domains->verify($domain['id']);
```

`get()` traz, além dos registros DNS, `dkimRotation` (rotação pendente ou `null`), `returnPath` e `trackingDomain` (`{host, target, verified, verifiedAt, record}` ou `null`) e `trackingDomainsAvailable` (o plano permite domínio de rastreamento próprio). `getWarmupStatus()` devolve `null` sem aquecimento; com aquecimento, traz `status` (`warming` ou `completed`), `currentDay`, `totalDays`, `dailyQuota`, `sentToday`, `remainingToday`, `quotaUsedPercent`, `lastSendDate`, `startedAt` e `completedAt`.

```php
[$rotation, $error] = $client->domains->rotateDkim($domain['id']);
$record = $rotation['record'];

[$tracking, $error] = $client->domains->setTrackingDomain($domain['id'], 'links.seudominio.com.br');
```

`rotateDkim()` é idempotente: com uma rotação pendente, devolve a mesma. Publique `record` no DNS; a chave em uso só muda depois que o novo seletor propaga. Falha com `NotFoundError` (`apiCode` `DOMAIN_NOT_FOUND`) ou `ConflictError` (`DOMAIN_NOT_VERIFIED`). `setTrackingDomain()` devolve o CNAME a publicar e falha com `ValidationError` (`INVALID_TRACKING_HOST`), `ConflictError` (`TRACKING_DOMAINS_DISABLED`) ou `NotFoundError`.

### Remetentes — `$client->senders` (_não publicado_)

Remetente verificado por endereço, alternativa a verificar o domínio inteiro.

| Método | Endpoint | Desde |
| --- | --- | --- |
| `create(string $email, ?string $displayName = null)` | `POST /v1/product/senders` | main |
| `list()` | `GET /v1/product/senders` | main |
| `verify(string $token)` | `POST /v1/product/senders/verify` | main |
| `delete(string $id)` | `DELETE /v1/product/senders/{id}` | main |

```php
[$sender, $error] = $client->senders->create('suporte@seudominio.com.br', 'Suporte');
```

### Templates — `$client->templates`

| Método | Endpoint | Desde |
| --- | --- | --- |
| `create(array $payload)` | `POST /v1/product/templates` | 0.1.0 |
| `list(array $query = [])` | `GET /v1/product/templates` | 0.1.0 |
| `get(string $id)` | `GET /v1/product/templates/{id}` | 0.1.0 |
| `update(string $id, array $payload)` | `PATCH /v1/product/templates/{id}` | 0.1.0 |
| `delete(string $id)` | `DELETE /v1/product/templates/{id}` | 0.1.0 |
| `preview(array $payload)` | `POST /v1/product/templates/preview` | main (ver nota) |
| `previewById(string $id, array $variables = [])` | `GET /templates/{id}` + `POST /templates/preview` | main |
| `format(array $payload)` | `POST /v1/product/templates/format` | main |
| `testRender(array $payload)` | `POST /v1/product/templates/test-render` | main |
| `testSend(string $id, array $payload)` | `POST /v1/product/templates/{id}/test-send` | main |

> Na `v0.1.0`, `preview(string $id, array $variables = [])` chamava `POST /templates/{id}/preview`, endpoint que a API não tem. Em `main` a assinatura passou a ser `preview(array $payload)`; para pré-visualizar um template salvo use `previewById()`.

```php
[$preview, $error] = $client->templates->preview([
    'html' => '<h1>Oi {{nome}}</h1>',
    'variables' => ['nome' => 'João'],
]);

[$preview, $error] = $client->templates->previewById($templateId, ['nome' => 'Ana']);
```

### Audiências — `$client->audiences`

| Método | Endpoint | Desde |
| --- | --- | --- |
| `create(array $payload)` | `POST /v1/product/audiences` | 0.1.0 |
| `list(array $query = [])` | `GET /v1/product/audiences` | 0.1.0 |
| `get(string $id)` | `GET /v1/product/audiences/{id}` | 0.1.0 |
| `update(string $id, array $payload)` | `PUT /v1/product/audiences/{id}` | 0.2.0 |
| `delete(string $id)` | `DELETE /v1/product/audiences/{id}` | 0.1.0 |
| `listContacts()` / `createContact()` / `bulkAddContacts()` | atalhos para `contacts->list()` / `create()` / `bulkAdd()` | 0.2.0 |

Contatos — `$client->audiences->contacts`:

| Método | Endpoint | Desde |
| --- | --- | --- |
| `create(string $audienceId, array $payload)` | `POST /v1/product/audiences/{id}/contacts` (`email`, `firstName`, `lastName`, `metadata`) | 0.2.0 |
| `list(string $audienceId, array $query = [])` | `GET /v1/product/audiences/{id}/contacts` (`limit`, `offset`) | 0.2.0 |
| `bulkAdd(string $audienceId, array $contacts)` | `POST /v1/product/audiences/{id}/contacts/bulk` (corpo `{ contacts }`, até 10 000) | 0.2.0 |
| `update(string $audienceId, string $contactId, array $payload)` | `PUT /v1/product/audiences/{id}/contacts/{contactId}` | 0.2.0 |
| `delete(string $audienceId, string $contactId)` | `DELETE /v1/product/audiences/{id}/contacts/{contactId}` | 0.2.0 |

```php
[$audience, $error] = $client->audiences->create(['name' => 'Clientes VIP']);
[$contact, $error] = $client->audiences->contacts->create($audience['id'], [
    'email' => 'vip@exemplo.com',
    'firstName' => 'Ana',
]);
```

### Campanhas — `$client->broadcasts`

| Método | Endpoint | Desde |
| --- | --- | --- |
| `create(array $payload)` | `POST /v1/product/broadcasts` | 0.1.0 |
| `list(array $query = [])` | `GET /v1/product/broadcasts` | 0.1.0 |
| `get(string $id)` | `GET /v1/product/broadcasts/{id}` | 0.1.0 |
| `send(string $id)` | `POST /v1/product/broadcasts/{id}/send` | 0.1.0 |
| `cancel(string $id)` | `POST /v1/product/broadcasts/{id}/cancel` | 0.1.0 |
| `testSend(string $id, array $payload)` | `POST /v1/product/broadcasts/{id}/test-send` | main |

Todas exigem chave `full_access`; sem ela, o erro é `PermissionError` (`PERMISSION_DENIED`). A API não tem exclusão de campanha: use `cancel()`.

```php
[$result, $error] = $client->broadcasts->send($broadcastId);
```

### Supressões — `$client->suppressions`

| Método | Endpoint | Desde |
| --- | --- | --- |
| `list(array $query = [])` | `GET /v1/product/suppressions` | 0.1.0 |
| `get(string $id)` | `GET /v1/product/suppressions/{id}` | 0.1.0 |
| `create(array $payload)` | `POST /v1/product/suppressions` | 0.1.0 |
| `delete(string $id)` | `DELETE /v1/product/suppressions/{id}` | 0.1.0 |
| `reactivate(string $id)` | `POST /v1/product/suppressions/{id}/reactivate` | main |
| `bulkCreate(array $suppressions)` | `POST /v1/product/suppressions/bulk` (corpo `{ suppressions }`) | main |

```php
[$page, $error] = $client->suppressions->list(['limit' => 50]);
```

### Webhooks — `$client->webhooks`

| Método | Endpoint | Desde |
| --- | --- | --- |
| `create(array\|WebhookPayload $payload)` | `POST /v1/product/webhooks` | 0.1.0 |
| `list(array $query = [])` | `GET /v1/product/webhooks` | 0.1.0 |
| `get(string $id)` | `GET /v1/product/webhooks/{id}` | 0.1.0 |
| `update(string $id, array $payload)` | `PUT /v1/product/webhooks/{id}` | 0.1.0 (verbo corrigido na 0.2.0) |
| `delete(string $id)` | `DELETE /v1/product/webhooks/{id}` | 0.1.0 |
| `toggle(string $id, bool $enabled)` | `PATCH /v1/product/webhooks/{id}` com `status` `active`/`paused` | 0.1.0 (endpoint corrigido em main) |
| `rotateSecret(string $id)` | `POST /v1/product/webhooks/{id}/rotate-secret` | 0.1.0 |
| `test(string $id)` | `POST /v1/product/webhooks/{id}/test` | 0.1.0 |
| `listDeliveries(string $id, array $query = [])` | `GET /v1/product/webhooks/{id}/deliveries` | 0.1.0 |
| `Webhooks::verifySignature(string $payload, string $secret, array $headers = [], ...)` | local (estático) | 0.2.0 (ver [Webhooks](#-webhooks)) |

```php
use CoffeeMail\Payloads\WebhookPayload;

[$webhook, $error] = $client->webhooks->create(WebhookPayload::create(
    url: 'https://seuapp.com.br/webhooks/coffeemail',
    events: ['email.delivered', 'email.bounced'],
    description: 'Webhook principal',
));
```

`WebhookPayload` tem só os campos do contrato: `url`, `events` e `description`.

Eventos aceitos pela API: `email.queued`, `email.processing`, `email.sent`, `email.delivered`, `email.delivery_delayed`, `email.opened`, `email.clicked`, `email.bounced`, `email.complained`, `email.failed`, `email.suppressed`, `email.scheduled`, `email.cancelled`, `email.unsubscribed`, `billing.plan_changed`.

### Métricas — `$client->stats`

| Método | Endpoint | Desde |
| --- | --- | --- |
| `get(array $query = [])` | `GET /v1/product/stats` | 0.1.0 |

```php
[$stats, $error] = $client->stats->get();
```

## 🔔 Webhooks

A API assina cada entrega assim:

| Header | Conteúdo |
| --- | --- |
| `x-webhook-signature` | `sha256=` + `hex(HMAC-SHA256(secret, "{timestamp}.{idempotencyKey}.{body}"))` |
| `x-webhook-timestamp` | Unix timestamp em segundos |
| `x-webhook-idempotency-key` | Chave aleatória da entrega |
| `x-webhook-test` | `true` em entregas disparadas por `webhooks->test()` |

O segredo tem o formato `whsec_...` e pode ser rotacionado com `webhooks->rotateSecret()`. A API trabalha com janela de tolerância de 300 segundos.

Valide com `Webhooks::verifySignature()`, passando o corpo **bruto** e os cabeçalhos da requisição:

```php
use CoffeeMail\Resources\Webhooks;

$body = file_get_contents('php://input') ?: '';
$headers = function_exists('getallheaders') ? getallheaders() : [];

if (! Webhooks::verifySignature($body, getenv('COFFEEMAIL_WEBHOOK_SECRET') ?: '', $headers)) {
    http_response_code(401);
    exit;
}

$event = json_decode($body, true);
http_response_code(204);
```

- Os nomes de cabeçalho são comparados sem diferenciar maiúsculas; valores em lista (como `$request->headers->all()` do Symfony/Laravel) são aceitos.
- Também dá para passar os valores direto: `verifySignature($body, $secret, signature: ..., timestamp: ..., idempotencyKey: ...)`.
- `toleranceSeconds` (padrão 300) ajusta a janela; `now` fixa o relógio em testes.
- `WebhookVerifier::sign($body, $secret, $timestamp, $idempotencyKey)` calcula a assinatura esperada, útil para testar o seu endpoint.
- A comparação é exata e em tempo constante (hex maiúsculo é rejeitado). O SDK passa nos vetores de teste gerados pelo assinador da API (`tests/Fixtures/webhook-vectors.json`).
- `x-webhook-idempotency-key` muda a cada tentativa de entrega; não serve para deduplicar retentativas.

Use o corpo **bruto**: qualquer re-serialização do JSON invalida a assinatura. Em Laravel, o pacote [coffee-mail-laravel](#laravel) já traz middleware para isso.

<a id="laravel"></a>

### Laravel

Para Laravel existe o pacote dedicado [`coffeemail/coffeemail-laravel`](https://github.com/Coffee-Mail/coffee-mail-laravel), construído sobre este SDK: mail driver para `Mail::to()`/`Mailable`/notificações, facade, configuração por `.env` e middleware de webhook. Prefira-o a registrar o cliente manualmente no container.

## 🔖 Versionamento e releases

- **SemVer.** Na série `0.x`, mudanças incompatíveis podem ocorrer entre minors, mas a regra abaixo vale desde já.
- **Compatibilidade retroativa.** A partir da `0.2.0`, a superfície pública (classes, métodos, nomes de parâmetro, propriedades, formatos de retorno) só pode **ganhar** itens ou ser **depreciada** com `@deprecated`; nunca renomeada nem removida. A `0.2.0` foi a exceção: quebrou a `0.1.0` para corrigir o contrato enquanto ainda não havia usuários ativos. Os SDKs oficiais (Node.js, Python, PHP, Java, Go, Ruby) seguem o mesmo contrato: funcionalidade nova entra em todos.
- **Publicação.** Um push de tag `v*` dispara [`.github/workflows/release.yml`](.github/workflows/release.yml), que valida o `composer.json`, roda `composer check` em PHP 8.2 e cria o GitHub Release com notas geradas automaticamente. O Packagist lê as tags do repositório. A versão fica em `CoffeeMail\Version::VALUE` (usada no `User-Agent`); `composer check:version` confere se ela tem seção no `CHANGELOG.md` e, no release, se bate com a tag.
- **Changelog.** [CHANGELOG.md](CHANGELOG.md), no formato Keep a Changelog.

## 🧪 Testes e qualidade

```bash
composer install
composer test
composer analyse
composer check
```

| Script | Comando | Descrição |
| --- | --- | --- |
| `composer test` | `pest` | Suíte Pest (`tests/Unit`), com transporte falso em `tests/Support/FakeTransport.php`. |
| `composer analyse` | `phpstan analyse --memory-limit=512M` | PHPStan nível 9 sobre `src` e `tests` ([`phpstan.neon`](phpstan.neon)). |
| `composer lint` | `phpcs` | PSR-12 em `src` e `tests` ([`phpcs.xml`](phpcs.xml)). |
| `composer format` | `phpcbf` | Corrige automaticamente o que o phpcs apontar. |
| `composer check:standards` | `php scripts/check-standards.php` | Barra `else`, `elseif` e `switch` em `src`. |
| `composer check` | `lint` + `analyse` + `test` + `check:standards` | Gate completo, usado no release. |

CI ([`.github/workflows/ci.yml`](.github/workflows/ci.yml)) roda em push e PR para `main`, em PHP 8.2, 8.3 e 8.4: `composer validate --no-check-lock`, `composer update`, phpcs, PHPStan, Pest e `check:standards`.

## 🗂️ Estrutura do repositório

```text
src/
├── CoffeeMail.php              cliente e composição dos recursos
├── Version.php                 versão do SDK (User-Agent)
├── Exceptions/                 CoffeeMailException, subclasses tipadas e ErrorCodes
├── Http/                       CoffeeMailResponse, HttpTransportInterface, CurlTransport,
│                               ResponseParser e ErrorMapper
├── I18n/I18n.php               mensagens locais em pt-BR e en
├── Payloads/                   EmailPayload, AttachmentPayload, DomainPayload, WebhookPayload
├── Resources/                  Emails, Domains, Senders, Templates, Audiences, Contacts,
│                               Broadcasts, Suppressions, Webhooks, Stats
└── Webhooks/WebhookVerifier.php verificação de assinatura de webhook
tests/
├── Fixtures/                   vetores de webhook gerados pelo assinador da API
├── Support/FakeTransport.php   transporte em memória para os testes
└── Unit/                       testes por recurso, payloads, envelope, erros e transporte
scripts/check-standards.php     gate de else/elseif/switch
scripts/check-version-sync.php  gate de versão (Version::VALUE, CHANGELOG, tag)
.github/workflows/              ci.yml e release.yml
```

## 🤝 Contribuição

Leia o [CONTRIBUTING.md](CONTRIBUTING.md). Em resumo: código em inglês, documentação, commits e PRs em pt-BR (`tipo(escopo): descrição`); sem `else`/`elseif`/`switch`; sem textos e números mágicos; cada comportamento novo com teste de caminho feliz e ao menos dois de erro; mudanças de API pública discutidas em issue antes do PR.

Vulnerabilidades: **não** abra issue pública; siga o [SECURITY.md](SECURITY.md).

## 🔗 Projetos relacionados

| Repositório | Papel |
| --- | --- |
| [coffee-mail-sdk-node](https://github.com/Coffee-Mail/coffee-mail-sdk-node) | SDK oficial Node.js/TypeScript (`@coffeemail/node`) |
| [coffee-mail-sdk-python](https://github.com/Coffee-Mail/coffee-mail-sdk-python) | SDK oficial Python (`coffeemail`) |
| **coffee-mail-sdk-php** (você está aqui) | SDK oficial PHP (`coffeemail/coffeemail-php`) |
| [coffee-mail-sdk-java](https://github.com/Coffee-Mail/coffee-mail-sdk-java) | SDK oficial Java (`br.com.coffeemail:coffeemail-java`) |
| [coffee-mail-sdk-go](https://github.com/Coffee-Mail/coffee-mail-sdk-go) | SDK oficial Go (`github.com/Coffee-Mail/coffee-mail-sdk-go`) |
| [coffee-mail-sdk-ruby](https://github.com/Coffee-Mail/coffee-mail-sdk-ruby) | SDK oficial Ruby (`coffeemail`) |
| [coffee-mail-laravel](https://github.com/Coffee-Mail/coffee-mail-laravel) | Integração Laravel (`coffeemail/coffeemail-laravel`: mail driver + facade) |
| [coffee-mail-sdk-workbench](https://github.com/Coffee-Mail/coffee-mail-sdk-workbench) | Bancada de validação dos SDKs por spec |

## 📄 Licença

[MIT](LICENSE) © 2026 Dev-Etto.
