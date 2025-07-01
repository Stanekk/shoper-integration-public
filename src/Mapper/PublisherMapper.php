<?php

namespace App\Mapper;

use App\DTO\PublisherDTO;
use App\Entity\Publisher;

class PublisherMapper
{
    public static function toDTO(Publisher $publisher): PublisherDTO
    {
        return new PublisherDTO(
           $publisher->getId(),
            $publisher->getName(),
            $publisher->getShoperId(),
            $publisher->getWholesaler()?->getId(),
            $publisher->getWholesaler()?->getName(),
        );
    }
}