Halo {{ $name ?: 'Pelanggan' }},

Terima kasih telah mendaftar di Batik Pandansari. Gunakan kode berikut untuk menyelesaikan verifikasi akun kamu:

KODE VERIFIKASI: {{ $otp }}

Kode berlaku selama {{ $expiresInMinutes }} menit dan hanya dapat digunakan sekali.
@if (!empty($isResend))

Kode ini adalah kode baru yang menggantikan kode verifikasi sebelumnya.
@endif

Penting: jangan bagikan kode ini kepada siapa pun. Jika kamu tidak melakukan pendaftaran, abaikan email ini.

Salam hangat,
Tim Batik Pandansari
