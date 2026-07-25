<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure;

use App\Notification\Application\NotificationChannel;
use App\Notification\Domain\EmailNotification;
use App\Notification\Domain\Notification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Delivers email notifications by rendering their localisable Twig templates (CE-027) and sending
 * them through the mailer. It handles any {@see EmailNotification}, independent of its type.
 */
final readonly class EmailChannel implements NotificationChannel
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail,
    ) {
    }

    public function name(): string
    {
        return 'email';
    }

    public function send(Notification $notification): void
    {
        if (!$notification instanceof EmailNotification) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($notification->recipient())
            ->subject($notification->subject())
            ->htmlTemplate($notification->htmlTemplate())
            ->textTemplate($notification->textTemplate())
            ->locale($notification->locale())
            ->context($notification->context());

        $this->mailer->send($email);
    }
}
