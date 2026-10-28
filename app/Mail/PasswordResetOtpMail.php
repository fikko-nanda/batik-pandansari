<?php

namespace App\Mail;

use App\Mail\Concerns\TracksNotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, TracksNotificationLog;

    public int $tries = 10;

    /** @var array<int, int> */
    public array $backoff = [2, 5, 10, 20, 30];

    public function __construct(
        public string $otp,
        public string $name = '',
        public bool $isResend = false
    ) {}

    public function build(): self
    {
        $this->attachNotificationLogHeader();

        $subject = $this->isResend ? 'Kode Pemulihan Password Ulang - Batik Pandansari' : 'Kode Pemulihan Password - Batik Pandansari';

        $data = [
            'otp' => $this->otp,
            'name' => $this->name,
            'isResend' => $this->isResend,
            'expiresInMinutes' => 5,
        ];

        return $this->subject($subject)
            ->view('emails.password-reset-otp', $data)
            ->text('emails.text.password-reset-otp', $data);
    }
}
