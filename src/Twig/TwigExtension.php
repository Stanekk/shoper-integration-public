<?php

namespace App\Twig;

use App\Api\ShoperConnectionApi;
use App\Service\UserService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{

    private UserService $userService;
    private ShoperConnectionApi $shoperConnectionApi;

    public function __construct(UserService $userService, ShoperConnectionApi $shoperConnectionApi)
    {
        $this->userService = $userService;
        $this->shoperConnectionApi = $shoperConnectionApi;
    }
    public function getFunctions(): array
    {
        return [
            new TwigFunction('isSuperAdmin', [$this, 'isSuperAdmin']),
            new TwigFunction('isShoperConnection', [$this, 'isShoperConnection']),
        ];
    }

    public function isSuperAdmin($user): bool
    {
        return $this->userService->isSuperAdmin($user);
    }

    public function isShoperConnection(): bool
    {
        return $this->shoperConnectionApi->isConnected();
    }
}
