{{--
    Layout email transaksional Batik Pandansari.
    Palet: Navy #142B4A, Ivory #FFFCF5, Gold #C7A96B.
    HTML inline sederhana agar aman di mayoritas klien email (Gmail, Outlook).
--}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $judul ?? 'Batik Pandansari' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#FFFCF5; font-family:Arial,Helvetica,sans-serif; color:#142B4A;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FFFCF5; padding:24px 12px;">
<tr>
<td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:100%; background-color:#ffffff; border:1px solid #e7dfcd; border-radius:10px; overflow:hidden;">
    <tr>
        <td style="background-color:#142B4A; padding:20px 28px;">
            <div style="font-size:20px; font-weight:bold; color:#FFFCF5; letter-spacing:0.5px;">Batik Pandansari</div>
            <div style="font-size:12px; color:#C7A96B; margin-top:3px;">Warisan Batik, Keindahan Abadi</div>
        </td>
    </tr>
    <tr>
        <td style="padding:28px;">
            @yield('content')
        </td>
    </tr>
    <tr>
        <td style="background-color:#FFFCF5; border-top:1px solid #e7dfcd; padding:18px 28px;">
            <div style="font-size:11px; color:#6b7280; line-height:1.6;">
                Email ini dikirim otomatis oleh sistem Batik Pandansari.<br>
                Jangan balas email ini. Jika butuh bantuan, hubungi kami melalui kanal resmi yang tertera di situs.
            </div>
        </td>
    </tr>
</table>
<div style="font-size:10px; color:#9ca3af; margin-top:14px;">
    &copy; {{ date('Y') }} Batik Pandansari. Seluruh hak cipta dilindungi.
</div>
</td>
</tr>
</table>
</body>
</html>
