<?php

namespace App\Controller;

use App\Exception\ServiceException;
use App\Service\ResetPasswordService;
use App\Service\UserService;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password', name: 'app_reset_password')]
    public function indexAction(
        Request $request,
        UserService $userService,
        ResetPasswordService $resetPasswordService
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $submittedCsrfToken = $request->getPayload()->get('_csrf_token');
        $resetPasswordErrors = [];

        if ($this->isCsrfTokenValid('csrf-reset-password', $submittedCsrfToken)) {
            $email = $request->request->get('email');
            if (!$userService->checkIfUserExist($email)) {
                $resetPasswordErrors[] = "The specified user does not exist";
                return $this->render('security/reset-password.html.twig', [
                    'resetPasswordErrors' => $resetPasswordErrors,
                ]);
            }
            try {
                $resetPasswordService->generateUserToken($email);
                $resetPasswordService->sendResetPasswordEmail($email);
                return $this->redirectToRoute('app_reset_password_success');
            } catch (ServiceException $exception) {
                $resetPasswordErrors[] = $exception->getMessage();
            } catch (RandomException $e) {
                $resetPasswordErrors[] = $e->getMessage();
            }
        }

        return $this->render('security/reset-password.html.twig', [
            'resetPasswordErrors' => $resetPasswordErrors,
        ]);
    }

    #[Route('/reset-password-success', name: 'app_reset_password_success')]
    public function successAction(
        Request $request,
        UserService $userService,
        ResetPasswordService $resetPasswordService
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/reset-password-send-success.html.twig');
    }

    #[Route('/reset-password-validation', name: 'app_reset_password_validation', methods: ['GET', 'POST'])]
    public function validationAction(Request $request, ResetPasswordService $resetPasswordService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $submittedCsrfToken = $request->getPayload()->get('_csrf_token');
        $email = $request->query->get('email');
        $token = $request->query->get('token');

        if($token === null || $email === null) {
            return $this->redirectToRoute('app_login');
        }

        $resetPasswordErrors = [];

        if ($this->isCsrfTokenValid('csrf-reset-password', $submittedCsrfToken)) {
            if ($request->getMethod() == 'POST') {
                $password = $request->request->get('password');
                $passwordRepeat = $request->request->get('repeatPassword');
                try {
                    $resetPasswordService->processResetPasswordToken($token, $email, $password, $passwordRepeat);
                    $this->addFlash('success', 'Your password has been reset. You may now log in.');
                    return $this->redirectToRoute('app_login');
                } catch (ServiceException $exception) {
                    $resetPasswordErrors[] = $exception->getMessage();
                } catch (\Exception $exception) {
                    $resetPasswordErrors[] = ['An error occurred during password reset, try again later'];
                }
            }
        }

        return $this->render('security/reset-password-validation.html.twig',
                ['resetPasswordErrors' => $resetPasswordErrors]);

    }
}