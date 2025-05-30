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
                            <h4>Master List</h4>
                            <span class="ml-1">Datatable</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active"><a href="{{ route('member') }}">Member</a></li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Member Datatable</h4>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="{{ url()->current() }}"
                                    class="mb-3 d-flex justify-content-center">
                                    <input type="text" name="search" value="{{ request('search') }}"
                                        class="form-control w-50" placeholder="Search by CID, Name, Branch..." />
                                    <button type="submit" class="btn btn-primary ms-2">Search</button>
                                </form>
                                <div class="table-responsive">
                                    <table id="masterlistTable" class="table table-striped table-bordered display">
                                        <thead>
                                            <tr>
                                                <th>CID</th>
                                                <th>Name</th>
                                                <th>Branch</th>
                                                <th>Savings</th>
                                                <th>Share Balance</th>
                                                <th>Loan Balance</th>
                                                <th>Loan Accounts</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($masterlists->unique('member.id') as $item)
                                                <tr>
                                                    <td>{{ $item->member->cid ?? '' }}</td>
                                                    <td>{{ $item->member->lname ?? '' }},
                                                        {{ $item->member->fname ?? '' }}</td>
                                                    <td>{{ $item->branch->name ?? '' }}</td>
                                                    <td>{{ $item->member->savings_balance ?? '' }}</td>
                                                    <td>{{ $item->member->share_balance ?? 'N/A' }}</td>
                                                    <td>{{ $item->member->loan_balance ?? 'N/A' }}</td>
                                                    <td>
                                                        @if ($item->member && $item->member->loanForecasts->isNotEmpty())
                                                            @foreach ($item->member->loanForecasts as $loan)
                                                                <div>{{ $loan->loan_acct_no }}</div>
                                                            @endforeach
                                                        @else
                                                            <div>N/A</div>
                                                        @endif
                                                    </td>

                                                    <td>


                                                        <button type="button" class="btn btn-rounded btn-primary"
                                                            data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $item->member->id }}"
                                                            data-cid="{{ $item->member->cid }}"
                                                            data-emp_id="{{ $item->member->emp_id }}"
                                                            data-fname="{{ $item->member->fname }}"
                                                            data-lname="{{ $item->member->lname }}"
                                                            data-address="{{ $item->member->address }}"
                                                            data-savings_balance="{{ $item->member->savingsBalance }}"
                                                            data-share_balance="{{ $item->member->shareBalance }}"
                                                            data-loan_balance="{{ $item->member->loan_balance }}"
                                                            data-birth_date="{{ optional($item->member->birth_date)->format('Y-m-d') }}"
                                                            data-date_registered="{{ optional($item->member->date_registered)->format('Y-m-d') }}"
                                                            data-gender="{{ $item->member->gender }}"
                                                            data-customer_type="{{ $item->member->customer_type }}"
                                                            data-customer_classification="{{ $item->member->customer_classification }}"
                                                            data-occupation="{{ $item->member->occupation }}"
                                                            data-approval_no="{{ $item->member->approval_no }}"
                                                            data-expiry_date="{{ optional($item->member->expiry_date)->format('Y-m-d') ?? '' }}"
                                                            data-start_hold="{{ optional($item->member->start_hold)->format('Y-m-d') ?? '' }}"
                                                            data-industry="{{ $item->member->industry }}"
                                                            data-area_officer="{{ $item->member->area_officer }}"
                                                            data-area="{{ $item->member->area }}"
                                                            data-status="{{ $item->member->status }}"
                                                            data-branch_id="{{ $item->member->branch_id }}"
                                                            data-additional_address="{{ $item->member->additional_address }}"
                                                            data-account_status="{{ $item->member->account_status }}"
                                                            data-loans='{!! json_encode($item->member->loan_forecasts_data) !!}'>
                                                            Edit
                                                        </button>


                                                        <button type="button" class="btn btn-rounded btn-info"
                                                            data-toggle="modal" data-target="#viewModal"
                                                            data-fname="{{ $item->member->fname }}"
                                                            data-lname="{{ $item->member->lname }}"
                                                            data-cid="{{ $item->member->cid }}"
                                                            data-emp_id="{{ $item->member->emp_id }}"
                                                            data-address="{{ e($item->member->address) }}"
                                                            data-branch="{{ $item->branch->name }}"
                                                            data-savings_balance="{{ $item->member->savingsBalance }}"
                                                            data-share_balance="{{ $item->member->shareBalance }}"
                                                            data-loan_balance="{{ $item->member->loan_balance }}"
                                                            data-birth_date="{{ optional($item->member->birth_date)->format('Y-m-d') }}"
                                                            data-date_registered="{{ optional($item->member->date_registered)->format('Y-m-d') }}"
                                                            data-gender="{{ $item->member->gender }}"
                                                            data-customer_type="{{ $item->member->customer_type }}"
                                                            data-customer_classification="{{ $item->member->customer_classification }}"
                                                            data-occupation="{{ $item->member->occupation }}"
                                                            data-industry="{{ $item->member->industry }}"
                                                            data-area_officer="{{ $item->member->area_officer }}"
                                                            data-area="{{ $item->member->area }}"
                                                            data-status="{{ $item->member->status }}"
                                                            data-additional_address="{{ e($item->member->additional_address) }}"
                                                            data-account_status="{{ $item->member->account_status }}"
                                                            data-loans='@json($item->member->loan_forecasts_data)'>
                                                            View
                                                        </button>


                                                        <button type="button" class="btn btn-rounded btn-danger"
                                                            data-toggle="modal" data-target="#deleteModal"
                                                            data-id="{{ $item->member->id }}">Delete</button>

                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>


                                        <tfoot>
                                            <tr>
                                                <th>CID</th>
                                                <th>Name</th>
                                                <th>Branch</th>
                                                <th>Savings</th>
                                                <th>Share Balance</th>
                                                <th>Loan Balance</th>
                                                <th>Loan Accounts</th>
                                                <th>Actions</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-lg" role="document">
                                    <form id="editForm" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Member</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <h5 class="member-profile">Member Profile</h5>
                                            <div class="modal-body row">

                                                <input type="hidden" name="id" id="edit-id">
                                                <div class="form-group col-md-6">
                                                    <label for="edit-cid">CID</label>
                                                    <input type="text" class="form-control" name="cid"
                                                        id="edit-cid">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-emp_id">Employee ID</label>
                                                    <input type="text" class="form-control" name="emp_id"
                                                        id="edit-emp_id">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-fname">First Name</label>
                                                    <input type="text" class="form-control" name="fname"
                                                        id="edit-fname">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-lname">Last Name</label>
                                                    <input type="text" class="form-control" name="lname"
                                                        id="edit-lname">
                                                </div>

                                                <div class="form-group col-md-12">
                                                    <label for="edit-address">Address</label>
                                                    <textarea class="form-control" name="address" id="edit-address"></textarea>
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-birth_date">Birth Date</label>
                                                    <input type="date" class="form-control" name="birth_date"
                                                        id="edit-birth_date">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-date_registered">Date Registered</label>
                                                    <input type="date" class="form-control" name="date_registered"
                                                        id="edit-date_registered">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-gender">Gender</label>
                                                    <select class="form-control" name="gender" id="edit-gender">
                                                        <option value="">Select</option>
                                                        <option value="male">Male</option>
                                                        <option value="female">Female</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-customer_type">Customer Type</label>
                                                    <input type="text" class="form-control" name="customer_type"
                                                        id="edit-customer_type">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-customer_classification">Classification</label>
                                                    <input type="text" class="form-control"
                                                        name="customer_classification"
                                                        id="edit-customer_classification">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-occupation">Occupation</label>
                                                    <input type="text" class="form-control" name="occupation"
                                                        id="edit-occupation">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-industry">Industry</label>
                                                    <input type="text" class="form-control" name="industry"
                                                        id="edit-industry">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-area_officer">Area Officer</label>
                                                    <input type="text" class="form-control" name="area_officer"
                                                        id="edit-area_officer">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-area">Area</label>
                                                    <input type="text" class="form-control" name="area"
                                                        id="edit-area">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-status">Status</label>
                                                    <select class="form-control" name="status" id="edit-status">
                                                        <option value="active">Active</option>
                                                        <option value="merged">Merged</option>
                                                    </select>
                                                </div>



                                                <div class="form-group col-md-6">
                                                    <label for="edit-branch_id">Branch</label>
                                                    <select class="form-control" id="edit-branch_id"
                                                        name="branch_id">
                                                        <option value="">Select Branch</option>
                                                        @foreach ($branches as $branch)
                                                            <option value="{{ $branch->id }}">{{ $branch->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>


                                                <div class="form-group col-md-6">
                                                    <label for="edit-savings_balance">Savings</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        name="savings_balance" id="edit-savings_balance">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-share_balance">Shares</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        name="share_balance" id="edit-share_balance">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-loan_balance">Loan Balance</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        name="loan_balance" id="edit-loan_balance">
                                                </div>

                                                <div class="form-group col-md-12">
                                                    <h5>Deduction Settings</h5>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-6">
                                                            <label for="edit-account_status">Request for Hold</label>
                                                            <select class="form-control" name="account_status"
                                                                id="edit-account_status">
                                                                <option value="">Select</option>
                                                                <option value="deduction">Deduction</option>
                                                                <option value="non-deduction">Non-Deduction</option>
                                                            </select>
                                                        </div>

                                                        <div class="form-group col-md-6">
                                                            <label for="edit-approval_no">Approval Number</label>
                                                            <input type="text" class="form-control" name="approval_no"
                                                                id="edit-approval_no">
                                                        </div>

                                                         <div class="form-group col-md-6">
                                                            <label for="edit-start_hold">Start Hold</label>
                                                            <input type="date" class="form-control"
                                                                name="start_hold" id="edit-start_hold">
                                                        </div>

                                                        <div class="form-group col-md-6">
                                                            <label for="edit-expiry_date">Expiry Date</label>
                                                            <input type="date" class="form-control"
                                                                name="expiry_date" id="edit-expiry_date">
                                                        </div>

                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <h5>All Loans</h5>
                                                    <div id="loan-counter" class="mb-2 font-weight-bold"></div>
                                                    <div id="edit-loan-forecast-container"></div>
                                                </div>

                                            </div>

                                            <div class="modal-footer d-flex justify-content-between">
                                                <div>
                                                    <button type="button" class="btn btn-secondary"
                                                        id="btnPrev">Previous</button>
                                                    <button type="button" class="btn btn-secondary"
                                                        id="btnNext">Next</button>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>

                                        </div>
                                    </form>


                                </div>


                            </div>



                            <div class="modal fade" id="viewModal" tabindex="-1" role="dialog"
                                aria-labelledby="viewModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document"> <!-- larger modal for space -->
                                    <div class="modal-content">
                                        <div class="modal-header text-dark">
                                            <h5 class="modal-title" id="viewModalLabel">Member Details</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="container-fluid">
                                                <div class="row g-3">
                                                    <!-- Member Info Column -->
                                                    <div class="col-md-6">
                                                        <h6>Personal Information</h6>
                                                        <p><strong>First Name:</strong> <span id="view-fname"></span>
                                                        </p>
                                                        <p><strong>Last Name:</strong> <span id="view-lname"></span>
                                                        </p>
                                                        <p><strong>CID:</strong> <span id="view-cid"></span></p>
                                                        <p><strong>Employee ID:</strong> <span id="view-emp_id"></span>
                                                        </p>
                                                        <p><strong>Address:</strong> <span id="view-address"></span>
                                                        </p>
                                                        <p><strong>Additional Address:</strong> <span
                                                                id="view-additional_address"></span></p>
                                                        <p><strong>Branch:</strong> <span id="view-branch"></span></p>
                                                        <p><strong>Area Officer:</strong> <span
                                                                id="view-area_officer"></span></p>
                                                        <p><strong>Area:</strong> <span id="view-area"></span></p>
                                                        <p><strong>Birth Date:</strong> <span
                                                                id="view-birth_date"></span></p>
                                                        <p><strong>Date Registered:</strong> <span
                                                                id="view-date_registered"></span></p>
                                                    </div>

                                                    <!-- Account Info Column -->
                                                    <div class="col-md-6">
                                                        <h6>Account Details</h6>
                                                        <p><strong>Gender:</strong> <span id="view-gender"></span></p>
                                                        <p><strong>Customer Type:</strong> <span
                                                                id="view-customer_type"></span></p>
                                                        <p><strong>Customer Classification:</strong> <span
                                                                id="view-customer_classification"></span></p>
                                                        <p><strong>Occupation:</strong> <span
                                                                id="view-occupation"></span></p>
                                                        <p><strong>Industry:</strong> <span id="view-industry"></span>
                                                        </p>
                                                        <p><strong>Status:</strong> <span id="view-status"></span></p>
                                                        <p><strong>Account Status:</strong> <span
                                                                id="view-account_status"></span></p>
                                                        <p><strong>Savings Balance:</strong> <span
                                                                id="view-savings_balance"></span></p>
                                                        <p><strong>Share Balance:</strong> <span
                                                                id="view-share_balance"></span></p>
                                                        <p><strong>Loan Balance:</strong> <span
                                                                id="view-loan_balance"></span></p>
                                                    </div>
                                                </div>

                                                <hr>

                                                <div>
                                                    <h6>Loan Details</h6>
                                                    <div id="loan-account-numbers" style="min-height: 150px;">
                                                        <!-- Loan details will be injected here -->
                                                    </div>

                                                    <div class="d-flex justify-content-between mt-2">
                                                        <button id="loan-prev" class="btn btn-sm btn-outline-primary"
                                                            disabled>Previous</button>
                                                        <button id="loan-next" class="btn btn-sm btn-outline-primary"
                                                            disabled>Next</button>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <form id="deleteForm" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Delete Member</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete this member?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-danger">Delete</button>
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
                                    Showing {{ $masterlists->firstItem() }} to {{ $masterlists->lastItem() }} of
                                    {{ $masterlists->total() }} results
                                </div>
                                <nav aria-label="Page navigation" class="mt-3">
                                    {{ $masterlists->links('pagination::bootstrap-5') }}
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

    <script src="./vendor/global/global.min.js"></script>
    <script src="./js/quixnav-init.js"></script>
    <script src="./js/custom.min.js"></script>
    <script src="./vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="./js/plugins-init/datatables.init.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('error') }}'
            });
        </script>
    @endif


    <script>
        let loans = []; // Will hold loans for current modal
        let currentLoanIndex = 0;

        $('#editModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            // Fill member fields as before
            $('#edit-id').val(button.data('id'));
            $('#edit-cid').val(button.data('cid'));
            $('#edit-emp_id').val(button.data('emp_id'));
            $('#edit-fname').val(button.data('fname'));
            $('#edit-lname').val(button.data('lname'));
            $('#edit-address').val(button.data('address'));
            $('#edit-birth_date').val(button.data('birth_date'));
            $('#edit-date_registered').val(button.data('date_registered'));
            $('#edit-gender').val(button.data('gender'));
            $('#edit-customer_type').val(button.data('customer_type'));
            $('#edit-customer_classification').val(button.data('customer_classification'));
            $('#edit-occupation').val(button.data('occupation'));
            $('#edit-industry').val(button.data('industry'));
            $('#edit-area_officer').val(button.data('area_officer'));
            $('#edit-area').val(button.data('area'));
            $('#edit-status').val(button.data('status'));
            $('#edit-branch_id').val(button.data('branch_id'));
            $('#edit-savings_balance').val(button.data('savings_balance'));
            $('#edit-share_balance').val(button.data('share_balance'));
            $('#edit-loan_balance').val(button.data('loan_balance'));
            $('#edit-billing_period').val(button.data('billing_period'));
            $('#edit-account_status').val(button.data('account_status'));
            $('#edit-approval_no').val(button.data('approval_no'));
            $('#edit-expiry_date').val(button.data('expiry_date'));
            $('#edit-start_hold').val(button.data('start_hold'));

            // Clear loans container
            loans = button.data('loans') || [];
            if (typeof loans === 'string') {
                try {
                    loans = JSON.parse(loans);
                } catch {
                    loans = [];
                }
            }

            currentLoanIndex = 0; // Reset index on modal show

            // Render the first loan or empty loan if none
            renderLoan(currentLoanIndex);

            // Set form action dynamically
            $('#editForm').attr('action', '/members/' + button.data('id'));

            updateNavButtons();
        });

        // Render loan at given index in container (overwrite existing)
        function renderLoan(index) {
            $('#edit-loan-forecast-container').empty();

            let loan = loans.length > 0 ? loans[index] : {};

            let html = `
    <div class="loan-item border p-3 mb-3 rounded position-relative">

        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Loan Account No.</label>
                <input type="text" name="loan_forecasts[${index}][loan_acct_no]" class="form-control" value="${loan.loan_acct_no || ''}">
            </div>
            <div class="form-group col-md-6">
                <label>Total Due</label>
                <input type="text" name="loan_forecasts[${index}][total_due]" class="form-control" value="${loan.total_due || ''}">
            </div>
            <div class="form-group col-md-6">
                <label>Amount Due</label>
                <input type="number" step="0.01" name="loan_forecasts[${index}][amount_due]" class="form-control" value="${loan.amount_due || ''}">
            </div>
            <div class="form-group col-md-6">
                <label>Open Date</label>
                <input type="date" name="loan_forecasts[${index}][open_date]" class="form-control" value="${loan.open_date || ''}">
            </div>
            <div class="form-group col-md-6">
                <label>Maturity Date</label>
                <input type="date" name="loan_forecasts[${index}][maturity_date]" class="form-control" value="${loan.maturity_date || ''}">
            </div>
            <div class="form-group col-md-6">
                <label>Amortization Due Date</label>
                <input type="date" name="loan_forecasts[${index}][amortization_due_date]" class="form-control" value="${loan.amortization_due_date || ''}">
            </div>
            <div class="form-group col-md-6">
                <label>Principal Due</label>
                <input type="number" step="0.01" name="loan_forecasts[${index}][principal_due]" class="form-control" value="${loan.principal_due || ''}">
            </div>
            <div class="form-group col-md-6">
                <label>Interest Due</label>
                <input type="number" step="0.01" name="loan_forecasts[${index}][interest_due]" class="form-control" value="${loan.interest_due || ''}">
            </div>
            <div class="form-group col-md-6">
                <label>Penalty Due</label>
                <input type="number" step="0.01" name="loan_forecasts[${index}][penalty_due]" class="form-control" value="${loan.penalty_due || ''}">
            </div>
        </div>
    </div>`;

            $('#edit-loan-forecast-container').html(html);

            if (loans.length === 0) {
                $('#loan-counter').text('No loans. You can add one.');
            } else {
                $('#loan-counter').text(`Loan ${index + 1} of ${loans.length}`);
            }
        }


        function updateNavButtons() {
            $('#btnPrev').prop('disabled', currentLoanIndex <= 0);
            $('#btnNext').prop('disabled', currentLoanIndex >= loans.length - 1);
        }




        // Button click handlers
        $('#btnNext').click(function() {
            if (currentLoanIndex < loans.length - 1) {
                currentLoanIndex++;
                renderLoan(currentLoanIndex);
                updateNavButtons();
            }
        });

        $('#btnPrev').click(function() {
            if (currentLoanIndex > 0) {
                currentLoanIndex--;
                renderLoan(currentLoanIndex);
                updateNavButtons();
            }
        });




        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            // Set member info fields
            $('#view-fname').text(button.data('fname'));
            $('#view-lname').text(button.data('lname'));
            $('#view-cid').text(button.data('cid'));
            $('#view-emp_id').text(button.data('emp_id'));
            $('#view-address').text(button.data('address'));
            $('#view-branch').text(button.data('branch'));
            $('#view-savings_balance').text(button.data('savings_balance'));
            $('#view-share_balance').text(button.data('share_balance'));
            $('#view-loan_balance').text(button.data('loan_balance'));
            $('#view-birth_date').text(button.data('birth_date'));
            $('#view-date_registered').text(button.data('date_registered'));
            $('#view-gender').text(button.data('gender'));
            $('#view-customer_type').text(button.data('customer_type'));
            $('#view-customer_classification').text(button.data('customer_classification'));
            $('#view-occupation').text(button.data('occupation'));
            $('#view-industry').text(button.data('industry'));
            $('#view-area_officer').text(button.data('area_officer'));
            $('#view-area').text(button.data('area'));
            $('#view-status').text(button.data('status'));
            $('#view-additional_address').text(button.data('additional_address'));
            $('#view-account_status').text(button.data('account_status'));

            // Loans navigation logic
            var loans = button.data('loans') || [];
            var currentIndex = 0;

            // Cache buttons and container
            var $loanNumbers = $('#loan-account-numbers');
            var $btnPrev = $('#loan-prev');
            var $btnNext = $('#loan-next');

            function renderLoan(index) {
                var loan = loans[index];
                if (!loan) {
                    $loanNumbers.html('<p>No loan accounts found.</p>');
                    $btnPrev.prop('disabled', true);
                    $btnNext.prop('disabled', true);
                    return;
                }

                // Simple, clean loan info display
                var html = `
            <p><strong>Loan Account No.:</strong> ${loan.loan_acct_no || 'N/A'}</p>
            <p><strong>Amount Due:</strong> ${loan.amount_due || 'N/A'}</p>
            <p><strong>Open Date:</strong> ${loan.open_date || 'N/A'}</p>
            <p><strong>Maturity Date:</strong> ${loan.maturity_date || 'N/A'}</p>
            <p><strong>Amortization Due Date:</strong> ${loan.amortization_due_date || 'N/A'}</p>
            <p><strong>Total Due:</strong> ${loan.total_due || 'N/A'}</p>
            <p><strong>Principal Due:</strong> ${loan.principal_due || 'N/A'}</p>
            <p><strong>Interest Due:</strong> ${loan.interest_due || 'N/A'}</p>
            <p><strong>Penalty Due:</strong> ${loan.penalty_due || 'N/A'}</p>
            <p><em>Loan ${index + 1} of ${loans.length}</em></p>
        `;
                $loanNumbers.html(html);

                // Enable/disable buttons based on index
                $btnPrev.prop('disabled', index === 0);
                $btnNext.prop('disabled', index === loans.length - 1);
            }

            // Initial render
            if (loans.length === 0) {
                $loanNumbers.html('<p>No loan accounts found.</p>');
                $btnPrev.prop('disabled', true);
                $btnNext.prop('disabled', true);
            } else {
                renderLoan(currentIndex);
            }

            // Button click handlers
            $btnPrev.off('click').on('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                    renderLoan(currentIndex);
                }
            });
            $btnNext.off('click').on('click', function() {
                if (currentIndex < loans.length - 1) {
                    currentIndex++;
                    renderLoan(currentIndex);
                }
            });
        });





        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            $('#deleteForm').attr('action', '/members/' + id);
        });
    </script>

    @if ($errors->any())
        <script>
            $(function() {
                let oldLoans = @json(old('loan_forecasts', []));
                loans = oldLoans;
                currentLoanIndex = 0;
                renderLoan(currentLoanIndex);
                updateNavButtons();

                $('#editModal').modal('show');

                Swal.fire({
                    icon: 'error',
                    title: 'Validation Failed',
                    html: '{!! implode('<br>', $errors->all()) !!}'
                });
            });
        </script>
    @endif
</body>

</html>
