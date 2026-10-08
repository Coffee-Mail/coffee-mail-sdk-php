<?php

declare(strict_types=1);

namespace CoffeeMail\Http;

use ArrayAccess;
use CoffeeMail\Exceptions\CoffeeMailException;
use LogicException;

/**
 * @template-covariant T
 * @implements ArrayAccess<int, mixed>
 */
final readonly class CoffeeMailResponse implements ArrayAccess
{
    /**
     * @param T|null $data
     */
    public function __construct(
        public mixed $data,
        public ?CoffeeMailException $error,
        public int $statusCode,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    public function isError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Retorna os dados se a resposta for bem-sucedida ou lança a exceção tipada em caso de erro.
     *
     * @return T
     * @throws CoffeeMailException
     */
    public function unwrap(): mixed
    {
        if ($this->error !== null) {
            throw $this->error;
        }

        /** @var T */
        return $this->data;
    }

    /**
     * Retorna uma tupla contendo [data, error] para desestruturação explícita.
     *
     * @return array{0: T|null, 1: CoffeeMailException|null}
     */
    public function toTuple(): array
    {
        return [$this->data, $this->error];
    }

    public function offsetExists(mixed $offset): bool
    {
        return $offset === 0 || $offset === 1;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            0 => $this->data,
            1 => $this->error,
            default => null,
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('A resposta do CoffeeMail é imutável.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('A resposta do CoffeeMail é imutável.');
    }
}
