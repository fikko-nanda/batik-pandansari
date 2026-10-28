<?php

namespace App\Mail\Concerns;

use App\Models\NotificationLog;
use Symfony\Component\Mime\Email;

/**
 * Menyisipkan id notification_logs ke header email sehingga event
 * MessageSending/MessageSent dapat memperbarui status pengiriman yang tepat.
 *
 * Header internal ini tidak berisi OTP maupun data sensitif.
 */
trait TracksNotificationLog
{
    public ?int $notificationLogId = null;

    public function withNotificationLog(?NotificationLog $log): static
    {
        $this->notificationLogId = $log?->id_notification_log;

        return $this;
    }

    protected function attachNotificationLogHeader(): void
    {
        if ($this->notificationLogId === null) {
            return;
        }

        $id = $this->notificationLogId;

        $this->withSymfonyMessage(function (Email $message) use ($id) {
            if (! $message->getHeaders()->has('X-Notification-Log-Id')) {
                $message->getHeaders()->addTextHeader(
                    'X-Notification-Log-Id',
                    (string) $id
                );
            }
        });
    }
}
