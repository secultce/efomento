@extends('mail.layouts.default')

@section('title', 'Pagamento confirmado — e-fomento')

@section('content')
<p style="margin: 0 0 18px;">Olá, {{ $recipientName }}.</p>
<h1 style="margin: 0 0 12px; font-size: 22px; font-weight: bold; line-height: 1.4;">Pagamento de parcela confirmado</h1>
<p style="margin: 0 0 24px;">O pagamento da parcela do seu projeto foi confirmado.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 24px; background-color: #f0f8f3; border: 1px solid #c9e3d3; border-radius: 8px;">
    <tr>
        <td style="padding: 20px 24px; font-size: 15px; line-height: 1.7; word-break: break-word;">
            <p style="margin: 0 0 12px;"><strong>Número do processo:</strong> {{ $processNumber }}</p>
            <p style="margin: 0 0 12px;"><strong>Projeto:</strong> {{ $projectTitle }}</p>
            <p style="margin: 0 0 12px;"><strong>Parcela:</strong> {{ $installmentNumber }}</p>
            <p style="margin: 0 0 12px;"><strong>Data de pagamento:</strong> {{ $paymentDate }}</p>
            <p style="margin: 0; color: #008344; font-size: 18px;"><strong>Valor pago:</strong> R$ {{ $paymentAmount }}</p>
        </td>
    </tr>
</table>
<p style="margin: 0 0 12px;"><a href="https://suporte.secult.ce.gov.br/" style="color: #008344; text-decoration: underline;">Precisa de ajuda? Acesse o suporte.</a></p>
<p style="margin: 0;">Até mais!</p>
@endsection
