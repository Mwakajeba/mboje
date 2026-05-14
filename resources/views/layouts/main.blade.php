@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp

<!doctype html>
<html lang="{{ app()->getLocale() }}" class="color-sidebar sidebarcolor3">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta http-equiv="Permissions-Policy" content="camera=(self), microphone=(), geolocation=()">
    @stack('head-meta')

    <title>@yield('title', 'Connect – Dashboard')</title>

    <!--favicon-->
    <link rel="icon" href="{{ asset('assets/images/favicon-32x32.png') }}" type="image/png" />

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <!-- DataTables Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- DataTables Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

    <!-- Plugins CSS -->
    <link href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/highcharts/css/highcharts.css') }}" rel="stylesheet" />

    <!-- Loader CSS -->
    <link href="{{ asset('assets/css/pace.min.css') }}" rel="stylesheet" />

    <!-- Bootstrap & Theme CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap-extended.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/dark-theme.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/semi-dark.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/header-colors.css') }}" rel="stylesheet" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
        @stack('styles')
</head>

<body>
    <div class="wrapper">
        {{-- Include Navigation and Header --}}
        @include('incs.sideMenu')
        @include('incs.navBar')

        {{-- Main Content --}}
        @yield('content')
    </div>

    <!-- Scripts -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap Bundle -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        if (typeof window.bootstrap === 'undefined' && typeof bootstrap !== 'undefined') {
            window.bootstrap = bootstrap;
        }
    </script>

    <!-- Plugins -->
    <script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>

    <!-- Highcharts -->
    <script src="{{ asset('assets/plugins/highcharts/js/highcharts.js') }}"></script>
    <script src="{{ asset('assets/plugins/highcharts/js/highcharts-more.js') }}"></script>
    <script src="{{ asset('assets/plugins/highcharts/js/variable-pie.js') }}"></script>
    <script src="{{ asset('assets/plugins/highcharts/js/solid-gauge.js') }}"></script>
    <script src="{{ asset('assets/plugins/highcharts/js/highcharts-3d.js') }}"></script>
    <script src="{{ asset('assets/plugins/highcharts/js/cylinder.js') }}"></script>
    <script src="{{ asset('assets/plugins/highcharts/js/funnel3d.js') }}"></script>
    <script src="{{ asset('assets/plugins/highcharts/js/exporting.js') }}"></script>
    <script src="{{ asset('assets/plugins/highcharts/js/export-data.js') }}"></script>
    <script src="{{ asset('assets/plugins/highcharts/js/accessibility.js') }}"></script>

    <!-- Global Error Handlers -->
    <script nonce="{{ $cspNonce ?? '' }}">
        // Fix Highcharts error #13 globally - prevent errors when containers don't exist
        if (typeof Highcharts !== 'undefined') {
            Highcharts.error = function(code, stop) {
                if (code === 13) {
                    console.warn('Highcharts error #13: Container not found, skipping chart rendering');
                    return false; // Don't stop execution
                }
                console.error('Highcharts error #' + code);
            };

            // Override chart initialization to check for container existence
            var originalChart = Highcharts.chart;
            Highcharts.chart = function(container, options) {
                if (typeof container === 'string' && !document.getElementById(container)) {
                    console.warn('Highcharts: Container "' + container + '" not found, skipping chart initialization');
                    return null;
                }
                if (typeof container === 'string' && !document.querySelector('#' + container)) {
                    console.warn('Highcharts: Container "' + container + '" not found with querySelector, skipping chart initialization');
                    return null;
                }
                return originalChart.apply(this, arguments);
            };
        }
        
        // Fix DataTables column count issues globally
        $(document).ready(function() {
            // Override DataTables initialization to handle column count errors
            $.fn.dataTable.ext.errMode = 'throw';
            
            // Add error handler for DataTables
            $(document).on('error.dt', function(e, settings, techNote, message) {
                if (message && message.includes('column count')) {
                    console.warn('DataTables column count warning suppressed for table:', settings.nTable.id);
                    return false; // Prevent the error from being thrown
                }
            });
            
            // Global AJAX error handler for unhandled errors
            $(document).ajaxError(function(event, xhr, settings, thrownError) {
                // Only handle JSON responses with error messages
                if (xhr.responseJSON && xhr.responseJSON.message && !xhr.responseJSON.success) {
                    // Check if this error is already being handled by a specific error handler
                    if (!settings.errorHandled) {
                        Swal.fire({
                            title: 'Error',
                            text: xhr.responseJSON.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            });
        });
    </script>

    <script src="{{ asset('assets/js/index4.js') }}"></script>

    <!-- DataTables Core -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>

    <!-- JSZip and PDFMake for export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <!-- Export Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script nonce="{{ $cspNonce ?? '' }}">
        $(document).ready(function () {
            // Only initialize example tables if they exist on the page
            if ($('#example').length > 0) {
                $('#example').DataTable();
            }

            // Only initialize example2 table with buttons if it exists and buttons extension is available
            if ($('#example2').length > 0) {
                try {
                    var table2 = $('#example2').DataTable({
                        lengthChange: false,
                        buttons: ['copy', 'excel', 'pdf', 'print']
                    });

                    // Place buttons container in the DOM
                    table2.buttons().container()
                        .appendTo('#example2_wrapper .col-md-6:eq(0)');
                } catch (e) {
                    // Fallback if buttons extension is not available
                    $('#example2').DataTable({
                        lengthChange: false
                    });
                }
            }

            // Select2 init
            $('.select2-single').select2({
                placeholder: 'Select',
                allowClear: true,
                width: '100%',
                theme: 'bootstrap-5'
            });
        });
    </script>

    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const name = this.querySelector('button').getAttribute('data-name');

                    Swal.fire({
                        title: `Delete "${name}"?`,
                        text: "This action cannot be undone!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.submit();
                        }
                    });
                });
            });
        });
    </script>

    @if(session('success'))
        <script nonce="{{ $cspNonce ?? '' }}">
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000
            });
        </script>
    @endif

    @if(session('error'))
        <script nonce="{{ $cspNonce ?? '' }}">
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: '{{ session('error') }}',
                showConfirmButton: false,
                timer: 4000
            });
        </script>
    @endif

    @if(session('info'))
        <script nonce="{{ $cspNonce ?? '' }}">
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: '{{ session('info') }}',
                showConfirmButton: false,
                timer: 3000
            });
        </script>
    @endif

    <!-- Prevent double submission globally and show processing text on buttons -->
    <script nonce="{{ $cspNonce ?? '' }}">
        (function() {
            function setButtonProcessingText(button) {
                const originalHtml = button.getAttribute('data-original-html') || button.innerHTML;
                button.setAttribute('data-original-html', originalHtml);
                const processingText = button.getAttribute('data-processing-text') || 'Creating...';
                button.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${processingText}`;
            }

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('form').forEach(function(form) {
                    // Skip forms explicitly opting out
                    if (form.classList.contains('no-global-submit-guard')) return;

                    form.addEventListener('submit', function (e) {
                        // If already submitted, block
                        if (form.getAttribute('data-submitted') === 'true') {
                            e.preventDefault();
                            return false;
                        }

                        // Mark as submitted
                        form.setAttribute('data-submitted', 'true');

                        // Disable all submit buttons and show processing text
                        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                        submitButtons.forEach(function(btn) {
                            if (btn.disabled) return;
                            btn.disabled = true;
                            // Set context-aware text if provided via data attribute
                            setButtonProcessingText(btn);
                        });
                    }, { capture: true });
                });
            });
        })();
    </script>

    <script nonce="{{ $cspNonce ?? '' }}">
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function () {
                const output = document.getElementById('preview');
                output.innerHTML = `<img src="${reader.result}" width="100">`;
            }
            reader.readAsDataURL(event.target.files[0]);
        }

        document.getElementById('region')?.addEventListener('change', function () {
            const region = this.value;
            fetch(`/get-districts/${region}`)
                .then(res => res.json())
                .then(data => {
                    let options = `<option value="">Select District</option>`;
                    data.forEach(district => {
                        options += `<option value="${district.name}">${district.name}</option>`;
                    });
                    document.getElementById('district').innerHTML = options;
                });
        });
    </script>

    <!-- App JS -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
        @stack('scripts')
</body>

</html>
