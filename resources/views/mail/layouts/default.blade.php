<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: Arial, Helvetica, sans-serif; color: #2d353f;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 800px; background-color: #ffffff; border-radius: 14px; overflow: hidden;">
                    <tr>
                        <td>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" aria-hidden="true">
                                <tr>
                                    <td width="25%" height="8" bgcolor="#28b099" style="font-size: 0; line-height: 0;">&nbsp;</td>
                                    <td width="25%" height="8" bgcolor="#c4d833" style="font-size: 0; line-height: 0;">&nbsp;</td>
                                    <td width="25%" height="8" bgcolor="#ffcc00" style="font-size: 0; line-height: 0;">&nbsp;</td>
                                    <td width="25%" height="8" bgcolor="#ef4b0b" style="font-size: 0; line-height: 0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 42px 24px 48px;">
                            <a href="{{ config('app.url') }}" style="color: #2d353f; font-size: 48px; font-weight: bold; letter-spacing: -2px; line-height: 1.2; text-decoration: none;">e-fomento</a>
                            <p style="margin: 12px 0 0; color: #69736e; font-size: 14px; line-height: 1.5;">Secretaria da Cultura do Ceará</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 28px 36px; font-size: 15px; line-height: 1.7;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td align="center" bgcolor="#008344" style="padding: 22px 24px; border-radius: 0 0 14px 14px; color: #ffffff; font-size: 14px; line-height: 1.6;">
                            <p style="margin: 0 0 4px; font-size: 16px; font-weight: bold;">e-fomento</p>
                            <a href="{{ config('app.url') }}" style="color: #ffffff; text-decoration: none; word-break: break-word;">{{ config('app.url') }}</a>
                            <img src="{{ $message->embed(public_path('images/logos/ceara-white.png')) }}" width="170" alt="Ceará — Governo do Estado — Secretaria da Cultura" style="display: block; width: 170px; max-width: 100%; height: auto; margin: 14px auto 0; border: 0;">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
