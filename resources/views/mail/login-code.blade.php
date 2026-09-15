<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seu código de acesso ao e-fomento</title>
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
                            <p style="margin: 0 0 18px;">Olá!</p>
                            <h1 style="margin: 0 0 12px; font-size: 22px; font-weight: bold; line-height: 1.4;">Confirme seu acesso ao e-fomento</h1>
                            <p style="margin: 0 0 24px;">Para concluir seu acesso, digite o código abaixo na tela de verificação:</p>
                            <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin: 0 auto 24px;">
                                <tr>
                                    <td align="center" style="padding: 16px 24px; background-color: #f0f8f3; border: 1px solid #c9e3d3; border-radius: 8px; color: #008344; font-family: 'Courier New', monospace; font-size: 36px; font-weight: bold; letter-spacing: 6px; line-height: 1.3; white-space: nowrap;">{{ $code }}</td>
                                </tr>
                            </table>
                            <p style="margin: 0 0 24px; text-align: center; color: #59665e; font-size: 14px;">O código expira em <strong>{{ $ttlMinutes }} minutos</strong> e só pode ser usado uma vez.</p>
                            <p style="margin: 0 0 24px;">Não compartilhe este código. Se você não tentou entrar, altere sua senha ou entre em contato com o suporte.</p>
                            <p style="margin: 0 0 12px;"><a href="https://suporte.secult.ce.gov.br/" style="color: #008344; text-decoration: underline;">Precisa de ajuda? Acesse o suporte.</a></p>
                            <p style="margin: 0;">Até mais!</p>
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
