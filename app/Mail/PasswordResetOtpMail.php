<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 10;

    /** @var array<int, int> */
    public array $backoff = [2, 5, 10, 20, 30];

    /**
     * Batasi laju pengiriman email agar tidak kena throttle penyedia SMTP.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('email')];
    }

    public function __construct(
        public string $otp,
        public string $name = '',
        public bool $isResend = false
    ) {}

    public function build(): self
    {
        $subject = $this->isResend ? 'Kode Pemulihan Password Ulang � Batik Pandansari' : 'Kode Pemulihan Password � Batik Pandansari';

        return $this->subject($subject)
            ->markdown('emails.password-reset-otp', [
                'otp' => $this->otp,
                'name' => $this->name,
                'isResend' => $this->isResend,
                'expiresInMinutes' => 5,
            ]);
    }
}
