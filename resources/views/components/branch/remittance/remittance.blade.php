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
                            <li class="breadcrumb-item"><a href="{{ route('dashboard_branch') }}">Dashboard</a></li>
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
                                    
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Information Note -->
                                <div class="alert alert-info alert-dismissible fade show mb-4">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><i class="fa fa-info-circle"></i> Remittance Import Information</h5>
                                    <p class="mb-2"><strong>What this import does:</strong></p>
                                    <ul class="mb-2">
                                        <li><strong>Loan Prioritization:</strong> Processes loans based on product prioritization settings (lower numbers = higher priority)</li>
                                        <li><strong>Smart Allocation:</strong> Automatically allocates payments to highest priority loans first</li>
                                        <li><strong>Data Matching:</strong> Matches employee IDs with existing loan records in the system</li>
                                        <li><strong>Payment Processing:</strong> Handles both loan payments and savings contributions</li>
                                        <li><strong>Share Management:</strong> Processes share capital contributions separately</li>
                                    </ul>
                                    <p class="mb-0"><small><strong>Note:</strong> The system ensures payments are applied to the most important loans first based on your prioritization settings. All data and exports are limited to your branch.</small></p>
                                </div>

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
                                            <form action="{{ route('branch.remittance.upload') }}" method="POST"
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
                                                        <button type="button" class="btn btn-outline-info btn-sm" data-toggle="modal" data-target="#loansSavingsFormatModal">
                                                            <i class="fa fa-eye"></i> View Expected Format
                                                        </button>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-info btn-block">
                                                    <i class="fa fa-upload"></i> Upload and Process Loans & Savings Remittance
                                                </button>

                                                <a href="{{ route('branch.remittance.generateExport', ['type' => 'loans_savings']) }}" class="btn btn-primary btn-block">
                                                    Collection file for Loans & Savings
                                                </a>

                                            </form>
                                        </div>
                                    </div>

                                    <div class="col-12 mb-4">
                                        <div class="upload-section">
                                            <form action="{{ route('branch.remittance.upload.share') }}" method="POST" enctype="multipart/form-data">
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
                                                        <button type="button" class="btn btn-outline-info btn-sm" data-toggle="modal" data-target="#sharesFormatModal">
                                                            <i class="fa fa-eye"></i> View Expected Format
                                                        </button>
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-info btn-block">
                                                    <i class="fa fa-upload"></i> Upload and Process Share Remittance
                                                </button>

                                                <a href="{{ route('branch.remittance.generateExport', ['type' => 'shares']) }}" class="btn btn-primary btn-block">
                                                    Collection file for Shares
                                                </a>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        @if (isset($preview) && $preview)
                                            <div class="row mb-4">
                                                <div class="col-md-4">
                                                    <a href="{{ route('branch.remittance.index', ['filter' => 'matched']) }}"
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
                                                    <a href="{{ route('branch.remittance.index', ['filter' => 'unmatched']) }}"
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
                                                    <a href="{{ route('branch.remittance.index') }}"
                                                        class="btn {{ !request()->has('filter') ? 'btn-primary' : 'btn-outline-primary' }}">
                                                        All Records
                                                    </a>
                                                    <a href="{{ route('branch.remittance.index', ['filter' => 'matched']) }}"
                                                        class="btn {{ request()->get('filter') === 'matched' ? 'btn-success' : 'btn-outline-success' }}">
                                                        Matched Only
                                                    </a>
                                                    <a href="{{ route('branch.remittance.index', ['filter' => 'unmatched']) }}"
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
                                        @else
                                            <div class="text-center py-5">
                                                <i class="fa fa-upload fa-4x text-muted mb-3"></i>
                                                <h4 class="text-muted">Upload a remittance file to see preview</h4>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @if($preview && $preview->count() > 0)
                                    <div class="d-flex justify-content-center mt-4 text-center">
                                        {{ $preview->appends(request()->query())->links() }}
                                    </div>
                                @endif
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

    <!-- Loans & Savings Format Modal -->
    <div class="modal fade" id="loansSavingsFormatModal" tabindex="-1" role="dialog" aria-labelledby="loansSavingsFormatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loansSavingsFormatModalLabel">
                        <i class="fa fa-file-excel text-success"></i> Loans & Savings Remittance Format
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6><i class="fa fa-info-circle"></i> File Format Requirements:</h6>
                        <ul class="mb-0">
                            <li><strong>File Type:</strong> Excel (.xlsx, .xls) or CSV</li>
                            <li><strong>First Row:</strong> Must contain headers exactly as shown below</li>
                            <li><strong>Data Rows:</strong> Start from row 2 onwards</li>
                            <li><strong>Amounts:</strong> Use numbers only (no currency symbols)</li>
                            <li><strong>Branch Filtering:</strong> Only members from your branch will be processed</li>
                        </ul>
                    </div>

                    <h6 class="font-weight-bold mb-3">Required Headers (First Row):</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Header Name</th>
                                    <th>Description</th>
                                    <th>Required</th>
                                    <th>Example</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>EmpId</code></td>
                                    <td>Employee ID</td>
                                    <td><span class="badge badge-success">Yes</span></td>
                                    <td>EMP001</td>
                                </tr>
                                <tr>
                                    <td><code>Name</code></td>
                                    <td>Employee Full Name</td>
                                    <td><span class="badge badge-success">Yes</span></td>
                                    <td>John Doe</td>
                                </tr>
                                <tr>
                                    <td><code>Loans</code></td>
                                    <td>Total Loan Payment Amount</td>
                                    <td><span class="badge badge-success">Yes</span></td>
                                    <td>1500.00</td>
                                </tr>
                                <tr>
                                    <td><code>Regular Savings</code></td>
                                    <td>Regular Savings Amount</td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                    <td>500.00</td>
                                </tr>
                                <tr>
                                    <td><code>Savings 2</code></td>
                                    <td>Retirement Savings Amount</td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                    <td>300.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="font-weight-bold mb-3 mt-4">Sample Data:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>EmpId</th>
                                    <th>Name</th>
                                    <th>Loans</th>
                                    <th>Regular Savings</th>
                                    <th>Savings 2</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>EMP001</td>
                                    <td>John Doe</td>
                                    <td>1500.00</td>
                                    <td>500.00</td>
                                    <td>300.00</td>
                                </tr>
                                <tr>
                                    <td>EMP002</td>
                                    <td>Jane Smith</td>
                                    <td>2000.00</td>
                                    <td>750.00</td>
                                    <td>400.00</td>
                                </tr>
                                <tr>
                                    <td>EMP003</td>
                                    <td>Bob Johnson</td>
                                    <td>1200.00</td>
                                    <td>0</td>
                                    <td>250.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                </div>
            </div>
        </div>
    </div>

    <!-- Shares Format Modal -->
    <div class="modal fade" id="sharesFormatModal" tabindex="-1" role="dialog" aria-labelledby="sharesFormatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sharesFormatModalLabel">
                        <i class="fa fa-file-excel text-success"></i> Shares Remittance Format
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6><i class="fa fa-info-circle"></i> File Format Requirements:</h6>
                        <ul class="mb-0">
                            <li><strong>File Type:</strong> Excel (.xlsx, .xls) or CSV</li>
                            <li><strong>First Row:</strong> Must contain headers exactly as shown below</li>
                            <li><strong>Data Rows:</strong> Start from row 2 onwards</li>
                            <li><strong>Name Format:</strong> LASTNAME, FIRSTNAME (comma separated)</li>
                            <li><strong>Amounts:</strong> Use numbers only (no currency symbols)</li>
                            <li><strong>Branch Filtering:</strong> Only members from your branch will be processed</li>
                        </ul>
                    </div>

                    <h6 class="font-weight-bold mb-3">Required Headers (First Row):</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Header Name</th>
                                    <th>Description</th>
                                    <th>Required</th>
                                    <th>Example</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>EmpId</code></td>
                                    <td>Employee ID (can be empty)</td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                    <td>EMP001</td>
                                </tr>
                                <tr>
                                    <td><code>Name</code></td>
                                    <td>Name in format: LASTNAME, FIRSTNAME</td>
                                    <td><span class="badge badge-success">Yes</span></td>
                                    <td>DOE, JOHN</td>
                                </tr>
                                <tr>
                                    <td><code>Share</code></td>
                                    <td>Share Capital Amount</td>
                                    <td><span class="badge badge-success">Yes</span></td>
                                    <td>1000.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="font-weight-bold mb-3 mt-4">Sample Data:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>EmpId</th>
                                    <th>Name</th>
                                    <th>Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>EMP001</td>
                                    <td>DOE, JOHN</td>
                                    <td>1000.00</td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>SMITH, JANE</td>
                                    <td>1500.00</td>
                                </tr>
                                <tr>
                                    <td>EMP003</td>
                                    <td>JOHNSON, BOB</td>
                                    <td>750.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <h6><i class="fa fa-exclamation-triangle"></i> Important Notes:</h6>
                        <ul class="mb-0">
                            <li><strong>Name Format:</strong> Must be "LASTNAME, FIRSTNAME" (comma and space required)</li>
                            <li><strong>EmpId:</strong> Can be empty for non-employee members</li>
                            <li><strong>Share Amount:</strong> Must be a positive number</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);

            // Update file input label
            $('.custom-file-input').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });
        });

        function generateExport() {
            let url = '{{ route('branch.remittance.generateExport') }}';
            window.location.href = url;
        }
    </script>
</body>

</html>
