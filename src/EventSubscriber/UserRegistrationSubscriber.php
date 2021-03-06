<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserRegistrationSubscriber implements EventSubscriberInterface
{
    private $mailer;
    private $twig;
    private $urlGenerator;
    private $sender;

    public function __construct(
        \Swift_Mailer $mailer,
        \Twig_Environment $twig,
        UrlGeneratorInterface $urlGenerator,
        $sender
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->sender = $sender;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::REGISTRATION_SUCCESS => 'onRegistrationSuccess',
        ];
    }

    public function onRegistrationSuccess(GenericEvent $event): void
    {
        /** @var User $user */
        $user = $event->getSubject();

        $confirmationUrl = $this->urlGenerator->generate('registration_confirm', [
            'confirmationToken' => $user->getConfirmationToken(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $template = $this->renderTemplate([
            'user' => $user,
            'confirmationUrl' => $confirmationUrl,
            'resend' => $event->hasArgument('resend'),
        ]);

        $message = (new \Swift_Message())
            ->setSubject(sprintf('Selamat datang %s, konfirmasi akun anda!', $user->getNamaLengkap()))
            ->setTo($user->getEmail())
            ->setFrom($this->sender)
            ->setBody($template, 'text/html')
        ;

        $this->mailer->send($message);
    }

    private function renderTemplate(array $options)
    {
        $template = $options['resend'] ?
            'resend_user_confirmation_token/confirm_resend_user_confirmation_token_email.html.twig' :
            'registration/confirm_registration_email.html.twig'
        ;

        return $this->twig->render($template, [
            'user' => $options['user'],
            'confirmationUrl' => $options['confirmationUrl'],
        ]);
    }
}
