# CoffeeMail PHP SDK

Cliente oficial do CoffeeMail para PHP moderno (PHP 8.2+). Fornece acesso fortemente tipado a todos os recursos da API de Produto:

* **E-mails**: Envio transacional e em lote, consulta de status, cancelamento, reenvio e tags.
* **Domínios**: Registro e verificação de DNS (SPF, DKIM, DMARC).
* **Modelos**: Gestão de templates e renderização de prévias.
* **Audiências**: Listas de contatos e audiências.
* **Campanhas**: Broadcasts e disparos em massa.
* **Supressões**: Lista de descadastros e bounces.
* **Webhooks**: Gestão de endpoints e validação criptográfica de assinatura HMAC SHA-256.
* **Estatísticas**: Métricas e taxas de entrega.

---

## Requisitos

* PHP 8.2 ou superior.
* Extensões PHP: `ext-curl`, `ext-json`.

---

## Instalação

```bash
composer require coffeemail/coffeemail-php
```

---

## Exemplo Rápido

O SDK adota o padrão seguro de retorno em envelope `{ data, error }`, sem disparar exceções não tratadas para respostas de erro da API:

```php
use CoffeeMail\CoffeeMail;

$client = new CoffeeMail('cm_live_sua_chave');

// Sintaxe 1: Desestruturação nativa em tupla
[$data, $error] = $client->emails->send([
    'from' => 'contato@seudominio.com.br',
    'to' => 'cliente@gmail.com',
    'subject' => 'Bem-vindo ao CoffeeMail!',
    'html' => '<h1>Olá!</h1><p>Sua conta está ativa.</p>',
]);

if ($error !== null) {
    echo "Erro ao enviar [{$error->code}]: {$error->message}\n";
    exit(1);
}

echo "E-mail enfileirado com sucesso: {$data->id}\n";
```

---

## Desenvolvimento e Testes

```bash
# Executar análise estática (PHPStan nível 9)
composer analyse

# Executar suíte de testes (Pest PHP)
composer test

# Validação completa de qualidade
composer check
```
