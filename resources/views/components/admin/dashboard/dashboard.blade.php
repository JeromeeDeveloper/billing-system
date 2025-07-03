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
                                    <div class="stat-text">Total Members</div>
                                    <div class="stat-digit">{{ number_format($totalMembers) }}</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-success w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card">
                            <div class="stat-widget-two card-body">
                                <div class="stat-content">
                                    <div class="stat-text">Active Loans</div>
                                    <div class="stat-digit">{{ number_format($totalActiveLoans) }}</div>
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
                                    <div class="stat-text">Total Loan Amount</div>
                                    <div class="stat-digit">₱{{ number_format($totalLoanAmount, 2) }}</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-warning w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card">
                            <div class="stat-widget-two card-body">
                                <div class="stat-content">
                                    <div class="stat-text">Total Savings</div>
                                    <div class="stat-digit">₱{{ number_format($totalSavings, 2) }}</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-danger w-100" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-8 col-lg-8 col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Monthly Loan Overview</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xl-12 col-lg-8">
                                        <div id="morris-bar-chart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-4 col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="m-t-10">
                                    <h4 class="card-title">Member Status Distribution</h4>
                                    <h2 class="mt-3">{{ number_format($totalMembers) }}</h2>
                                </div>
                                <div class="widget-card-circle mt-5 mb-5" id="info-circle-card">
                                    <i class="ti-control-shuffle pa"></i>
                                </div>
                                <ul class="widget-line-list m-b-15">
                                    <li class="border-right">{{ $deductionPercentage }}% <br><span class="text-success"><i
                                                class="ti-hand-point-up"></i> Deduction</span></li>
                                    <li>{{ $nonDeductionPercentage }}% <br><span class="text-danger"><i
                                                class="ti-hand-point-down"></i>Non-Deduction</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Generation Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
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
                        </div>
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
        // Morris bar chart
        Morris.Bar({
            element: 'morris-bar-chart',
            data: [
                @foreach($months as $index => $month)
                {
                    y: '{{ $month }}',
                    a: {{ $loanAmounts[$index] ?? 0 }},
                    b: {{ $loanCounts[$index] ?? 0 }}
                }{{ !$loop->last ? ',' : '' }}
                @endforeach
            ],
            xkey: 'y',
            ykeys: ['a', 'b'],
            labels: ['Loan Amount', 'Number of Loans'],
            barColors: ['#343957', '#5873FE'],
            hideHover: 'auto',
            gridLineColor: '#eef0f2',
            resize: true
        });

        $('#info-circle-card').circleProgress({
            value: {{ $deductionPercentage / 100 }},
            size: 100,
            fill: {
                gradient: ["#a389d5"]
            }
        });
    </script>
    @endpush

</body>

</html>
