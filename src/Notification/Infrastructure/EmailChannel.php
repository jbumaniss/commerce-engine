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
 *
 * A notification that declares the email channel without implementing {@see EmailNotification}
 * is a wiring bug: it fails loudly instead of being silently dropped, so the failure surfaces
 * through the normal Messenger retry/failure path.
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
            throw new \InvalidArgumentException(sprintf('The "%s" notification channel can only deliver notifications implementing %s, got "%s".', $this->name(), EmailNotification::class, $notification::class));
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
