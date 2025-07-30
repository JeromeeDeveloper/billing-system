<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Special Billing - Billing and Collection</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">
    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <div id="main-wrapper">
        @include('layouts.partials.header')
        @include('layouts.partials.sidebar')
        <div class="content-body">
            <div class="container-fluid">
                <div class="row page-titles mx-0 mb-3">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4>Special Billing</h4>
                            <span class="ml-1">Upload and Process Special Billing Data</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Special Billing</li>
                        </ol>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Upload Special Billing Excel File</h4>
                                @php
                                    $specialBillingEnabled = $exportStatuses->get('special_billing') ? $exportStatuses->get('special_billing')->is_enabled : true;
                                    $userHasExported = $exportStatuses->get('special_billing') && !$exportStatuses->get('special_billing')->is_enabled;
                                    $canExport = $specialBillingEnabled && !$noBranch && !$noRegularSavings && $allBranchUsersApproved && !$anyBranchUsersPending && !$userHasExported;
                                @endphp
                                <a href="{{ $canExport && $hasSpecialBillingData ? route('special-billing.export') : 'javascript:void(0);' }}"
                                   class="btn btn-success {{ !$canExport || !$hasSpecialBillingData ? 'disabled' : '' }}"
                                   onclick="{{ $canExport && $hasSpecialBillingData ? '' : 'void(0)' }}">
                                    <i class="fa fa-file-excel"></i> Export Special Billing
                                    @if(!$hasSpecialBillingData)
                                        <br><small class="text-muted">(Disabled - No special billing data for this period)</small>
                                    @elseif($userHasExported)
                                        <br><small class="text-muted">(Disabled - You have already exported for this billing period)</small>
                                    @elseif(!$specialBillingEnabled)
                                        <br><small class="text-muted">(Disabled - Already exported. Wait for next period to enable)</small>
                                    @elseif($anyBranchUsersPending)
                                        <br><small class="text-muted">(Disabled - Some branch users have pending status)</small>
                                    @elseif($noBranch)
                                        <br><small class="text-muted">(Disabled - Some members have no branch)</small>
                                    @elseif($noRegularSavings)
                                        <br><small class="text-muted">(Disabled - Some members have no regular savings)</small>
                                    @elseif(!$allBranchUsersApproved)
                                        <br><small class="text-muted">(Disabled - Not all branch users are approved)</small>
                                    @endif
                                </a>
                            </div>
                            <div class="card-body">
                                <!-- Information Note -->
                                <div class="alert alert-info alert-dismissible fade show mb-4">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><i class="fa fa-info-circle"></i> Special Billing Flow & User Guide (Admin)</h5>
                                    <ol class="mb-2">
                                        <li><strong>Upload:</strong> Admin uploads special billing files (forecast and Loan).</li>
                                        <li><strong>Processing:</strong> System processes only loans with special billing type and calculates amortization.</li>
                                        <li><strong>Review & Search:</strong> Admin can search, review, and export special billing data for all branches.</li>
                                        <li><strong>Export:</strong> Export the processed special billing data as needed.</li>
                                    </ol>
                                    <ul class="mb-2">
                                        <li><strong>File Requirements:</strong> Ensure files meet the required format and headers before uploading.</li>
                                        <li><strong>History:</strong> View and download previous special billing uploads and exports.</li>
                                    </ul>
                                    <p class="mb-0"><small><strong>Note:</strong> Only admin can upload special billing data. Branch users can only view and export data filtered to their branch.</small></p>
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
                                <form action="{{ route('special-billing.import') }}" method="POST" enctype="multipart/form-data" class="mb-4">
                                    @csrf
                                    <div class="form-group">
                                        <label class="font-weight-bold">Forecast File</label>
                                        <div class="custom-file mb-2">
                                            <input type="file" class="custom-file-input" name="forecast_file" id="forecast_file" accept=".xlsx,.xls,.csv" required>
                                            <label class="custom-file-label" for="forecast_file">Choose forecast file</label>
                                        </div>
                                        <label class="font-weight-bold">Loan File</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" name="detail_file" id="detail_file" accept=".xlsx,.xls,.csv" required>
                                            <label class="custom-file-label" for="detail_file">Choose Loan file</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-info">
                                        <i class="fa fa-upload"></i> Upload and Process Special Billing
                                    </button>
                                </form>

                                <!-- Search Form -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <form action="{{ route('special-billing.index') }}" method="GET" class="form-inline">
                                            <div class="input-group">
                                                <input type="text" name="search" class="form-control" placeholder="Search by Employee ID, Name, or CID..." value="{{ request('search') }}">
                                                <div class="input-group-append">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fa fa-search"></i> Search
                                                    </button>
                                                    @if(request('search'))
                                                        <a href="{{ route('special-billing.index') }}" class="btn btn-secondary">
                                                            <i class="fa fa-times"></i> Clear
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <span class="text-muted">Showing {{ $specialBillings->firstItem() ?? 0 }} to {{ $specialBillings->lastItem() ?? 0 }} of {{ $specialBillings->total() }} entries</span>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>CID</th>h
                                                <th>Employee ID</th>
                                                <th>Name</th>
                                                <th>Amortization</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Gross</th>
                                                <th>Office</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($specialBillings as $billing)
                                            <tr>
                                                <td>{{ $billing->cid }}</td>
                                                <td>{{ $billing->employee_id }}</td>
                                                <td>{{ $billing->name }}</td>
                                                <td>{{ number_format($billing->amortization, 2) }}</td>
                                                <td>{{ $billing->start_date }}</td>
                                                <td>{{ $billing->end_date }}</td>
                                                <td>{{ number_format($billing->gross, 2) }}</td>
                                                <td>{{ $billing->office }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="7" class="text-center">No special billing records found.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                @if($specialBillings->hasPages())
                                    <div class="d-flex justify-content-center mt-3">
                                        {{ $specialBillings->appends(request()->query())->links() }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer">
            <div class="copyright">
                <p>Copyright © Designed & Developed by <a href="https://mass-specc.coop/" target="_blank">MASS-SPECC COOPERATIVE</a>2025</p>
            </div>
        </div>
    </div>
    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('js/quixnav-init.js') }}"></script>
    <script src="{{ asset('js/custom.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Update file input labels to show selected filenames
        document.addEventListener('DOMContentLoaded', function() {
            const forecastFile = document.getElementById('forecast_file');
            const detailFile = document.getElementById('detail_file');
            const uploadForm = document.querySelector('form[action="{{ route('special-billing.import') }}"]');

            forecastFile.addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : 'Choose forecast file';
                this.nextElementSibling.textContent = fileName;
            });

            detailFile.addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : 'Choose detail file';
                this.nextElementSibling.textContent = fileName;
            });

            // Show loading swal on upload form submit
            if (uploadForm) {
                uploadForm.addEventListener('submit', function(e) {
                    Swal.fire({
                        title: 'Uploading... Please wait',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                });
            }
        });
    </script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: @json(session('success')),
                showConfirmButton: false,
                timer: 1500
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: @json(session('error')),
                showConfirmButton: true
            });
        </script>
    @endif
</body>
</html>
