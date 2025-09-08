<!DOCTYPE html>
<html lang="en">

@include('layouts.partials.head')

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body">
            @if(session('status_change_notice'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <strong>Status Change Notice:</strong> {{ session('status_change_notice') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif
            <!-- row -->
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-3 col-sm-6">
                        <div class="card">
                            <div class="stat-widget-two card-body">
                                <div class="stat-content">
                                    <div class="stat-text">Total Members</div>
                                    <div class="stat-digit">{{ number_format($totalMembers) }}</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-primary w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card">
                            <div class="stat-widget-two card-body">
                                <div class="stat-content">
                                    <div class="stat-text">Total Branches</div>
                                    <div class="stat-digit">{{ number_format($totalBranches ?? 0) }}</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-primary w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card">
                            <div class="stat-widget-two card-body">
                                <div class="stat-content">
                                    <div class="stat-text">Active Loans</div>
                                    <div class="stat-digit">{{ number_format($totalActiveLoans ?? 0) }}</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-primary w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card">
                            <div class="stat-widget-two card-body">
                                <div class="stat-content">
                                    <div class="stat-text">Count of Loan Products</div>
                                    <div class="stat-digit">{{ number_format($totalLoanProducts) }}</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-primary w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-8 col-lg-8 col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Members Overview</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xl-12 col-lg-8">
                                        <div id="branch-members-chart">
                                            <div class="row">
                                                @foreach($branches as $index => $branch)
                                                    @php
                                                        $memberCount = $memberCounts[$index];
                                                        $percentage = $totalMembers > 0 ? round(($memberCount / $totalMembers) * 100, 1) : 0;
                                                        $colors = ['#593bdb'];
                                                        $color = $colors[$index % count($colors)];
                                                    @endphp
                                                    <div class="col-lg-6 col-md-6 mb-4">
                                                        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid {{ $color }} !important;">
                                                            <div class="card-body p-4">
                                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                                    <div>
                                                                        <h6 class="card-title mb-1 text-dark font-weight-bold">{{ $branch }}</h6>
                                                                        <small class="text-muted">Branch</small>
                                                                    </div>
                                                                    <div class="text-right">
                                                                        <h3 class="mb-0 font-weight-bold" style="color: {{ $color }}">{{ number_format($memberCount) }}</h3>
                                                                        <small class="text-muted">Members</small>
                                                                    </div>
                                                                </div>

                                                                <div class="progress mb-2" style="height: 8px; background-color: #f8f9fa;">
                                                                    <div class="progress-bar" role="progressbar"
                                                                         style="width: {{ $percentage }}%; background-color: {{ $color }};"
                                                                         aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                                                    </div>
                                                                </div>

                                                                {{-- <div class="d-flex justify-content-between align-items-center">
                                                                    <small class="text-muted">{{ $percentage }}% of total</small>
                                                                    @if($memberCount > 0)
                                                                        <span class="badge badge-pill" style="background-color: {{ $color }}; color: white;">
                                                                            {{ $memberCount > 1000 ? 'Large' : ($memberCount > 100 ? 'Medium' : 'Small') }}
                                                                        </span>
                                                                    @else
                                                                        <span class="badge badge-pill badge-secondary">No Members</span>
                                                                    @endif
                                                                </div> --}}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <!-- Summary Card -->
                                            <div class="row mt-4">
                                                <div class="col-12">
                                                    <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                        <div class="card-body p-4 text-white">
                                                            <div class="row align-items-center">
                                                                <div class="col-md-8">
                                                                    <h4 class="mb-2 font-weight-bold">Total Overview</h4>
                                                                    <p class="mb-0 opacity-75">Across all {{ count($branches) }} branches</p>
                                                                </div>
                                                                <div class="col-md-4 text-right">
                                                                    <h2 class="mb-0 font-weight-bold">{{ number_format($totalMembers) }}</h2>
                                                                    <small class="opacity-75">Total Members</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-4 col-md-4">

                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Report Center</h4>
                            </div>
                            <div class="card-body">
                                <!-- Information Note -->
                                <div class="alert alert-info alert-dismissible fade show mb-4">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><i class="fa fa-info-circle"></i> Billing Period Management</h5>
                                    <p class="mb-2">You can manually close the current billing period and move all users to the next period. This is useful if remittances are late or you want to control the period closing date.</p>
                                    <form method="POST" action="{{ route('billing.close-period') }}" id="closePeriodForm">
                                        @csrf
                                        <button type="button" class="btn btn-danger mt-2" data-toggle="modal" data-target="#closePeriodModal">
                                            <i class="fa fa-calendar-times-o"></i> Close Billing Period
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>

                        {{-- <div class="card">
                            <div class="card-body text-center">
                                <div class="m-t-10">
                                    <h4 class="card-title">Member Status Distribution</h4>
                                    <h2 class="mt-3">{{ number_format($totalMembers) }}</h2>
                                </div>
                                <div class="widget-card-circle mt-5 mb-5" id="info-circle-card">
                                    <i class="ti-control-shuffle pa"></i>
                                </div>
                                <ul class="widget-line-list m-b-15">
                                    <li class="border-right">{{ $pgbPercentage }}% <br><span class="text-success"><i
                                                class="ti-hand-point-up"></i> PGB</span></li>
                                    <li>{{ $newPercentage }}% <br><span class="text-danger"><i
                                                class="ti-hand-point-down"></i>New</span></li>
                                </ul>
                            </div>
                        </div> --}}



                    </div>
                </div>

                <!-- Report Generation Section -->
                <div class="row">
                    <div class="col-12">
                        {{-- <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Report Center</h4>
                            </div>
                            <div class="card-body">
                                <!-- Information Note -->
                                <div class="alert alert-info alert-dismissible fade show mb-4">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><i class="fa fa-info-circle"></i> Available Reports</h5>
                                    <ul class="mb-2">
                                        <li><strong>List of Profile:</strong> Export comprehensive member profile data</li>
                                        <li><strong>Remittance Report Consolidated:</strong> Consolidated remittance data across all branches</li>
                                        <li><strong>Remittance Report Per Branch:</strong> Branch-specific remittance reports</li>
                                        <li><strong>Remittance Report Per Branch Member:</strong> Export records of all members per branch</li>
                                    </ul>
                                    <p class="mb-0"><small><strong>Note:</strong> All reports are generated for the current billing period and include the latest data.</small></p>
                                </div>

                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="{{ route('atm.export.list-of-profile') }}" class="btn btn-success btn-block">
                                            <i class="fas fa-file-excel me-2"></i> Export List of Profile
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="{{ route('atm.export.remittance-report-consolidated') }}" class="btn btn-primary btn-block">
                                            <i class="fas fa-file-excel me-2"></i> Export Remittance Report Consolidated
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="{{ route('atm.export.remittance-report-per-branch') }}" class="btn btn-info btn-block">
                                            <i class="fas fa-file-excel me-2"></i> Remittance Report Per Branch
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="{{ route('atm.export.remittance-report-per-branch-member') }}" class="btn btn-warning btn-block">
                                            <i class="fas fa-file-excel me-2"></i> Remittance Report Per Branch Member
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/" target="_blank">MASS-SPECC COOPERATIVE</a>2025</p>
            </div>
        </div>
    </div>

    @include('layouts.partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if ($showPrompt)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                title: 'Select Billing Period',
                html: `<input type="month" id="billing_period" class="swal2-input" placeholder="Billing Period">`,
                confirmButtonText: 'Agree',
                showCancelButton: false,
                allowOutsideClick: false,
                preConfirm: () => {
                    const billingPeriod = document.getElementById('billing_period').value;
                    if (!billingPeriod) {
                        Swal.showValidationMessage('Please select a billing period');
                        return false;
                    }

                    return fetch('{{ route('dashboard.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ billing_period: billingPeriod })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to save billing period');
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error}`);
                    });
                }
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
        });
    </script>
    @endif

    @if (session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '{{ session('success') }}',
            confirmButtonColor: '#3085d6'
        });
    </script>
    @endif

                @push('scripts')
    <script>
        // Initialize circle progress for member status distribution
        $(document).ready(function() {
            $('#info-circle-card').circleProgress({
                value: {{ $pgbPercentage / 100 }},
                size: 100,
                fill: {
                    gradient: ["#a389d5"]
                }
            });
        });
    </script>
    @endpush

    <!-- Custom Bootstrap Modal for Confirmation -->
    <div class="modal fade" id="closePeriodModal" tabindex="-1" role="dialog" aria-labelledby="closePeriodModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title text-white" id="closePeriodModalLabel"><i class="fa fa-exclamation-triangle"></i> Close Billing Period Confirmation</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="text-center">
              <!-- Current Billing Period Display -->
              <div class="alert alert-primary mb-4">
                <h6 class="alert-heading"><i class="fa fa-calendar"></i> Current Billing Period</h6>
                <h4 class="text-white mb-0">
                  {{ Auth::user()->billing_period ? \Carbon\Carbon::parse(Auth::user()->billing_period)->format('F Y') : 'Not Set' }}
                </h4>
                {{-- <small class="text-muted">{{ Auth::user()->billing_period ? \Carbon\Carbon::parse(Auth::user()->billing_period)->format('Y-m-01') : '' }}</small> --}}
              </div>

              <!-- Next Billing Period Display -->
              <div class="alert alert-primary mb-4">
                <h6 class="alert-heading"><i class="fa fa-calendar-plus"></i> Next Billing Period</h6>
                <h4 class="text-white mb-0">
                  {{ Auth::user()->billing_period ? \Carbon\Carbon::parse(Auth::user()->billing_period)->addMonth()->format('F Y') : 'Not Set' }}
                </h4>
                {{-- <small class="text-muted">{{ Auth::user()->billing_period ? \Carbon\Carbon::parse(Auth::user()->billing_period)->addMonth()->format('Y-m-01') : '' }}</small> --}}
              </div>

              <!-- Warning Messages -->
              <div class="alert alert-primary">
                <h6 class="alert-heading text-primary"><i class="fa fa-exclamation-triangle"></i> Important Warnings</h6>
                <ul class="text-center mb-0 text-white">
                  <li><strong>This will close the current billing period for <span class="text-danger">ALL USERS</span></strong></li>
                  <li><strong>All users will be moved to the next billing period</strong></li>
                  <li><strong>This action cannot be undone</strong></li>
                  <li><strong>Late remittances or unfinished tasks will be <span class="text-danger">LOCKED OUT</span></strong></li>
                </ul>
              </div>

              <p class="text-danger font-weight-bold mt-3">Please double-check with your team before proceeding.</p>
            </div>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              <i class="fa fa-times"></i> Cancel
            </button>
            <button type="button" class="btn btn-danger" id="confirmClosePeriod">
              <i class="fa fa-calendar-times-o"></i> Yes, Close Billing Period
            </button>
          </div>
        </div>
      </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var confirmBtn = document.getElementById('confirmClosePeriod');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    // Get current and next billing period for display
                    var currentPeriod = '{{ Auth::user()->billing_period ? \Carbon\Carbon::parse(Auth::user()->billing_period)->format("F Y") : "Not Set" }}';
                    var nextPeriod = '{{ Auth::user()->billing_period ? \Carbon\Carbon::parse(Auth::user()->billing_period)->addMonth()->format("F Y") : "Not Set" }}';

                    // Show loading state
                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';

                    // Submit form via AJAX
                    fetch('{{ route('billing.close-period') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Billing Period Closed Successfully!',
                                html: `
                                    <div class="text-left">
                                        <p><strong>Current Period:</strong> ${currentPeriod}</p>
                                        <p><strong>New Period:</strong> ${nextPeriod}</p>
                                        <p><strong>Status:</strong> All users have been moved to the new billing period.</p>
                                        <p><strong>Note:</strong> All branch users have been set to 'pending' status.</p>
                                    </div>
                                `,
                                confirmButtonColor: '#3085d6',
                                allowOutsideClick: false,
                                confirmButtonText: 'OK - Logout Now'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Redirect to login page
                                    window.location.href = '{{ route('login.form') }}';
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error Closing Billing Period',
                                text: data.message || 'An error occurred while closing the billing period.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while closing the billing period. Please try again or contact support.',
                            confirmButtonColor: '#d33'
                        });
                    })
                    .finally(() => {
                        // Reset button state
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="fa fa-calendar-times-o"></i> Yes, Close Billing Period';
                    });
                });
            }
        });
    </script>

</body>

</html>
