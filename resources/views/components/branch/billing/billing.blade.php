<!DOCTYPE html>
<html lang="en">

<head>

    @include('layouts.partials.head')

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
                            <h4>Billing</h4>
                            <span class="ml-1">Datatable</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard_branch') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active"><a href="{{ route('billing.branch') }}">Billing</a></li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">

                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Billing Datatable</h4>

                                <div class="d-flex flex-column align-items-end">
                                    <div class="d-flex mb-1 buttons-header">
                                        <a href="{{ $allBranchApproved ? route('billing.export.branch', ['billing_period' => now()->format('Y-m')]) : '#' }}"
                                            class="btn btn-rounded btn-primary text-white me-2 {{ !$allBranchApproved ? 'disabled' : '' }}"
                                            @if (!$allBranchApproved) onclick="Swal.fire('Action Blocked', 'All branch users must be approved before generating billing.', 'warning'); return false;" @endif>
                                            <span class="btn-icon-left text-primary">
                                                <i class="fa fa-file"></i>
                                            </span>
                                            Generate Billing
                                        </a>

                                        <form action="{{ route('billing.approve') }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-rounded btn-primary text-white">
                                                <span class="btn-icon-left text-primary">
                                                    <i class="fa fa-check"></i>
                                                </span>
                                                Approve Billing
                                            </button>
                                        </form>

                                        <a href="{{ route('billing.loan-report.branch') }}" class="btn btn-rounded btn-success text-white me-2">
                                            <span class="btn-icon-left text-success">
                                                <i class="fa fa-chart-line"></i>
                                            </span>
                                            Loan Report
                                        </a>

                                        <a href="{{ route('billing.exports.branch') }}" class="btn btn-rounded btn-info text-white ms-2">
                                            <span class="btn-icon-left text-info">
                                                <i class="fa fa-history"></i>
                                            </span>
                                            View Export History
                                        </a>
                                    </div>

                                    @if (!$allBranchApproved)
                                        <div class="text-danger small">
                                            * Not all branch users has approved yet.
                                        </div>
                                    @endif
                                </div>
                            </div>


                            <form method="GET" action="{{ url()->current() }}"
                                class="card-header d-flex justify-content-between align-items-center">

                                <div>
                                    <label for="perPage">Show</label>
                                    <select name="perPage" id="perPage" onchange="this.form.submit()"
                                        class="form-select d-inline-block w-auto">
                                        <option value="10" {{ request('perPage') == 10 ? 'selected' : '' }}>10
                                        </option>
                                        <option value="25" {{ request('perPage') == 25 ? 'selected' : '' }}>25
                                        </option>
                                        <option value="50" {{ request('perPage') == 50 ? 'selected' : '' }}>50
                                        </option>
                                        <option value="100" {{ request('perPage') == 100 ? 'selected' : '' }}>100
                                        </option>
                                    </select>
                                    <label>entries</label>
                                </div>

                                <div class="d-flex">
                                    <input type="text" name="search" value="{{ request('search') }}"
                                        class="form-control" placeholder="Search..." />
                                    <button type="submit" class="btn btn-primary ms-2">Search</button>
                                </div>

                            </form>


                            <div class="card-body">
                                <!-- Information Note -->
                                <div class="alert alert-info alert-dismissible fade show mb-4">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <h5><i class="fa fa-info-circle"></i> Branch Billing Flow & User Guide</h5>
                                    <ol class="mb-2">
                                        <li><strong>Review & Edit:</strong> Branch users can view and edit billing records for their branch only.</li>
                                        <li><strong>Amortization:</strong> Only regular loans are included in the amortization column. Members with zero amortization are hidden.</li>
                                        <li><strong>Approval:</strong> Once all records are correct, approve your branch's billing. Admin cannot generate the consolidated export until all branches approve.</li>
                                        <li><strong>Export:</strong> Export your branch's billing data (if allowed) and view export history.</li>
                                        <li><strong>Loan Reports:</strong> Generate detailed loan reports showing total billed vs remitted amounts for your branch members.</li>
                                    </ol>
                                    <ul class="mb-2">
                                        <li><strong>Search & Filter:</strong> Use the search bar and filters to quickly find members or billing records within your branch.</li>
                                        <li><strong>Record Management:</strong> Edit or delete billing records as needed using the Actions column.</li>
                                    </ul>
                                    <p class="mb-0"><small><strong>Note:</strong> Only regular loans are included in amortization calculations. Approval is required before admin can generate the final report.</small></p>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered display">
                                        <thead>
                                            <tr>
                                                <th>Employee #</th>
                                                <th>Amortization</th>
                                                <th>Name</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Gross</th>
                                                <th>Office</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @php
                                                $productMap = json_decode(file_get_contents(public_path('loan_product_map.json')), true);
                                            @endphp
                                            @foreach ($billing as $member)
                                                @php
                                                    $amortization = $member->loanForecasts->filter(function($loan) use ($productMap) {
                                                        $segments = explode('-', $loan->loan_acct_no);
                                                        $productCode = $segments[2] ?? null;
                                                        return isset($productMap[$productCode]) && $productMap[$productCode] === 'regular';
                                                    })->sum('total_due');
                                                @endphp
                                                @if ($amortization > 0)
                                                    <tr>
                                                        <td>{{ $member->emp_id }}</td>
                                                        <td>{{ number_format($amortization, 2) }}</td>
                                                        <td>{{ $member->fname }} {{ $member->lname }}</td>
                                                        <td>{{ optional($member->start_date)->format('Y-m-d') }}</td>
                                                        <td>{{ optional($member->end_date)->format('Y-m-d') }}</td>
                                                        <td>{{ number_format($member->principal, 2) }}</td>
                                                        <td>{{ $member->area ?? '' }}</td>
                                                        <td>
                                                            <button class="btn btn-rounded btn-primary edit-btn"
                                                                data-toggle="modal" data-target="#editModal"
                                                                data-id="{{ $member->id }}"
                                                                data-emp_id="{{ $member->emp_id }}"
                                                                data-fname="{{ $member->fname }}"
                                                                data-lname="{{ $member->lname }}"
                                                                data-loan_balance="{{ $member->loan_balance }}"
                                                                data-principal="{{ $member->principal }}"
                                                                data-area="{{ $member->area }}"
                                                                data-start_date="{{ optional($member->start_date)->format('Y-m-d') }}"
                                                                data-end_date="{{ optional($member->end_date)->format('Y-m-d') }}">Edit</button>

                                                            <button class="btn btn-rounded btn-info view-btn"
                                                                data-toggle="modal" data-target="#viewModal"
                                                                data-emp_id="{{ $member->emp_id }}"
                                                                data-name="{{ $member->fname }} {{ $member->lname }}"
                                                                data-loan_balance="{{ $member->loan_balance }}"
                                                                data-start_date="{{ optional($member->start_date)->format('Y-m-d') }}"
                                                                data-end_date="{{ optional($member->end_date)->format('Y-m-d') }}"
                                                                data-principal="{{ $member->principal }}"
                                                                data-office="{{ $member->area }}">View</button>

                                                            <button class="btn btn-rounded btn-danger delete-btn"
                                                                data-toggle="modal" data-target="#deleteModal"
                                                                data-id="{{ $member->id }}">Delete</button>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>

                                        <tfoot>
                                            <tr>
                                                <th>Employee #</th>
                                                <th>Amortization</th>
                                                <th>Name</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Gross</th>
                                                <th>Office</th>
                                                <th>Actions</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-lg" role="document">
                                    <form method="POST" action="{{ route('billing.update.branch', 0) }}"
                                        id="editForm">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5>Edit Member</h5>
                                            </div>
                                            <div class="modal-body row">
                                                <input type="hidden" name="id" id="edit-id">

                                                <div class="form-group col-md-6">
                                                    <label>Employee #</label>
                                                    <input type="text" name="emp_id" id="edit-emp_id"
                                                        class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>First Name</label>
                                                    <input type="text" name="fname" id="edit-fname"
                                                        class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Last Name</label>
                                                    <input type="text" name="lname" id="edit-lname"
                                                        class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Loan Balance</label>
                                                    <input type="number" step="0.01" name="loan_balance"
                                                        id="edit-loan_balance" class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Principal</label>
                                                    <input type="number" step="0.01" name="principal"
                                                        id="edit-principal" class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Start Date</label>
                                                    <input type="date" name="start_date" id="edit-start_date"
                                                        class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>End Date</label>
                                                    <input type="date" name="end_date" id="edit-end_date"
                                                        class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Office</label>
                                                    <input type="text" name="area" id="edit-area"
                                                        class="form-control">
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-success">Update</button>
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-md" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5>View Member</h5>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Employee #:</strong> <span id="view-emp_id"></span></p>
                                            <p><strong>Name:</strong> <span id="view-name"></span></p>
                                            <p><strong>Start Date:</strong> <span id="view-start_date"></span></p>
                                            <p><strong>End Date:</strong> <span id="view-end_date"></span></p>
                                            <p><strong>Loan Balance:</strong> ₱<span id="view-loan_balance"></span></p>
                                            <p><strong>Principal:</strong> ₱<span id="view-principal"></span></p>
                                            <p><strong>Office:</strong> <span id="view-office"></span></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <form method="POST" action="" id="deleteForm">
                                        @csrf
                                        @method('DELETE')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5>Confirm Delete</h5>
                                            </div>
                                            <div class="modal-body">Are you sure you want to delete this member?</div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                                <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <style>
                                p.small.text-muted {
                                    display: none;
                                }
                            </style>

                            <div class="d-flex flex-column align-items-center my-4">
                                <div>
                                    Showing {{ $billing->firstItem() }} to {{ $billing->lastItem() }} of
                                    {{ $billing->total() }} results
                                </div>
                                <nav aria-label="Page navigation" class="mt-3">
                                    {{ $billing->links('pagination::bootstrap-5') }}
                                </nav>
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

    @include('layouts.partials.footer')

    <script>
        $('.edit-btn').on('click', function() {
            const button = $(this);
            const id = button.data('id');
            $('#editForm').attr('action', `/billing/${id}`);

            $('#edit-id').val(id);
            $('#edit-emp_id').val(button.data('emp_id'));
            $('#edit-fname').val(button.data('fname'));
            $('#edit-lname').val(button.data('lname'));
            $('#edit-loan_balance').val(button.data('loan_balance'));
            $('#edit-principal').val(button.data('principal'));
            $('#edit-start_date').val(button.data('start_date'));
            $('#edit-end_date').val(button.data('end_date'));
            $('#edit-area').val(button.data('area'));
        });


        $('.view-btn').on('click', function() {
            const button = $(this);
            $('#view-emp_id').text(button.data('emp_id'));
            $('#view-name').text(button.data('name'));
            $('#view-start_date').text(button.data('start_date'));
            $('#view-end_date').text(button.data('end_date'));
            $('#view-loan_balance').text(parseFloat(button.data('loan_balance')).toFixed(2));
            $('#view-principal').text(parseFloat(button.data('principal')).toFixed(2));
            $('#view-office').text(button.data('office'));
        });

        $('.delete-btn').on('click', function() {
            const id = $(this).data('id');
            $('#deleteForm').attr('action', `/billing/${id}`);
        });
    </script>

    <script>
        document.getElementById('generateBillingBtn').addEventListener('click', function(event) {
            event.preventDefault(); // prevent default navigation

            Swal.fire({
                title: 'Processing Billing',
                text: 'Please wait...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            // Redirect to href after showing loader
            window.location.href = this.href;
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '{{ session('success') }}',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        @endif

        @if (session('info'))
            Swal.fire({
                icon: 'info',
                title: 'Info',
                text: '{{ session('info') }}',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        @endif
    </script>

</body>

</html>
