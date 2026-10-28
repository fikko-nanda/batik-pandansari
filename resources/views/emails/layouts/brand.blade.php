{{--
    Layout email transaksional Batik Pandansari.
    Identitas biru navy, tanpa gambar/ornamen agar ramah filter spam
    dan tetap tampil rapi di Gmail, Outlook, dan klien email lain.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<title>{{ $judul ?? 'Batik Pandansari' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#F4F6F8; font-family:Arial,Helvetica,sans-serif; color:#1F2937;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent; mso-hide:all;">
    @yield('preheader', 'Batik Pandansari')
</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F4F6F8; padding:24px 12px;">
<tr>
<td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:100%; background-color:#ffffff; border:1px solid #E5E7EB; border-radius:8px;">
    <tr>
        <td style="background-color:#142B4A; padding:22px 28px;">
            <div style="font-size:20px; font-weight:bold; color:#FFFFFF;">Batik Pandansari</div>
            <div style="font-size:12px; color:#B9CBE0; margin-top:4px;">Warisan Batik, Keindahan Abadi</div>
        </td>
    </tr>
    <tr>
        <td style="padding:28px;">
            @yield('content')
        </td>
    </tr>
    <tr>
        <td style="background-color:#F1F5FA; border-top:1px solid #D6E0EA; padding:16px 28px;">
            <div style="font-size:11px; color:#5B7391; line-height:1.7;">
                Email ini dikirim otomatis oleh sistem <strong style="color:#142B4A;">Batik Pandansari</strong>.<br>
                Jangan balas email ini. Jika butuh bantuan, hubungi kami melalui kanal resmi yang tertera di situs.
            </div>
        </td>
    </tr>
</table>
<div style="font-size:10px; color:#9CA3AF; margin-top:12px;">
    &copy; {{ date('Y') }} Batik Pandansari. Seluruh hak cipta dilindungi.
</div>
</td>
</tr>
</table>
</body>
</html>
