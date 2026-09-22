@extends('mail.layouts.default')

@section('title', 'Seu código de acesso ao e-fomento')

@section('content')
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
@endsection
