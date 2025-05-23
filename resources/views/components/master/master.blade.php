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
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>CID</th>
                                                <th>Name</th>
                                                <th>Branch</th>
                                                <th>Status</th>
                                                <th>Account Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($masterlists as $item)
                                                <tr>
                                                    <td>{{ $item->member->cid ?? '' }}</td>
                                                    <td>{{ $item->member->lname ?? '' }},
                                                        {{ $item->member->fname ?? '' }}</td>
                                                    <td>{{ $item->branch->name ?? '' }}</td>
                                                    <td>{{ $item->member->status ?? '' }}</td>
                                                    <td>{{ $item->member->account_status ?? 'N/A' }}</td>
                                                    <td>
                                                        <button type="button" class="btn btn-rounded btn-primary"
                                                            data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $item->member->id }}"
                                                            data-cid="{{ $item->member->cid }}"
                                                            data-emp_id="{{ $item->member->emp_id }}"
                                                            data-fname="{{ $item->member->fname }}"
                                                            data-lname="{{ $item->member->lname }}"
                                                            data-address="{{ $item->member->address }}"
                                                            data-savings_balance="{{ $item->member->savings_balance }}"
                                                            data-share_balance="{{ $item->member->share_balance }}"
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
                                                            data-branch_id="{{ $item->member->branch_id }}"
                                                            data-additional_address="{{ $item->member->additional_address }}"
                                                            data-account_status="{{ $item->member->account_status }}">

                                                            Edit </button>


                                                        <button type="button" class="btn btn-rounded btn-info"
                                                            data-toggle="modal" data-target="#viewModal"
                                                            data-fname="{{ $item->member->fname }}"
                                                            data-lname="{{ $item->member->lname }}"
                                                            data-cid="{{ $item->member->cid }}"
                                                            data-emp_id="{{ $item->member->emp_id }}"
                                                            data-address="{{ e($item->member->address) }}"
                                                            data-savings_balance="{{ $item->member->savings_balance }}"
                                                            data-branch="{{ $item->branch->name }}"
                                                            data-share_balance="{{ $item->member->share_balance }}"
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
                                                            data-account_status="{{ $item->member->account_status }}">
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
                                                <th>Status</th>
                                                <th>Account Status</th>
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

                                                <div class="form-group col-md-12">
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


                                                <div class="form-group col-md-4">
                                                    <label for="edit-savings_balance">Savings</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        name="savings_balance" id="edit-savings_balance">
                                                </div>

                                                <div class="form-group col-md-4">
                                                    <label for="edit-share_balance">Shares</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        name="share_balance" id="edit-share_balance">
                                                </div>

                                                <div class="form-group col-md-4">
                                                    <label for="edit-loan_balance">Loan</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        name="loan_balance" id="edit-loan_balance">
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
                                                    <label for="edit-account_status">Account Status</label>
                                                    <select class="form-control" name="account_status"
                                                        id="edit-account_status">
                                                        <option value="">Select</option>
                                                        <option value="deduction">Deduction</option>
                                                        <option value="non-deduction">Non-Deduction</option>
                                                    </select>
                                                </div>

                                                <div class="form-group col-md-12">
                                                    <label for="edit-additional_address">Additional Address</label>
                                                    <textarea class="form-control" name="additional_address" id="edit-additional_address"></textarea>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>

                                        </div>
                                    </form>
                                </div>
                            </div>



                            <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-lg" role="document"> <!-- larger modal for space -->
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">View Member Details</h5>
                                            <button type="button" class="close"
                                                data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="container-fluid">
                                                <div class="row">
                                                    <!-- First Column -->
                                                    <div class="col-md-6">
                                                        <p><strong>First Name:</strong> <span id="view-fname"></span>
                                                        </p>
                                                        <p><strong>Last Name:</strong> <span id="view-lname"></span>
                                                        </p>
                                                        <p><strong>CID:</strong> <span id="view-cid"></span></p>
                                                        <p><strong>Employee ID:</strong> <span id="view-emp_id"></span>
                                                        </p>
                                                        <p><strong>Address:</strong> <span id="view-address"></span>
                                                        </p>
                                                        <p><strong>Branch:</strong> <span id="view-branch"></span>
                                                        </p>
                                                        <p><strong>Savings Balance:</strong> <span
                                                                id="view-savings_balance"></span></p>
                                                        <p><strong>Share Balance:</strong> <span
                                                                id="view-share_balance"></span></p>
                                                        <p><strong>Loan Balance:</strong> <span
                                                                id="view-loan_balance"></span></p>
                                                        <p><strong>Birth Date:</strong> <span
                                                                id="view-birth_date"></span></p>
                                                        <p><strong>Date Registered:</strong> <span
                                                                id="view-date_registered"></span></p>
                                                    </div>
                                                    <!-- Second Column -->
                                                    <div class="col-md-6">
                                                        <p><strong>Gender:</strong> <span id="view-gender"></span></p>
                                                        <p><strong>Customer Type:</strong> <span
                                                                id="view-customer_type"></span></p>
                                                        <p><strong>Customer Classification:</strong> <span
                                                                id="view-customer_classification"></span></p>
                                                        <p><strong>Occupation:</strong> <span
                                                                id="view-occupation"></span></p>
                                                        <p><strong>Industry:</strong> <span id="view-industry"></span>
                                                        </p>
                                                        <p><strong>Area Officer:</strong> <span
                                                                id="view-area_officer"></span></p>
                                                        <p><strong>Area:</strong> <span id="view-area"></span></p>
                                                        <p><strong>Status:</strong> <span id="view-status"></span></p>
                                                        <p><strong>Additional Address:</strong> <span
                                                                id="view-additional_address"></span></p>
                                                        <p><strong>Account Status:</strong> <span
                                                                id="view-account_status"></span></p>
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
        $('#editModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            // List all fields to set
            $('#edit-id').val(button.data('id'));
            $('#edit-cid').val(button.data('cid'));
            $('#edit-emp_id').val(button.data('emp_id'));
            $('#edit-fname').val(button.data('fname'));
            $('#edit-lname').val(button.data('lname'));
            $('#edit-address').val(button.data('address'));
            $('#edit-savings_balance').val(button.data('savings_balance'));
            $('#edit-share_balance').val(button.data('share_balance'));
            $('#edit-loan_balance').val(button.data('loan_balance'));
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
            $('#edit-additional_address').val(button.data('additional_address'));
            $('#edit-account_status').val(button.data('account_status'));
            $('#edit-branch_id').val(button.data('branch_id'));
            $('#editForm').attr('action', '/members/' + button.data('id'));
        });


        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
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
        });


        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            $('#deleteForm').attr('action', '/members/' + id);
        });
    </script>


</body>

</html>
