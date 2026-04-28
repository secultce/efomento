<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        .page {
            padding: 40px 50px;
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 16px;
        }

        .header .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #555;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 6px 0 0;
        }

        .meta {
            margin-bottom: 24px;
            font-size: 11px;
            color: #444;
        }

        .meta span {
            margin-right: 24px;
        }

        .content {
            line-height: 1.7;
        }

        .content p {
            margin: 0 0 10px;
        }

        .content ul,
        .content ol {
            margin: 0 0 10px;
            padding-left: 20px;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .content table td,
        .content table th {
            border: 1px solid #ccc;
            padding: 6px 8px;
        }

        .footer {
            margin-top: 48px;
            border-top: 1px solid #ccc;
            padding-top: 12px;
            font-size: 10px;
            color: #777;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="content">
            {!! $document->body !!}
        </div>

        <div class="footer">
            Gerado pelo e-Fomento em {{ now()->format('d/m/Y \à\s H:i') }}
        </div>
    </div>
</body>
</html>
