<?php

declare(strict_types=1);

namespace App\Notification\Domain;

/**
 * A notification that can be delivered by the email channel: it provides the templated,
 * localisable email content (CE-027) without knowing how it is sent.
 */
interface EmailNotification extends Notification
{
    public function subject(): string;

    public function htmlTemplate(): string;

    public function textTemplate(): string;

    public function locale(): string;

    /**
     * @return array<string, mixed>
     */
    public function context(): array;
}
