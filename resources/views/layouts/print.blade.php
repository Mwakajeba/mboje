<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Print')</title>

    <!-- Bootstrap CSS for print -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom print styles -->
    <style nonce="{{ $cspNonce ?? '' }}">
        @media print {
            body {
                font-size: 12px;
                line-height: 1.4;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-before: always;
            }

            .print-header, .print-footer {
                position: fixed;
                left: 0;
                right: 0;
                background: white;
            }

            .print-header {
                top: 0;
                border-bottom: 1px solid #ccc;
                padding: 10px 20px;
            }

            .print-footer {
                bottom: 0;
                border-top: 1px solid #ccc;
                padding: 10px 20px;
            }

            .print-content {
                margin-top: 60px;
                margin-bottom: 60px;
            }
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
        }

        .print-container {
            max-width: 100%;
        }
    </style>

    @stack('styles')
</head>

<body>
    @yield('content')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>