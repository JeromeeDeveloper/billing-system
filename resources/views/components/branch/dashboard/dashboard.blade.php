<!DOCTYPE html>
<html lang="en">

@include('layouts.partials.head')

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
                                    <div class="stat-text">Branch Members</div>
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
                                    <div class="stat-text">Special Products</div>
                                    <div class="stat-digit">{{ number_format($specialProductTypeCount ?? 0) }}</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-primary w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-12 col-lg-12 col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Branch Billing Status</h4>

                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xl-12 col-lg-12">
                                        <div id="branch-status-chart" style="height: auto;">
                                            <div class="row">
                                                @foreach($allBranches as $branch)
                                                    @php
                                                        $isApproved = in_array($branch->name, $approvedBranches);
                                                        $statusColor = $isApproved ? '#28a745' : '#dc3545';
                                                        $statusText = $isApproved ? 'Approved' : 'Pending';
                                                    @endphp
                                                    <div class="col-lg-12 col-md-12 mb-12">
                                                        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid {{ $statusColor }} !important;">
                                                            <div class="card-body p-4">
                                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                                    <div>
                                                                        <h6 class="card-title mb-1 text-dark font-weight-bold">{{ $branch->name }}</h6>
                                                                        <small class="text-muted">Branch</small>
                                                                    </div>
                                                                    <div class="text-right">
                                                                        <span class="badge badge-pill px-3 py-2" style="background-color: {{ $statusColor }}; color: white;">
                                                                            {{ $statusText }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <!-- Summary Card -->
                                            {{-- <div class="row mt-4">
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
                                            </div> --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- Branch Report Generation Section -->
                <div class="row">
                    <div class="col-12">

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

                    return fetch('{{ route('dashboard.store.branch') }}', {
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

</body>

</html>

