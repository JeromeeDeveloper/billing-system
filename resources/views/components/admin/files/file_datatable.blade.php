<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Billing and Collection</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">

    <link href="./vendor/datatables/css/jquery.dataTables.min.css" rel="stylesheet">

    <link href="./css/style.css" rel="stylesheet">

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
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4>File Uploads</h4>
                            <span class="ml-1">Datatable</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active"><a href="{{ route('billing') }}">Billing</a></li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="card-title mb-0">File Datatable</h4>
                                    <small class="text-muted">Showing latest 5 files total for current billing period</small>
                                </div>
                                <div class="d-flex align-items-center" style="gap: 10px;">
                                    <a href="{{ route('file.retention.dashboard') }}" class="btn btn-rounded btn-info">
                                        <span class="btn-icon-left text-info">
                                            <i class="fa fa-cogs"></i>
                                        </span>
                                        File Retention
                                    </a>
                                    @if($isApproved)
                                        <button type="button" class="btn btn-rounded btn-primary" data-toggle="modal"
                                            data-target="#exampleModalpopover">
                                            <span class="btn-icon-left text-primary">
                                                <i class="fa fa-upload"></i>
                                            </span>
                                            Upload
                                        </button>
                                                                                                                                                @else
                                        <button type="button" class="btn btn-rounded btn-secondary" disabled
                                            title="Upload is disabled. One or more branch users have been approved.">
                                            <span class="btn-icon-left text-secondary">
                                                <i class="fa fa-upload"></i>
                                            </span>
                                            Upload (Disabled)
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="modal fade" id="exampleModalpopover" tabindex="-1" role="dialog"
                                aria-labelledby="exampleModalpopoverLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <form id="uploadForm" action="{{ route('document.upload') }}" method="POST"
                                            enctype="multipart/form-data">
                                            @csrf

                                            <div class="modal-body">

                                                @php
                                                    $billingPeriod = auth()->user()->billing_period
                                                        ? \Carbon\Carbon::parse(auth()->user()->billing_period)->format(
                                                            'F Y',
                                                        )
                                                        : 'N/A';
                                                @endphp

                                                <div class="form-group">
                                                    <label class="font-weight-bold mb-2">Billing Period</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $billingPeriod }}" readonly>
                                                </div>


                                                <div class="form-group">

                                                    <label for="file" class="font-weight-bold mb-2">📁 Installment
                                                        Forecast
                                                        File
                                                    </label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="file"
                                                            name="file">
                                                        <label class="custom-file-label" for="file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="savings_file" class="font-weight-bold mb-2">💰 Savings
                                                        File</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input"
                                                            id="savings_file" name="savings_file">
                                                        <label class="custom-file-label" for="savings_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="shares_file" class="font-weight-bold mb-2">📊 Shares
                                                        File</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="shares_file"
                                                            name="shares_file">
                                                        <label class="custom-file-label" for="shares_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="cif_file" class="font-weight-bold mb-2">👤 CIF File
                                                    </label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="cif_file"
                                                            name="cif_file">
                                                        <label class="custom-file-label" for="cif_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="loan_file" class="font-weight-bold mb-2">🏛️ Loans
                                                        File
                                                    </label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input"
                                                            id="loan_file" name="loan_file">
                                                        <label class="custom-file-label" for="loan_file">Choose
                                                            file...</label>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Upload</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            @endif

                                                                                                                @if(!$isApproved)
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fa fa-exclamation-triangle me-2"></i>
                                    <strong>Upload Disabled:</strong> File upload is currently disabled because one or more branch users have been approved.
                                    Upload is only enabled when all branch users are still in pending status.
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            @endif


                            <div class="card-body">
                                <div class="alert alert-info mb-3">
                                    <i class="fa fa-info-circle me-2"></i>
                                    <strong>Note:</strong> This table shows only the latest 5 files total for the current billing period.
                                    To view all files and manage file retention, use the <strong>File Retention</strong> button above.
                                </div>

                                <div class="alert alert-warning mb-3">
                                    <i class="fa fa-exclamation-triangle me-2"></i>
                                    <strong>Important:</strong> If you're uploading files downloaded from websites and encounter "Invalid Spreadsheet file" errors,
                                    please open the file in Excel and save it as a new .csv file before uploading. This ensures compatibility with our system.
                                </div>
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Filename</th>
                                                <th>Document Type</th>
                                                <th>File Path</th>
                                                <th>Billing Period</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($documents as $document)
                                                <tr>
                                                    <td>{{ $document->filename }}</td>
                                                    <td>{{ $document->document_type }}</td>
                                                    <td>{{ $document->filepath }}</td>
                                                    @php
                                                        $billingPeriod = $document->billing_period
                                                            ? \Carbon\Carbon::parse($document->billing_period)
                                                            : null;
                                                    @endphp

                                                    <td>
                                                        {{ $billingPeriod ? $billingPeriod->format('Y-m') : 'N/A' }}
                                                    </td>

                                                    <td>
                                                        <a class="btn btn-rounded btn-primary"
                                                            href="{{ asset('storage/' . $document->filepath) }}"
                                                            target="_blank">
                                                            <i class="fas fa-download me-1"></i> Download
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>

                                        <tfoot>
                                            <tr>
                                                <th>Filename</th>
                                                <th>Document Type</th>
                                                <th>File Path</th>
                                                <th>Billing Period</th>
                                                <th>Actions</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/"
                        target="_blank">MASS-SPECC
                        COOPERATIVE</a>2025</p>
            </div>
        </div>

    </div>

    <script src="./vendor/global/global.min.js"></script>
    <script src="./js/quixnav-init.js"></script>
    <script src="./js/custom.min.js"></script>
    <script src="./vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="./js/plugins-init/datatables.init.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadForm = document.getElementById('uploadForm');

            if (uploadForm) {
                uploadForm.addEventListener('submit', function() {
                    Swal.fire({
                        title: 'Uploading...',
                        html: 'Please wait while the file is being processed.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                });
            }
        });
    </script>

    {{-- <script>
        document.querySelectorAll('.custom-file-input').forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                let fileName = file?.name || 'Choose file...';
                e.target.nextElementSibling.innerText = fileName;

                // Validate file if one is selected
                if (file) {
                    const fileSize = file.size / 1024 / 1024; // Convert to MB
                    const fileName = file.name.toLowerCase();

                    // Check file size
                    if (fileSize > 2) {
                        Swal.fire({
                            icon: 'error',
                            title: 'File Too Large',
                            text: 'File size exceeds 2MB limit. Please choose a smaller file.'
                        });
                        e.target.value = '';
                        e.target.nextElementSibling.innerText = 'Choose file...';
                        return;
                    }

                    // Check file extension
                    const allowedExtensions = ['.xlsx', '.xls', '.csv'];
                    const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));

                    if (!hasValidExtension) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid File Format',
                            text: 'Please upload .xlsx, .xls, or .csv files only.'
                        });
                        e.target.value = '';
                        e.target.nextElementSibling.innerText = 'Choose file...';
                        return;
                    }

                    // Show file info for downloaded files
                    if (fileName.includes('download') || fileName.includes('export')) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Downloaded File Detected',
                            html: `
                                <p>This appears to be a downloaded file. If you encounter upload issues:</p>
                                <ol class="text-left">
                                    <li>Open the file in Excel</li>
                                    <li>Save it as a new .xlsx file</li>
                                    <li>Upload the new file</li>
                                </ol>
                            `,
                            confirmButtonText: 'Continue Upload',
                            showCancelButton: true,
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (!result.isConfirmed) {
                                e.target.value = '';
                                e.target.nextElementSibling.innerText = 'Choose file...';
                            }
                        });
                    }
                }
            });
        });
    </script> --}}


</body>

</html>
