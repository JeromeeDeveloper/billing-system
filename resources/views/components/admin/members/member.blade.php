<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Billing and Collection</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">

    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
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
                            <h4>Members</h4>
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
                            <style>
                                tr {
                                    text-align: center;
                                }

                                .card-header {
                                    background-color: #f8f9fa;
                                    border-bottom: 1px solid rgba(0,0,0,.125);
                                }

                                .card-title {
                                    color: #2c3e50;
                                    font-weight: 600;
                                    margin: 0;
                                }

                                .btn-success {
                                    background-color: #2ecc71;
                                    border-color: #27ae60;
                                    transition: all 0.3s ease;
                                }

                                .btn-success:hover {
                                    background-color: #27ae60;
                                    border-color: #219a52;
                                    transform: translateY(-1px);
                                    box-shadow: 0 4px 6px rgba(0,0,0,.1);
                                }

                                .input-group {
                                    box-shadow: 0 2px 4px rgba(0,0,0,.04);
                                }

                                .form-control:focus {
                                    border-color: #3498db;
                                    box-shadow: 0 0 0 0.2rem rgba(52,152,219,.25);
                                }

                                .btn-primary {
                                    background-color: #3498db;
                                    border-color: #2980b9;
                                }

                                .btn-primary:hover {
                                    background-color: #2980b9;
                                    border-color: #2472a4;
                                }
                            </style>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <form method="GET" action="{{ url()->current() }}" class="d-flex">
                                        <div class="input-group" style="width: 300px;">
                                            <input type="text" name="search" value="{{ request('search') }}"
                                                class="form-control" placeholder="Search by CID, Name, Branch..." />
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fa fa-search"></i> Search
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addModal">
                                        <i class="fa fa-plus-circle"></i> Add New Member
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="display table table-striped" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Branch</th>
                                                <th>CID</th>

                                                <th>Address</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($members as $member)
                                                <tr>
                                                    <td>{{ $member->id }}</td>
                                                    <td>{{ $member->fname }} {{ $member->lname }}</td>
                                                    <td>{{ $member->branch?->name ?? 'N/A' }}</td>
                                                    <td>{{ $member->cid }}</td>

                                                    <td>{{ $member->address }}</td>
                                                    <td>
                                                        <!-- Edit Button -->
                                                        <button type="button" class="btn btn-rounded btn-primary"
                                                            data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $member->id }}"
                                                            data-fname="{{ $member->fname }}"
                                                            data-lname="{{ $member->lname }}"
                                                            data-cid="{{ $member->cid }}"
                                                            data-branch="{{ $member->branch_id }}"
                                                            data-savings_balance="{{ $member->savings_balance }}"
                                                            data-share_balance="{{ $member->share_balance }}">
                                                            Edit
                                                        </button>

                                                        <!-- View Button -->
                                                        <button class="btn btn-rounded btn-info" data-toggle="modal"
                                                            data-target="#viewModal" data-id="{{ $member->id }}"
                                                            data-fname="{{ $member->fname }}"
                                                            data-lname="{{ $member->lname }}"
                                                            data-branch="{{ $member->branch?->name ?? 'N/A' }}"
                                                            data-cid="{{ $member->cid }}"
                                                            data-emp_id="{{ $member->emp_id }}"
                                                            data-address="{{ $member->address }}"
                                                            data-savings="{{ $member->savings_balance }}"
                                                            data-share="{{ $member->share_balance }}"
                                                            data-loan="{{ $member->loan_balance }}">
                                                            View
                                                        </button>

                                                        <!-- Delete Button -->
                                                        <button class="btn btn-rounded btn-danger" data-toggle="modal"
                                                            data-target="#deleteModal"
                                                            data-id="{{ $member->id }}">Delete</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>

                                        <tfoot>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Branch</th>
                                                <th>CID</th>
                                                <th>Address</th>
                                                <th>Actions</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <style>
                                p.small.text-muted {
                                    display: none;
                                }
                            </style>

                            <div class="d-flex flex-column align-items-center my-4">
                                <div>
                                    Showing {{ $members->firstItem() }} to {{ $members->lastItem() }} of
                                    {{ $members->total() }} results
                                </div>
                                <nav aria-label="Page navigation" class="mt-3">
                                    {{ $members->links('pagination::bootstrap-5') }}
                                </nav>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/" target="_blank">MASS-SPECC
                        COOPERATIVE</a>2025</p>
            </div>
        </div>

    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <form action="{{ route('members.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">Add New Member</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="container-fluid">
                            <!-- Personal Information -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="text-primary">Personal Information</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Branch</label>
                                        <select class="form-control" name="branch_id" required>
                                            <option value="">Select Branch</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>CID</label>
                                        <input type="text" name="cid" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Employee ID</label>
                                        <input type="text" name="emp_id" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input type="text" name="fname" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Last Name</label>
                                        <input type="text" name="lname" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="text-primary">Contact Information</h6>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Address</label>
                                        <textarea name="address" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Additional Address</label>
                                        <textarea name="additional_address" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="text-primary">Additional Information</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Birth Date</label>
                                        <input type="date" name="birth_date" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Date Registered</label>
                                        <input type="date" name="date_registered" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <select class="form-control" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Professional Information -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="text-primary">Professional Information</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Customer Type</label>
                                        <input type="text" name="customer_type" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Customer Classification</label>
                                        <input type="text" name="customer_classification" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Occupation</label>
                                        <input type="text" name="occupation" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Industry</label>
                                        <input type="text" name="industry" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Area Officer</label>
                                        <input type="text" name="area_officer" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Area</label>
                                        <input type="text" name="area" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Information -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="text-primary">Financial Information</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Savings Balance</label>
                                        <input type="number" step="0.01" name="savings_balance" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Share Balance</label>
                                        <input type="number" step="0.01" name="share_balance" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Loan Balance</label>
                                        <input type="number" step="0.01" name="loan_balance" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Account Settings -->
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary">Account Settings</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Account Status</label>
                                        <select class="form-control" name="account_status">
                                            <option value="">Select Status</option>
                                            <option value="deduction">Deduction</option>
                                            <option value="non-deduction">Non-Deduction</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Approval Number</label>
                                        <input type="text" name="approval_no" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Start Hold</label>
                                        <input type="month" name="start_hold" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Expiry Date</label>
                                        <input type="month" name="expiry_date" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Member</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Member</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="container-fluid">
                            <!-- Personal Information -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="text-primary">Personal Information</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Branch</label>
                                        <select class="form-control" name="branch_id" id="edit-branch_id" required>
                                            <option value="">Select Branch</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>CID</label>
                                        <input type="text" name="cid" id="edit-cid" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Employee ID</label>
                                        <input type="text" name="emp_id" id="edit-emp_id" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input type="text" name="fname" id="edit-fname" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Last Name</label>
                                        <input type="text" name="lname" id="edit-lname" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="text-primary">Contact Information</h6>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Address</label>
                                        <textarea name="address" id="edit-address" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Additional Address</label>
                                        <textarea name="additional_address" id="edit-additional_address" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="text-primary">Additional Information</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Birth Date</label>
                                        <input type="date" name="birth_date" id="edit-birth_date" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Date Registered</label>
                                        <input type="date" name="date_registered" id="edit-date_registered" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <select class="form-control" name="gender" id="edit-gender">
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Professional Information -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="text-primary">Professional Information</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Customer Type</label>
                                        <input type="text" name="customer_type" id="edit-customer_type" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Customer Classification</label>
                                        <input type="text" name="customer_classification" id="edit-customer_classification" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Occupation</label>
                                        <input type="text" name="occupation" id="edit-occupation" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Industry</label>
                                        <input type="text" name="industry" id="edit-industry" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Area Officer</label>
                                        <input type="text" name="area_officer" id="edit-area_officer" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Area</label>
                                        <input type="text" name="area" id="edit-area" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Information -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <h6 class="text-primary">Financial Information</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Savings Balance</label>
                                        <input type="number" step="0.01" name="savings_balance" id="edit-savings_balance" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Share Balance</label>
                                        <input type="number" step="0.01" name="share_balance" id="edit-share_balance" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Loan Balance</label>
                                        <input type="number" step="0.01" name="loan_balance" id="edit-loan_balance" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Account Settings -->
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary">Account Settings</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Account Status</label>
                                        <select class="form-control" name="account_status" id="edit-account_status">
                                            <option value="">Select Status</option>
                                            <option value="deduction">Deduction</option>
                                            <option value="non-deduction">Non-Deduction</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Approval Number</label>
                                        <input type="text" name="approval_no" id="edit-approval_no" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Start Hold</label>
                                        <input type="month" name="start_hold" id="edit-start_hold" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Expiry Date</label>
                                        <input type="month" name="expiry_date" id="edit-expiry_date" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Member Details</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <!-- Personal Information -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="text-primary">Personal Information</h6>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Full Name:</strong> <span id="view-full-name"></span></p>
                                <p><strong>CID:</strong> <span id="view-cid"></span></p>
                                <p><strong>Employee ID:</strong> <span id="view-emp_id"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Branch:</strong> <span id="view-branch"></span></p>
                                <p><strong>Birth Date:</strong> <span id="view-birth_date"></span></p>
                                <p><strong>Date Registered:</strong> <span id="view-date_registered"></span></p>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="text-primary">Contact Information</h6>
                                <p><strong>Address:</strong> <span id="view-address"></span></p>
                                <p><strong>Additional Address:</strong> <span id="view-additional_address"></span></p>
                            </div>
                        </div>

                        <!-- Professional Information -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="text-primary">Professional Information</h6>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Customer Type:</strong> <span id="view-customer_type"></span></p>
                                <p><strong>Customer Classification:</strong> <span id="view-customer_classification"></span></p>
                                <p><strong>Occupation:</strong> <span id="view-occupation"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Industry:</strong> <span id="view-industry"></span></p>
                                <p><strong>Area Officer:</strong> <span id="view-area_officer"></span></p>
                                <p><strong>Area:</strong> <span id="view-area"></span></p>
                            </div>
                        </div>

                        <!-- Financial Information -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="text-primary">Financial Information</h6>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Savings Balance:</strong> ₱<span id="view-savings_balance"></span></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Share Balance:</strong> ₱<span id="view-share_balance"></span></p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>Loan Balance:</strong> ₱<span id="view-loan_balance"></span></p>
                            </div>
                        </div>

                        <!-- Account Settings -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary">Account Settings</h6>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Account Status:</strong> <span id="view-account_status"></span></p>
                                <p><strong>Approval Number:</strong> <span id="view-approval_no"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Start Hold:</strong> <span id="view-start_hold"></span></p>
                                <p><strong>Expiry Date:</strong> <span id="view-expiry_date"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Member</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this member? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="./vendor/global/global.min.js"></script>
    <script src="./js/quixnav-init.js"></script>
    <script src="./js/custom.min.js"></script>
    <script src="./vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="./js/plugins-init/datatables.init.js"></script>

    <script>
        // Edit Modal
        $('#editModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');

            $('#editForm').attr('action', '/members/' + id);
            $('#edit-fname').val(button.data('fname'));
            $('#edit-lname').val(button.data('lname'));
            $('#edit-cid').val(button.data('cid'));
            $('#edit-emp_id').val(button.data('emp_id'));
            $('#edit-branch_id').val(button.data('branch'));
            $('#edit-address').val(button.data('address'));
            $('#edit-additional_address').val(button.data('additional_address'));
            $('#edit-birth_date').val(button.data('birth_date'));
            $('#edit-date_registered').val(button.data('date_registered'));
            $('#edit-gender').val(button.data('gender'));
            $('#edit-customer_type').val(button.data('customer_type'));
            $('#edit-customer_classification').val(button.data('customer_classification'));
            $('#edit-occupation').val(button.data('occupation'));
            $('#edit-industry').val(button.data('industry'));
            $('#edit-area_officer').val(button.data('area_officer'));
            $('#edit-area').val(button.data('area'));
            $('#edit-savings_balance').val(button.data('savings_balance'));
            $('#edit-share_balance').val(button.data('share_balance'));
            $('#edit-loan_balance').val(button.data('loan_balance'));
            $('#edit-account_status').val(button.data('account_status'));
            $('#edit-approval_no').val(button.data('approval_no'));
            $('#edit-start_hold').val(button.data('start_hold'));
            $('#edit-expiry_date').val(button.data('expiry_date'));
        });

        // View Modal
        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#view-full-name').text(button.data('fname') + ' ' + button.data('lname'));
            $('#view-cid').text(button.data('cid'));
            $('#view-emp_id').text(button.data('emp_id'));
            $('#view-branch').text(button.data('branch'));
            $('#view-address').text(button.data('address'));
            $('#view-additional_address').text(button.data('additional_address'));
            $('#view-birth_date').text(button.data('birth_date'));
            $('#view-date_registered').text(button.data('date_registered'));
            $('#view-gender').text(button.data('gender'));
            $('#view-customer_type').text(button.data('customer_type'));
            $('#view-customer_classification').text(button.data('customer_classification'));
            $('#view-occupation').text(button.data('occupation'));
            $('#view-industry').text(button.data('industry'));
            $('#view-area_officer').text(button.data('area_officer'));
            $('#view-area').text(button.data('area'));
            $('#view-savings_balance').text(formatCurrency(button.data('savings_balance')));
            $('#view-share_balance').text(formatCurrency(button.data('share_balance')));
            $('#view-loan_balance').text(formatCurrency(button.data('loan_balance')));
            $('#view-account_status').text(button.data('account_status'));
            $('#view-approval_no').text(button.data('approval_no'));
            $('#view-start_hold').text(button.data('start_hold'));
            $('#view-expiry_date').text(button.data('expiry_date'));
        });

        // Delete Modal
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            $('#deleteForm').attr('action', '/members/' + id);
        });

        function formatCurrency(amount) {
            return parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
    </script>

</body>

</html>
