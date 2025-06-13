<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Remittance Upload - Billing and Collection</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">
    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <style>
        .stats-card {
            transition: transform 0.2s;
            cursor: pointer;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .preview-table th {
            background-color: #f3f6f9;
        }

        .upload-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>
    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>

    <div id="main-wrapper">
        @include('layouts.partials.header')
        @include('layouts.partials.sidebar')

        <div class="content-body">
            <div class="container-fluid">
                <div class="row page-titles mx-0 mb-3">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4>Remittance Upload</h4>
                            <span class="ml-1">Upload and Process Remittance Data</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Remittance Upload</li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center title-container">
                                    <h4 class="card-title mb-0">Upload Remittance Excel File</h4>
                                </div>
                                <div class="d-flex align-items-center ms-3">
                                    <button onclick="generateExport()" class="btn btn-success">
                                        <i class="fa fa-file-excel"></i> Export
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                @if (session('success'))
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <strong><i class="fa fa-check-circle"></i> Success!</strong>
                                        {{ session('success') }}
                                    </div>
                                @endif

                                @if (session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <strong><i class="fa fa-exclamation-circle"></i> Error!</strong>
                                        {{ session('error') }}
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-12 mb-4">
                                        <div class="upload-section">
                                            <form action="{{ route('remittance.upload') }}" method="POST"
                                                enctype="multipart/form-data">
                                                @csrf
                                                <div class="form-group">
                                                    <label class="font-weight-bold">Select Excel File</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" name="file"
                                                            id="file" accept=".xlsx,.xls,.csv" required>
                                                        <label class="custom-file-label" for="file">Choose
                                                            file</label>
                                                    </div>
                                                    <div class="mt-3">
                                                        <h6 class="text-muted mb-2">File Requirements:</h6>
                                                        <ul class="text-muted small pl-3">
                                                            <li>Excel format (.xlsx, .xls, .csv,)</li>
                                                            <li>Required headers:
                                                                <ul class="pl-3">
                                                                    <li>EmpId</li>
                                                                    <li>Name</li>
                                                                    <li>Loans</li>
                                                                    <li>Savings Product Names</li>
                                                                </ul>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-block">
                                                    <i class="fa fa-upload"></i> Upload and Process
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="col-12 mb-4">
                                        <div class="upload-section">
                                            <form action="{{ route('remittance.upload.share') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="form-group">
                                                    <label class="font-weight-bold">Upload Share Remittance</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" name="file" id="shareFile" accept=".xlsx,.xls,.csv" required>
                                                        <label class="custom-file-label" for="shareFile">Choose file</label>
                                                    </div>
                                                    <div class="mt-3">
                                                        <h6 class="text-muted mb-2">File Requirements:</h6>
                                                        <ul class="text-muted small pl-3">
                                                            <li>Excel format (.xlsx, .xls, .csv)</li>
                                                            <li>Required headers:
                                                                <ul class="pl-3">
                                                                    <li>EmpId (can be null)</li>
                                                                    <li>Name (format: LASTNAME, FIRSTNAME)</li>
                                                                    <li>Share (amount)</li>
                                                                </ul>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-info btn-block">
                                                    <i class="fa fa-upload"></i> Upload and Process Share Remittance
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        @if (isset($preview) && $preview)
                                            <div class="row mb-4">
                                                <div class="col-md-4">
                                                    <a href="{{ route('remittance.index', ['filter' => 'matched']) }}"
                                                        class="text-decoration-none">
                                                        <div class="card stats-card bg-success-light">
                                                            <div class="card-body">
                                                                <div class="media align-items-center">
                                                                    <div class="media-body mr-3">
                                                                        <h2 class="text-success">{{ $stats['matched'] ?? 0 }}</h2>
                                                                        <span class="text-success">Matched Records</span>
                                                                    </div>
                                                                    <i class="fa fa-check-circle fa-3x text-success"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </div>
                                                <div class="col-md-4">
                                                    <a href="{{ route('remittance.index', ['filter' => 'unmatched']) }}"
                                                        class="text-decoration-none">
                                                        <div class="card stats-card bg-danger-light">
                                                            <div class="card-body">
                                                                <div class="media align-items-center">
                                                                    <div class="media-body mr-3">
                                                                        <h2 class="text-danger">{{ $stats['unmatched'] ?? 0 }}</h2>
                                                                        <span class="text-danger">Unmatched Records</span>
                                                                    </div>
                                                                    <i class="fa fa-exclamation-circle fa-3x text-danger"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="card stats-card bg-info-light">
                                                        <div class="card-body">
                                                            <div class="media align-items-center">
                                                                <div class="media-body mr-3">
                                                                    <h2 class="text-info">₱{{ number_format($stats['total_amount'] ?? 0, 2) }}</h2>
                                                                    <span class="text-info">Total Amount</span>
                                                                </div>
                                                                <i class="fa fa-money-bill fa-3x text-info"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <a href="{{ route('remittance.index') }}"
                                                        class="btn {{ !request()->has('filter') ? 'btn-primary' : 'btn-outline-primary' }}">
                                                        All Records
                                                    </a>
                                                    <a href="{{ route('remittance.index', ['filter' => 'matched']) }}"
                                                        class="btn {{ request()->get('filter') === 'matched' ? 'btn-success' : 'btn-outline-success' }}">
                                                        Matched Only
                                                    </a>
                                                    <a href="{{ route('remittance.index', ['filter' => 'unmatched']) }}"
                                                        class="btn {{ request()->get('filter') === 'unmatched' ? 'btn-danger' : 'btn-outline-danger' }}">
                                                        Unmatched Only
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-striped table-bordered preview-table">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 90px;">Status</th>
                                                            <th>EmpId</th>
                                                            <th>Name</th>
                                                            <th class="text-right">Loans</th>
                                                            @if(isset($preview[0]['savings']) && is_array($preview[0]['savings']))
                                                                @foreach (array_keys($preview[0]['savings']) as $productName)
                                                                    <th class="text-right">{{ $productName }}</th>
                                                                @endforeach
                                                            @endif
                                                            <th>Message</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($preview as $row)
                                                            <tr>
                                                                <td>
                                                                    @if ($row['status'] === 'success')
                                                                        <span class="badge badge-success">
                                                                            <i class="fa fa-check"></i> Matched
                                                                        </span>
                                                                    @else
                                                                        <span class="badge badge-danger">
                                                                            <i class="fa fa-times"></i> Unmatched
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $row['emp_id'] }}</td>
                                                                <td>{{ $row['name'] }}</td>
                                                                <td class="text-right">
                                                                    ₱{{ number_format($row['loans'] ?? 0, 2) }}
                                                                </td>
                                                                @if(isset($row['savings']) && is_array($row['savings']))
                                                                    @foreach ($row['savings'] as $amount)
                                                                        <td class="text-right">
                                                                            ₱{{ number_format($amount ?? 0, 2) }}
                                                                        </td>
                                                                    @endforeach
                                                                @endif
                                                                <td>
                                                                    @if ($row['status'] !== 'success')
                                                                        <i class="fa fa-exclamation-circle text-danger"></i>
                                                                    @endif
                                                                    {{ $row['message'] }}
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="6" class="text-center">
                                                                    <div class="py-4">
                                                                        <i class="fa fa-info-circle fa-2x text-muted mb-2"></i>
                                                                        <p class="text-muted">No records found.</p>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>

                                            @if(isset($preview) && $preview && method_exists($preview, 'appends'))
                                                <div class="d-flex justify-content-center mt-4 text-center">
                                                    {{ $preview->appends(request()->query())->links() }}
                                                </div>
                                            @endif
                                        @else
                                            <div class="text-center py-5">
                                                <i class="fa fa-upload fa-4x text-muted mb-3"></i>
                                                <h4 class="text-muted">Upload a remittance file to see preview</h4>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .flex.justify-between.flex-1.sm\:hidden {
                display: none;
            }
        </style>

        <div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/"
                        target="_blank">MASS-SPECC COOPERATIVE</a>2025</p>
            </div>
        </div>
    </div>

    @include('layouts.partials.footer')

    <script>
        // Update custom file input label
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });

        // Initialize DataTable if preview exists
        $(document).ready(function() {
            if ($('.table').length) {
                $('.table').DataTable({
                    pageLength: 25,
                    ordering: true,
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                        '<"row"<"col-sm-12"tr>>' +
                        '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    buttons: ['copy', 'excel', 'pdf', 'print']
                });
            }

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        });

        function generateExport() {
            let url = '{{ route('remittance.generateExport') }}';
            window.location.href = url;
        }
    </script>
</body>

</html>
