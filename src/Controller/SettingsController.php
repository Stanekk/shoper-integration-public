<?php

namespace App\Controller;


use App\Entity\User;
use App\Exception\ServiceException;
use App\Form\Type\ChangePasswordType;
use App\Form\Type\NewUserType;
use App\Service\LoggerService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends AbstractController
{
    #[Route(path: '/settings', name: 'app_settings', methods: ['GET', 'POST'])]
    public function indexAction(Request $request, UserService $userService, LoggerService $loggerService): Response
    {
        $user = new User();
        $users = $userService->getAllUsers();
        $currentUser = $this->getUser();

        $newUserForm = $this->createForm(NewUserType::class, $user);
        $newUserForm->handleRequest($request);

        $changePasswordForm = $this->createForm(ChangePasswordType::class);
        $changePasswordForm->handleRequest($request);

        if ($newUserForm->isSubmitted() && $newUserForm->isValid()) {
            $passwordRepeat = $newUserForm->get('passwordRepeat')->getData();

            try {
                $userService->createNewUser($user, $passwordRepeat);
                $loggerService->getUserLogger()->info('User: ' . $currentUser->getEmail(). ' created new user: ' . $user->getEmail());
                $this->addFlash('success', 'The user has been created.');
                return $this->redirectToRoute('app_settings');
            } catch (ServiceException $exception) {
                $this->addFlash('error', $exception->getMessage());
            } catch (\Exception $e) {
                //
            }
        }

        if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {
            $oldPassword = $changePasswordForm->get('oldPassword')->getData();
            $password = $changePasswordForm->get('password')->getData();
            $passwordRepeat = $changePasswordForm->get('passwordRepeat')->getData();
            try {
                $userService->changeUserPassword($currentUser,$oldPassword, $password, $passwordRepeat);
                $loggerService->getUserLogger()->info('User: ' . $currentUser->getEmail(). ' changed password');
                $this->addFlash('success', 'Password has been changed.');
            } catch (ServiceException $exception) {
                $this->addFlash('error', $exception->getMessage());
            } catch (\Exception $e) {
                //
            }
        }

        return $this->render('pages/settings/settings.html.twig', [
            'newUserForm' => $newUserForm->createView(),
            'changePasswordForm' => $changePasswordForm->createView(),
            'users' => $users,
        ]);
    }

    #[Route(path: '/settings/user/delete/{id}', name: 'app_settings_user_delete', methods: ['GET'])]
    public function deleteUserAction(Request $request, $id, UserService $userService, LoggerService $loggerService): Response
    {
        $userToDelete = $id;
        $currentUser = $this->getUser();

        try {
            $userToDeleteObject = $userService->getUserById($userToDelete);
            $userService->deleteUser($currentUser, $userToDelete);
            $loggerService->getUserLogger()->info('User: ' . $currentUser->getEmail(). ' deleted: ' . $userToDeleteObject->getEmail());
            $this->addFlash('success', 'The user has been deleted.');
        } catch (ServiceException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            //
        }

        return $this->redirectToRoute('app_settings');
    }
}