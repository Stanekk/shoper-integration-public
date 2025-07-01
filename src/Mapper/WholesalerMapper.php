<?php

namespace App\Mapper;

use App\DTO\WholesalerDTO;
use App\DTO\PublisherDTO;
use App\Entity\Wholesaler;

class WholesalerMapper
{
    public static function toDTO(Wholesaler $wholesaler): WholesalerDTO
    {
        $publishersDTO = [];
        foreach ($wholesaler->getPublishers() as $publisher) {
            $publishersDTO[] = new PublisherDTO(
                $publisher->getId(),
                $publisher->getName(),
                $publisher->getShoperId(),
                $publisher->getWholesaler()?->getId(),
                $publisher->getWholesaler()?->getName(),
            );
        }

        return new WholesalerDTO(
            $wholesaler->getId(),
            $wholesaler->getName(),
            $publishersDTO
        );
    }
}
