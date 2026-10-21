<x-mail::message>
# Kode Pemulihan Password - Batik Pandansari

Halo {{ $name ?: "Pelanggan" }},

Kami menerima permintaan pemulihan kata sandi untuk akun Batik Pandansari kamu.

Gunakan kode OTP berikut untuk melanjutkan proses pemulihan password:

<x-mail::panel>
**{{ $otp }}**
</x-mail::panel>

Kode ini berlaku selama **{{ $expiresInMinutes }} menit** dan hanya dapat digunakan satu kali.

@isset($isResend)
@if($isResend)
> Kode ini adalah kode baru yang menggantikan kode OTP pemulihan password sebelumnya.
@endif
@endisset

**Penting:** Jangan bagikan kode ini kepada siapa pun. Jika kamu tidak melakukan permintaan ini, abaikan email ini.

Salam hangat,
**Tim Batik Pandansari**
</x-mail::message>

