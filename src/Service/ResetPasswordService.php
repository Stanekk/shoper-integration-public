<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\ServiceException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Exception;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordService
{

    private $userService;
    private $emailService;
    private $logger;
    private $entityManager;
    private $urlGenerator;

    public function __construct(
        UserService $userService,
        EmailService $emailService,
        LoggerService $logger,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->userService = $userService;
        $this->emailService = $emailService;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @throws RandomException
     */
    public function generateToken(): string
    {
        $part1 = $this->generatePart(4);
        $part2 = $this->generatePart(4);
        $part3 = $this->generatePart(6);

        return sprintf('%s-%s-%s', $part1, $part2, $part3);
    }

    /**
     * @throws RandomException
     */
    private function generatePart(int $length): string
    {
        return strtoupper(bin2hex(random_bytes($length)));
    }


    public function generateResetPasswordToken(): string
    {
        try {
            $token = $this->generateToken();
        } catch (\Exception $e) {
            $token = null;
        }

        return $token;
    }

    /**
     * @throws ServiceException
     */
    public function generateUserToken($email): void
    {
        $user = $this->userService->getUserByEmail($email);

        if (!$user instanceof User) {
            throw new ServiceException('User not found.');
        }

        $token = $this->generateResetPasswordToken();

        if ($token === null) {
            throw new ServiceException('Invalid reset password token, please try again.');
        }

        $expireAt = (new \DateTimeImmutable())->modify('+20 minutes');

        $user->setResetPasswordToken($token);
        $user->setResetPasswordTokenExpireAt($expireAt);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function validateResetPasswordToken(string $token, string $email): bool
    {
        $user = $this->userService->getUserByEmail($email);
        if (!$user instanceof User) {
            return false;
        }
        if ($user->getResetPasswordTokenExpireAt() <= new \DateTimeImmutable()) {
            return false;
        }

        if (hash_equals($user->getResetPasswordToken(), $token)) {
            return true;
        }

        return false;
    }

    public function sendResetPasswordEmail($email): void
    {
        $user = $this->userService->getUserByEmail($email);

        if ($user instanceof User) {
            $link = $this->generateResetPasswordLink($user);
            $this->emailService->sendResetPasswordEmail($user, $link);
        }
    }

    private function generateResetPasswordLink($user): ?string
    {
        $link = null;
        if ($user instanceof User) {
            $token = $user->getResetPasswordToken();
            $email = $user->getEmail();
            $link = $this->urlGenerator->generate('app_reset_password_validation',
                ['token' => $token, 'email' => $email], UrlGeneratorInterface::ABSOLUTE_URL);
            $this->logger->getUserLogger()->info(sprintf('Reset password link generated for user %s.', $email));
        }
        return $link;
    }

    /**
     * @throws ServiceException
     */
    public function processResetPasswordToken(
        string $token,
        string $email,
        string $newPassword,
        string $newPasswordRepeat
    ): void {
        $user = $this->userService->getUserByEmail($email);
        if (!$this->validateResetPasswordToken($token, $email)) {
            throw new ServiceException('Invalid reset password token, please try again.');
        }
        $this->userService->resetUserPassword($user, $newPassword, $newPasswordRepeat);
        $this->logger->getUserLogger()->info(sprintf('Reset password change for user %s.', $email));
        $this->clearUserToken($user);
    }

    public function clearUserToken($user): void
    {
        if($user instanceof User){
            $user->setResetPasswordToken(null);
            $user->setResetPasswordTokenExpireAt(null);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

}