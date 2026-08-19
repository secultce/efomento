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
            left: 0;
            right: 0;
            height: 80px;
        }

        footer {
            position: fixed;
            bottom: -100px;
            left: 0;
            right: 0;
            height: 100px;
        }

        .full-width-header,
        .full-width-footer {
            position: relative;
            left: -50px;
            width: calc(100% + 100px);
            height: 100px;
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

        .doc-image-full {
            display: block;
            width: 100%;
            max-height: 120px;
            object-fit: contain;
        }

        .footer-image-full {
            display: block;
            width: 100%;
            max-height: 100px;
            object-fit: contain;
        }

        .content {
            line-height: 1.7;
            text-align: justify;
            overflow-wrap: anywhere;
            word-wrap: break-word;
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
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .content table td,
        .content table th {
            border: 1px solid #ccc;
            padding: 6px 8px;
            white-space: normal;
            overflow-wrap: anywhere;
            word-wrap: break-word;
            vertical-align: top;
        }

        .content table th {
            background: #e6f1e3;
            font-weight: bold;
        }

        .content img {
            max-width: 100%;
            height: auto;
        }

        .content pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>

<body>

    <header>
        @php
        $headerImages = $document->images->filter(
        fn ($img) => $img->section?->value === 'header'
        );

        $headerFull = $headerImages->first(
        fn ($img) => $img->is_full_width
        );
        @endphp

        @if($headerFull)
        <div class="full-width-header">
            <img
                src="{{ public_path('storage/' . $headerFull->path) }}"
                class="doc-image-full">
        </div>
        @elseif($headerImages->count() > 0)
        <table class="image-table">
            <tr>
                <td class="text-left">
                    @if($img = $headerImages->first(fn ($i) => $i->position?->value === 'left'))
                    <img
                        src="{{ public_path('storage/' . $img->path) }}"
                        class="doc-image">
                    @endif
                </td>

                <td class="text-center">
                    @if($img = $headerImages->first(fn ($i) => $i->position?->value === 'center'))
                    <img
                        src="{{ public_path('storage/' . $img->path) }}"
                        class="doc-image">
                    @endif
                </td>

                <td class="text-right">
                    @if($img = $headerImages->first(fn ($i) => $i->position?->value === 'right'))
                    <img
                        src="{{ public_path('storage/' . $img->path) }}"
                        class="doc-image">
                    @endif
                </td>
            </tr>
        </table>
        @endif
    </header>

    <footer>
        @php
        $footerImages = $document->images->filter(
        fn ($img) => $img->section?->value === 'footer'
        );

        $footerFull = $footerImages->first(
        fn ($img) => $img->is_full_width
        );
        @endphp

        @if($footerFull)
        <div class="full-width-footer">
            <img
                src="{{ public_path('storage/' . $footerFull->path) }}"
                class="footer-image-full">
        </div>
        @elseif($footerImages->count() > 0)
        <table class="image-table">
            <tr>
                <td class="text-left">
                    @if($img = $footerImages->first(fn ($i) => $i->position?->value === 'left'))
                    <img
                        src="{{ public_path('storage/' . $img->path) }}"
                        class="doc-image">
                    @endif
                </td>

                <td class="text-center">
                    @if($img = $footerImages->first(fn ($i) => $i->position?->value === 'center'))
                    <img
                        src="{{ public_path('storage/' . $img->path) }}"
                        class="doc-image">
                    @endif
                </td>

                <td class="text-right">
                    @if($img = $footerImages->first(fn ($i) => $i->position?->value === 'right'))
                    <img
                        src="{{ public_path('storage/' . $img->path) }}"
                        class="doc-image">
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
