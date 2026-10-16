# Changelog

Todas as mudanças relevantes deste projeto são documentadas aqui. O formato segue o [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o projeto adota o [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não lançado]

### Adicionado

- `senders->resendVerification($id)` reenvia o e-mail de verificação com token novo.
- Documentação de `broadcasts->create()` cobre `scheduledAt` (ISO 8601, no futuro: a campanha nasce `scheduled`) e de `broadcasts->get()` cobre `metrics`; os campos já trafegavam pelo payload em array.

### Alterado

- A resposta de `senders->create()` não traz mais `verificationToken`: o token segue por e-mail ao próprio endereço cadastrado.
- `broadcasts->send()` responde assim que o envio é enfileirado (`status` `sending`); o progresso acompanha-se por `broadcasts->get()`.

## [0.2.0] - Não publicado

Esta versão corrige o contrato com a API e **quebra a API pública da 0.1.0**. A quebra foi aceita porque ainda não havia usuários ativos; a partir daqui volta a regra de só adicionar e depreciar.

### Adicionado

- `CoffeeMail\Webhooks\WebhookVerifier` com `verify()` e `sign()`.
- Erros: `apiCode` (código original da API), `requestId` (header `x-request-id`), constantes `ErrorCodes` e a classe `PermissionError` (`PERMISSION_DENIED`, `requiredPermission`).
- `audiences->update()` e o sub-recurso `audiences->contacts` (`create`, `list`, `bulkAdd`, `update`, `delete`), mais os atalhos `audiences->createContact()`, `listContacts()` e `bulkAddContacts()`.
- `CoffeeMail\Version::VALUE` e o gate `composer check:version`.
- `senders->create()` aceita `displayName` opcional.
- `emails->send()` e `sendBatch()` repassam `metadata`, `emailType` e `suppressionCategory`, antes descartados.
- `EmailPayload` ganha `templateId`, `variables`, `metadata`, `emailType` e `suppressionCategory`.

### Alterado

- **Quebra:** `Webhooks::verifySignature()` usa o algoritmo da API (`sha256=` + HMAC-SHA256 de `timestamp.idempotencyKey.corpo`, janela de 300s) e a assinatura passou a ser `verifySignature(string $payload, string $secret, array $headers = [], ?string $signature, ?string $timestamp, ?string $idempotencyKey, int $toleranceSeconds = 300, ?int $now = null)`. A versão anterior calculava `HMAC(corpo)` e rejeitava toda entrega real.
- **Quebra:** `details` dos erros é sempre `array` e reúne `meta` e `details` do corpo de erro da API.
- **Quebra:** `InternalServerError` usa o código canônico `INTERNAL_SERVER_ERROR` (antes `INTERNAL_ERROR`) e só cobre 500, 502, 503 e 504, com o status real. Outros status de erro viram `CoffeeMailException` com o código da API ou `API_ERROR`.
- **Quebra:** resposta 2xx com JSON ilegível vira erro `INVALID_RESPONSE` em vez de `data = ['message' => corpo]`.
- **Quebra:** 403 em operações de campanha vira `PermissionError` em vez de `ForbiddenError`.
- **Quebra:** `suppressions->get()` e `delete()` recebem o ID da supressão (parâmetro `$id`).
- `webhooks->update()` usa `PUT`, como a API.
- `emails->send()`/`sendBatch()` validam o conteúdo como a API: `html`/`text` com `subject`, ou `templateId`, nunca os dois. `subject` deixou de ser exigido em envios por template.
- **Quebra:** `EmailPayload::$subject` passou a ser `?string` (opcional com `templateId`).
- `audiences->bulkAddContacts()` envia `{ "contacts": [...] }`, formato que a API espera.
- `Content-Type: application/json` só é enviado quando há corpo.
- `User-Agent` passa a refletir a versão real (`coffeemail-php/0.2.0`).

### Removido

- **Quebra:** `broadcasts->delete()` (a API não tem a rota; use `cancel()`).
- **Quebra:** `audiences->addContact()` e `removeContact()` (use `audiences->contacts`).
- **Quebra:** `WebhookPayload::$name` e o parâmetro `name` de `WebhookPayload::create()`; o campo não existe no contrato e a API o rejeitava. A assinatura passou a ser `create(string $url, array $events, ?string $description = null)`.

## [0.1.0] - 2026-10-08

### Adicionado

- Primeira versão publicada no Packagist.
