<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\ServiceException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private $userRepository;
    private $entityManager;
    private $passwordHasher;

    private static int $MINIMUM_PASSWORD_LENGTH = 6;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function checkIfUserExist($email): bool
    {
        if ($this->userRepository->findOneBy(['email' => $email])) {
            return true;
        }
        return false;
    }

    public function getUserByEmail($email): ?User
    {
        return $this->userRepository->findOneBy(['email' => $email]);
    }

    /**
     * @throws ServiceException
     */
    public function createNewUser($user, $passwordRepeat): void
    {
        if ($this->checkIfUserExist($user->getEmail())) {
            throw new ServiceException("User already exist");
        }

        if(!$this->checkPasswordRepeat($user->getPassword(), $passwordRepeat)) {
            throw new ServiceException("The passwords do not match.");
        }

        if(!$this->checkPasswordLength($user->getPassword())) {
            throw new ServiceException("Password length must be at least " . self::$MINIMUM_PASSWORD_LENGTH);
        }

        $plainTextPassword = $user->getPassword();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainTextPassword);
        $user->setPassword($hashedPassword);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @throws ServiceException
     */
    public function deleteUser($currentUser, $userToDelete): void
    {
        $searchUser = $this->userRepository->findOneBy(['id' => $userToDelete]);
        if ($searchUser instanceof User) {
            if ($searchUser->getId() === $currentUser->getId()) {
                throw new ServiceException("You can't delete your account");
            }
            if ($this->isSuperAdmin($searchUser)) {
                throw new ServiceException("Super admin cannot be removed");
            }
            $this->entityManager->remove($searchUser);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws ServiceException
     */
    public function getUserById(int $id): User
    {
        $searchUser = $this->userRepository->findOneBy(['id' => $id]);
        if ($searchUser instanceof User) {
            return $searchUser;
        }
        throw new ServiceException("User not found");
    }

    public function isSuperAdmin($user): bool
    {
        if ($user instanceof User) {
            $roles = $user->getRoles();
            if (in_array("ROLE_SUPER_ADMIN", $roles)) {
                return true;
            }
        }
        return false;
    }

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    /**
     * @throws ServiceException
     */
    public function changeUserPassword(User $currentUser, $oldPassword, $newPassword, $repeatPassword): void
    {
        if (!$this->isPasswordValid($currentUser, $oldPassword)) {
            throw new ServiceException("Wrong current password");
        }

        if (!$this->checkPasswordRepeat($newPassword, $repeatPassword)) {
            throw new ServiceException("Passwords do not match");
        }
        if (!$this->checkPasswordLength($newPassword)) {
            throw new ServiceException("The password should be at least 6 characters long");
        }

        $hashedPassword = $this->passwordHasher->hashPassword($currentUser, $newPassword);
        $currentUser->setPassword($hashedPassword);

        $this->entityManager->persist($currentUser);
        $this->entityManager->flush();

    }

    /**
     * @throws ServiceException
     */
    public function resetUserPassword(User $currentUser, $newPassword, $repeatPassword): void
    {

        if (!$this->checkPasswordRepeat($newPassword, $repeatPassword)) {
            throw new ServiceException("Passwords do not match");
        }
        if (!$this->checkPasswordLength($newPassword)) {
            throw new ServiceException("The password should be at least 6 characters long");
        }

        $hashedPassword = $this->passwordHasher->hashPassword($currentUser, $newPassword);
        $currentUser->setPassword($hashedPassword);

        $this->entityManager->persist($currentUser);
        $this->entityManager->flush();

    }

    public function checkPasswordLength($password): bool
    {
        if(strlen($password) < self::$MINIMUM_PASSWORD_LENGTH) {
            return false;
        }
        return true;
    }

    public function checkPasswordRepeat($password, $passwordRepeat): bool
    {
        return $password === $passwordRepeat;
    }

}