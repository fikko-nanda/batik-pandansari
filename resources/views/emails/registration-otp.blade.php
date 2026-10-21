<x-mail::message>
# Verifikasi Email Kamu - Batik Pandansari

Halo {{ $name ?: "Pelanggan" }},

Terima kasih telah mendaftar di **Batik Pandansari**.

Gunakan kode OTP berikut untuk menyelesaikan proses verifikasi registrasi akun kamu:

<x-mail::panel>
**{{ $otp }}**
</x-mail::panel>

Kode ini berlaku selama **{{ $expiresInMinutes }} menit** dan hanya dapat digunakan satu kali.

@isset($isResend)
@if($isResend)
> Kode ini adalah kode baru yang menggantikan kode verifikasi sebelumnya.
@endif
@endisset

**Penting:** Jangan bagikan kode ini kepada siapa pun. Jika kamu tidak melakukan permintaan ini, abaikan email ini.

Salam hangat,
**Tim Batik Pandansari**
</x-mail::message>

