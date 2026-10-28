@extends('emails.layouts.brand', ['judul' => 'Verifikasi Email - Batik Pandansari'])

@section('preheader', 'Kode verifikasi akun Batik Pandansari kamu sudah siap.')

@section('content')
<h1 style="margin:0 0 16px 0; font-size:20px; line-height:1.4; color:#142B4A;">
    Verifikasi email kamu
</h1>

<p style="margin:0 0 12px 0; font-size:14px; line-height:1.75; color:#374151;">
    Halo <strong style="color:#142B4A;">{{ $name ?: 'Pelanggan' }}</strong>,
</p>
<p style="margin:0 0 18px 0; font-size:14px; line-height:1.75; color:#374151;">
    Terima kasih telah mendaftar di Batik Pandansari. Gunakan kode berikut untuk
    menyelesaikan verifikasi akun kamu.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px 0;">
    <tr>
        <td align="center" style="background-color:#EEF3F9; border:1px solid #C3D3E4; border-radius:8px; padding:22px 16px;">
            <div style="font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#4B6B8F; margin-bottom:8px;">
                Kode verifikasi
            </div>
            <div style="font-size:36px; line-height:1.2; font-weight:bold; letter-spacing:10px; color:#142B4A; padding-left:10px;">
                {{ $otp }}
            </div>
            <div style="font-size:12px; color:#5B7391; margin-top:10px;">
                Berlaku selama {{ $expiresInMinutes }} menit dan hanya dapat digunakan sekali.
            </div>
        </td>
    </tr>
</table>

@if (!empty($isResend))
<p style="margin:0 0 16px 0; font-size:13px; line-height:1.7; color:#6B7280;">
    Kode ini adalah kode baru yang menggantikan kode verifikasi sebelumnya.
</p>
@endif

<p style="margin:0 0 18px 0; font-size:13px; line-height:1.7; color:#374151;">
    <strong style="color:#142B4A;">Penting:</strong> jangan bagikan kode ini kepada siapa pun.
    Jika kamu tidak melakukan pendaftaran, abaikan email ini.
</p>

<p style="margin:0; font-size:14px; line-height:1.75; color:#374151;">
    Salam hangat,<br>
    <strong style="color:#142B4A;">Tim Batik Pandansari</strong>
</p>
@endsection
