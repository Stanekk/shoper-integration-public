<?php

namespace App\DTO;

class WholesalerDTO
{
    public ?int $id;
    public ?string $name;
    public array $publishers;

    public function __construct(?int $id, ?string $name, array $publishers)
    {
        $this->id = $id;
        $this->name = $name;
        $this->publishers = $publishers;
    }
}