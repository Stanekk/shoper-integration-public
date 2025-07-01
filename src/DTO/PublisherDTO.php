<?php

namespace App\DTO;

class PublisherDTO
{
    public ?int $id;
    public ?string $name;
    public ?string $shoperId;
    public ?int $wholesalerId;
    public ?string $wholesalerName;

    public function __construct(?int $id, ?string $name, ?string $shoperId,?int $wholesalerId,?string $wholesalerName)
    {
        $this->id = $id;
        $this->name = $name;
        $this->shoperId = $shoperId;
        $this->wholesalerId = $wholesalerId;
        $this->wholesalerName = $wholesalerName;
    }
}
