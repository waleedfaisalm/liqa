<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#0b0816;font-family:Tahoma,Arial,sans-serif;direction:rtl;text-align:right;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0b0816;padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#16122b;border:1px solid #2d2650;border-radius:16px;">
                <tr>
                    <td style="padding:28px 28px 8px;font-size:26px;font-weight:bold;color:#ffffff;">لقاء</td>
                </tr>
                <tr>
                    <td style="padding:8px 28px 0;font-size:20px;font-weight:bold;color:#ffffff;">{{ $heading }}</td>
                </tr>
                <tr>
                    <td style="padding:12px 28px 0;font-size:15px;line-height:1.9;color:#cbd5e1;">
                        @foreach ($lines as $line)
                            <p style="margin:0 0 10px;">{{ $line }}</p>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 28px;">
                        <a href="{{ $url }}" style="display:inline-block;background:#9333ea;background-image:linear-gradient(90deg,#9333ea,#db2777);color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:12px 28px;border-radius:10px;">{{ $button }}</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 8px;font-size:13px;line-height:1.8;color:#94a3b8;">{{ $footnote }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 28px 24px;font-size:12px;line-height:1.7;color:#64748b;word-break:break-all;" dir="ltr">
                        {{ $url }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
