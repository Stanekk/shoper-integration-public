<?php

namespace App\Service;

use App\Entity\ShoperConnectionSettings;
use App\Repository\ShoperConnectionSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ShoperConnectionSettingsService
{
    private EntityManagerInterface $entityManager;
    private ShoperConnectionSettingsRepository $shoperConnectionSettingsRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ShoperConnectionSettingsRepository $shoperConnectionSettingsRepository,
    ) {
        $this->entityManager = $entityManager;
        $this->shoperConnectionSettingsRepository = $shoperConnectionSettingsRepository;
    }

    public function getShoperConnectionSettings()
    {
        $settings = $this->shoperConnectionSettingsRepository->findAll();
        if (count($settings) > 0) {
            return $settings[0];
        }
        return new ShoperConnectionSettings();
    }

    public function clearAllSettings()
    {
        $settings = $this->shoperConnectionSettingsRepository->findAll();
        foreach ($settings as $setting) {
            $this->entityManager->remove($setting);
        }
        $this->entityManager->flush();
    }

    public function saveShoperConnectionSettings(ShoperConnectionSettings $settings): void
    {
        if ($settings->getRestUser() && $settings->getRestPassword() && $settings->getShopUrl()) {
            $settings->setCreatedAt(new \DateTimeImmutable('now'));
            $this->clearAllSettings();
            $this->entityManager->persist($settings);
            $this->entityManager->flush();
        }
    }
}