<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 120px 50px 100px 50px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        header {
            position: fixed;
            top: -95px;
            left: 0px;
            right: 0px;
            height: 80px;
        }

        footer {
            position: fixed;
            bottom: -75px;
            left: 0px;
            right: 0px;
            height: 80px;
        }

        .image-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .image-table td {
            border: none !important;
            padding: 0;
            width: 33.33%;
            vertical-align: middle;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .doc-image {
            max-width: 170px;
            max-height: 80px;
            object-fit: contain;
        }

        .content {
            line-height: 1.7;
            text-align: justify;
        }

        .content p {
            margin: 0 0 12px;
        }

        .content ul,
        .content ol {
            margin: 0 0 12px;
            padding-left: 20px;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .content table td,
        .content table th {
            border: 1px solid #ccc;
            padding: 6px 8px;
        }
    </style>
</head>

<body>

    <header>
        @if($document->images->where('section.value', 'header')->count() > 0)
        <table class="image-table">
            <tr>
                <td class="text-left">
                    @if($img = $document->images->where('section.value', 'header')->where('position.value', 'left')->first())
                    <img src="{{ public_path('storage/' . $img->path) }}" class="doc-image">
                    @endif
                </td>
                <td class="text-center">
                    @if($img = $document->images->where('section.value', 'header')->where('position.value', 'center')->first())
                    <img src="{{ public_path('storage/' . $img->path) }}" class="doc-image">
                    @endif
                </td>
                <td class="text-right">
                    @if($img = $document->images->where('section.value', 'header')->where('position.value', 'right')->first())
                    <img src="{{ public_path('storage/' . $img->path) }}" class="doc-image">
                    @endif
                </td>
            </tr>
        </table>
        @endif
    </header>

    <footer>
        @if($document->images->where('section.value', 'footer')->count() > 0)
        <table class="image-table">
            <tr>
                <td class="text-left">
                    @if($img = $document->images->where('section.value', 'footer')->where('position.value', 'left')->first())
                    <img src="{{ public_path('storage/' . $img->path) }}" class="doc-image">
                    @endif
                </td>
                <td class="text-center">
                    @if($img = $document->images->where('section.value', 'footer')->where('position.value', 'center')->first())
                    <img src="{{ public_path('storage/' . $img->path) }}" class="doc-image">
                    @endif
                </td>
                <td class="text-right">
                    @if($img = $document->images->where('section.value', 'footer')->where('position.value', 'right')->first())
                    <img src="{{ public_path('storage/' . $img->path) }}" class="doc-image">
                    @endif
                </td>
            </tr>
        </table>
        @endif
    </footer>

    <div class="content">
        {!! $document->body !!}
    </div>

</body>

</html>