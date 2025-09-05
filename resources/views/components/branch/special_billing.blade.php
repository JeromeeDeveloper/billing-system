<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Special Billing - Branch</title>
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
                            <h4>Special Billing (Branch)</h4>
                            <span class="ml-1">View and Export Special Billing Data for Your Branch</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard_branch') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Special Billing</li>
                        </ol>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center align-items-start gap-2">
                                <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                                    <h4 class="card-title mb-0 me-3">Special Billing Data (Branch)</h4>
                                    <span class="badge badge-{{ $specialBillingApprovalStatus === 'approved' ? 'success' : 'warning' }}">
                                        <i class="fa fa-{{ $specialBillingApprovalStatus === 'approved' ? 'check-circle' : 'clock' }}"></i>
                                        Status: {{ ucfirst($specialBillingApprovalStatus) }}
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    @php
                                        $specialBillingEnabled = $exportStatuses->get('special_billing') ? $exportStatuses->get('special_billing')->is_enabled : true;
                                        $userHasExported = $exportStatuses->get('special_billing') && !$exportStatuses->get('special_billing')->is_enabled;
                                        $canExport = $specialBillingEnabled && !$noBranch && !$noRegularSavings && !$notAllApproved && $userIsApproved && $allBranchUsersApproved && !$anyBranchUsersPending && !$userHasExported && $specialBillingApprovalStatus === 'approved';
                                    @endphp

                                    <!-- Export Button -->
                                    <a href="{{ $canExport && $hasSpecialBillingData ? route('special-billing.export.branch') : 'javascript:void(0);' }}"
                                       class="btn btn-rounded btn-primary text-white {{ !$canExport || !$hasSpecialBillingData ? 'disabled' : '' }}"
                                       onclick="{{ $canExport && $hasSpecialBillingData ? '' : 'void(0)' }}">
                                        <span class="btn-icon-left text-primary"><i class="fa fa-file"></i></span>
                                        Generate Special Billing
                                    </a>


                                    <!-- Approval Buttons -->
                                    @if($specialBillingApprovalStatus === 'pending' && !$hasSpecialBillingExportForPeriod)
                                        <form action="{{ route('branch.remittance.special-billing.approve') }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-rounded btn-success text-white">
                                                <span class="btn-icon-left text-success"><i class="fa fa-check"></i></span>
                                                Approve Special Billing
                                            </button>
                                        </form>
                                    @elseif($specialBillingApprovalStatus === 'approved')
                                        <form action="{{ route('branch.remittance.special-billing.cancel-approval') }}" method="POST" class="m-0" id="cancelSpecialBillingApprovalForm">
                                            @csrf
                                            <button type="submit" class="btn btn-rounded btn-warning text-white {{ $hasSpecialBillingExportForPeriod ? 'disabled' : '' }}"
                                                    @if($hasSpecialBillingExportForPeriod) disabled @endif>
                                                <span class="btn-icon-left text-warning"><i class="fa fa-times"></i></span>
                                                Cancel Approval
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Show message when special billing is already generated -->
                                    @if($hasSpecialBillingExportForPeriod)
                                        <span class="badge badge-info">
                                            <i class="fa fa-info-circle"></i> Special billing already generated
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Disabled Messages -->
                            @if($hasSpecialBillingExportForPeriod)
                                <div class="alert alert-danger text-center small mb-0 mt-2">
                                    Special billing has been generated for this period. Cancel approval is disabled.
                                </div>
                            @endif
                            @if(!$hasSpecialBillingData)
                                <div class="alert alert-warning text-center small mb-0 mt-2">
                                    * No special billing data available for this period.
                                </div>
                            @elseif($userHasExported)
                                <div class="alert alert-warning text-center small mb-0 mt-2">
                                    * You have already exported for this billing period.
                                </div>
                            @elseif(!$specialBillingEnabled)
                                <div class="alert alert-warning text-center small mb-0 mt-2">
                                    * Already exported. Wait for next period to enable.
                                </div>
                            @elseif($anyBranchUsersPending)
                                <div class="alert alert-warning text-center small mb-0 mt-2">
                                    * Some branch users have pending status.
                                </div>
                            @elseif($noBranch)
                                <div class="alert alert-warning text-center small mb-0 mt-2">
                                    * Some members have no branch assigned.
                                </div>
                            @elseif($noRegularSavings)
                                <div class="alert alert-warning text-center small mb-0 mt-2">
                                    * Some members have no regular savings.
                                </div>
                            @elseif($notAllApproved)
                                <div class="alert alert-warning text-center small mb-0 mt-2">
                                    * Some members are not approved.
                                </div>
                            @elseif(!$userIsApproved)
                                <div class="alert alert-warning text-center small mb-0 mt-2">
                                    * Your account is not approved.
                                </div>
                            @elseif(!$allBranchUsersApproved)
                                <div class="alert alert-warning text-center small mb-0 mt-2">
                                    * Not all branch users are approved.
                                </div>
                            @elseif($specialBillingApprovalStatus !== 'approved')
                                <div class="alert alert-warning text-center small mb-0 mt-2">
                                    * Special billing must be approved first.
                                </div>
                            @endif

                            <div class="card-body">
                                <!-- Information Note -->
                                <div class="alert alert-info alert-dismissible fade show mb-4">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><i class="fa fa-info-circle"></i> Branch Special Billing Flow & User Guide</h5>
                                    <ol class="mb-2">
                                        <li><strong>View:</strong> Branch users can view special billing data for their branch only.</li>
                                        <li><strong>Approve:</strong> Review the data and approve special billing when ready.</li>
                                        <li><strong>Export:</strong> Export special billing data only after approval.</li>
                                        <li><strong>Filtered Data:</strong> All data and exports are limited to your branch's members.</li>
                                    </ol>
                                    <p class="mb-0"><small><strong>Note:</strong> Approval is required before exporting. Once exported, approval cannot be cancelled.</small></p>
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

                                @if (session('special_billing_approval_success'))
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <strong><i class="fa fa-check-circle"></i> Success!</strong>
                                        {{ session('special_billing_approval_success') }}
                                    </div>
                                @endif

                                <!-- Search Form -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <form action="{{ route('special-billing.index.branch') }}" method="GET" class="form-inline">
                                            <div class="input-group">
                                                <input type="text" name="search" class="form-control" placeholder="Search by Employee ID, Name, or CID..." value="{{ request('search') }}">
                                                <div class="input-group-append">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fa fa-search"></i> Search
                                                    </button>
                                                    @if(request('search'))
                                                        <a href="{{ route('special-billing.index.branch') }}" class="btn btn-secondary">
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
                                                <th>CID</th>
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

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
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
                text: '{{ session('error') }}'
            });
        </script>
    @endif

    <script>
        // Handle Cancel Special Billing Approval button click
        const cancelSpecialBillingForm = document.getElementById('cancelSpecialBillingApprovalForm');
        if (cancelSpecialBillingForm) {
            cancelSpecialBillingForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Show loading state
                Swal.fire({
                    title: 'Checking Special Billing Status...',
                    text: 'Please wait while we check if special billing export has been generated.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Check database for special billing exports
                fetch('{{ route("branch.remittance.special-billing.check-export-status") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.hasExport) {
                        // Special billing export has been generated, show error message
                        Swal.fire({
                            title: 'Cannot Cancel Approval',
                            text: 'Special billing export has already been generated for this period. Cancel approval is not allowed.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        // No special billing export found, proceed with cancellation
                        Swal.fire({
                            title: 'Cancel Special Billing Approval?',
                            text: 'Are you sure you want to cancel the special billing approval? This action cannot be undone.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, Cancel Approval',
                            cancelButtonText: 'No, Keep Approval'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Submit the form
                                this.submit();
                            }
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while checking special billing status.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    console.error('Error:', error);
                });
            });
        }
    </script>
</body>
</html>
