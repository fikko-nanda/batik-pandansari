Halo {{ $name ?: 'Pelanggan' }},

Kami menerima permintaan pemulihan kata sandi untuk akun Batik Pandansari kamu. Gunakan kode berikut untuk melanjutkan:

KODE PEMULIHAN: {{ $otp }}

Kode berlaku selama {{ $expiresInMinutes }} menit dan hanya dapat digunakan sekali.
@if (!empty($isResend))

Kode ini adalah kode baru yang menggantikan kode pemulihan sebelumnya.
@endif

Penting: jangan bagikan kode ini kepada siapa pun. Jika kamu tidak meminta pemulihan kata sandi, abaikan email ini dan kata sandi kamu tetap aman.

Salam hangat,
Tim Batik Pandansari
