<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{

    private $mailer;

    private static string $SENDER_EMAIL = 'swi@standev.pl';

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendResetPasswordEmail($user, $resetLink)
    {
        $email = (new TemplatedEmail())
            ->from(self::$SENDER_EMAIL)
            ->to($user->getEmail())
            ->subject('Reset password')
            ->htmlTemplate('emails/reset-password.html.twig')
            ->context([
                'link' => $resetLink,
            ]);

        $this->mailer->send($email);
    }


}