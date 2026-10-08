<?php

declare(strict_types=1);

namespace CoffeeMail\Payloads;

interface PayloadInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
