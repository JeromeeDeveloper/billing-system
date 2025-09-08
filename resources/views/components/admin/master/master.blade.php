<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Billing and Collection</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">

    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    {{-- <link href="{{ asset('icons/font-awesome-old/css/font-awesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset('icons/material-design-iconic-font/css/materialdesignicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('icons/line-awesome/css/line-awesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset('icons/simple-line-icons/css/simple-line-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('icons/themify-icons/css/themify-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('icons/avasta/css/style.css') }}" rel="stylesheet"> --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
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
                            <li class="breadcrumb-item active"><a href="{{ route('master') }}">Master List</a></li>
                        </ol>
                    </div>
                </div>

                <!-- Add Member Modal -->
                <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <form action="{{ route('members.store') }}" method="POST">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header text-dark">
                                    <h5 class="modal-title" id="addModalLabel">Add Member</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>

                                <div class="modal-body">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <!-- Personal Information Section -->
                                            <div class="col-12 mb-3">
                                                <h5>Personal Information</h5>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Branch</label>
                                                    <select class="form-control" name="branch_id">
                                                        <option value="">Select Branch</option>
                                                        @foreach ($branches as $branch)
                                                            <option value="{{ $branch->id }}">{{ $branch->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">CID</label>
                                                    <input type="text" name="cid" class="form-control" required>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Employee ID</label>
                                                    <input type="text" name="emp_id" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">First Name</label>
                                                    <input type="text" name="fname" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Last Name</label>
                                                    <input type="text" name="lname" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label">Address</label>
                                                    <textarea name="address" class="form-control" rows="2"></textarea>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Birth Date</label>
                                                    <input type="date" name="birth_date" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Date Registered</label>
                                                    <input type="date" name="date_registered" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Gender</label>
                                                    <select class="form-control" name="gender">
                                                        <option value="">Select Gender</option>
                                                        <option value="male">Male</option>
                                                        <option value="female">Female</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Additional Information Section -->
                                            <div class="col-12 mb-3 mt-4">
                                                <h5>Additional Information</h5>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Customer Type</label>
                                                    <input type="text" name="customer_type" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Customer Classification</label>
                                                    <input type="text" name="customer_classification"
                                                        class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Occupation</label>
                                                    <input type="text" name="occupation" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Industry</label>
                                                    <input type="text" name="industry" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Area Officer</label>
                                                    <input type="text" name="area_officer" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Area</label>
                                                    <input type="text" name="area" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Member Tagging</label>
                                                    <select class="form-control" name="member_tagging">
                                                        <option value="New" selected>New</option>
                                                        <option value="PGB">PGB</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label class="form-label">Remarks</label>
                                                    <textarea name="remarks" class="form-control" rows="2" placeholder="Remarks"></textarea>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-success">Save Member</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- End Add Member Modal -->

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-white border-0 px-3 py-3">
                                <div class="row w-100 align-items-center g-2">
                                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                                        <h4 class="card-title mb-0 text-primary">Member Datatable</h4>
                                        <small class="text-muted">Manage all members in the system</small>
                                    </div>
                                    <div class="col-12 col-md-8 d-flex flex-column align-items-md-end align-items-center">
                                        <form method="GET" action="{{ url()->current() }}" class="d-flex flex-grow-1 flex-md-grow-0 gap-2 align-items-center mb-2 mb-md-0 justify-content-md-end w-100" style="max-width: 400px;">
                                            <input type="text" name="search" value="{{ request('search') }}" class="form-control flex-grow-1 mr-2" placeholder="Search members..." />
                                            <button type="submit" class="btn btn-primary d-flex align-items-center px-3">
                                                <i class="fa fa-search me-2"></i> <span class="d-none d-md-inline">Search</span>
                                            </button>
                                        </form>

                                    </div>
                                    <div class="action-bar d-flex flex-wrap flex-md-nowrap gap-2 justify-content-center align-items-center p-2 rounded shadow-sm mt-2 w-100" style="background: #f8f9fa;">
                                        <a href="#" class="btn btn-primary d-flex align-items-center px-3 mr-2" data-toggle="modal" data-target="#addModal">
                                            <i class="fa fa-plus-circle me-2"></i><span class="d-none d-md-inline"> Add New Member</span>
                                        </a>

                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="masterlistTable" class="table table-striped table-bordered display">
                                        <thead>
                                            <tr>
                                                <th>CID</th>
                                                <th>EMPID</th>
                                                <th>Name</th>
                                                <th>Branch</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($masterlists->unique('member.id') as $item)
                                                <tr>
                                                    <td>{{ $item->member->cid ?? '' }}</td>
                                                    <td>{{ $item->member->emp_id ?? 'N/A' }}</td>
                                                    <td>{{ $item->member->lname ?? '' }},
                                                        {{ $item->member->fname ?? '' }}</td>
                                                    <td>{{ $item->member->branch ? $item->member->branch->name : 'N/A' }}</td>
                                                    <td>{{ $item->member->member_tagging ?? 'N/A' }}</td>


                                                    <td>


                                                        @php
                                                            $isEditDisabled = $isEditDisabledForAll ||
                                                                (!$isEditDisabledForAll && in_array($item->member->branch_id, $editDisabledBranches));
                                                        @endphp
                                                        <button type="button" class="btn btn-rounded btn-info"
                                                            data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $item->member->id }}"
                                                            data-cid="{{ $item->member->cid }}"
                                                            data-emp_id="{{ $item->member->emp_id }}"
                                                            data-fname="{{ $item->member->fname }}"
                                                            data-lname="{{ $item->member->lname }}"
                                                            data-address="{{ $item->member->address }}"
                                                            data-birth_date="{{ optional($item->member->birth_date)->format('Y-m-d') }}"
                                                            data-date_registered="{{ optional($item->member->date_registered)->format('Y-m-d') }}"
                                                            data-gender="{{ $item->member->gender }}"
                                                            data-customer_type="{{ $item->member->customer_type }}"
                                                            data-customer_classification="{{ $item->member->customer_classification }}"
                                                            data-occupation="{{ $item->member->occupation }}"
                                                            data-approval_no="{{ $item->member->approval_no }}"
                                                            data-expiry_date="{{ optional($item->member->expiry_date)->format('Y-m-d') }}"
                                                            data-start_hold="{{ optional($item->member->start_hold)->format('Y-m-d') }}"
                                                            data-account_status="{{ $item->member->account_status }}"
                                                            data-industry="{{ $item->member->industry }}"
                                                            data-area_officer="{{ $item->member->area_officer }}"
                                                            data-area="{{ $item->member->area }}"
                                                            data-status="{{ $item->member->status }}"
                                                            data-branch_id="{{ $item->member->branch_id }}"
                                                            data-additional_address="{{ $item->member->additional_address }}"
                                                            data-loans='{!! json_encode($item->member->loan_forecasts_data) !!}'
                                                            data-savings='{!! json_encode($item->member->savings) !!}'
                                                            data-shares='{!! json_encode($item->member->shares) !!}'
                                                            @if($isEditDisabled) disabled title="Edit disabled - Export has been generated" @endif>
                                                            Edit
                                                        </button>


                                                        <button type="button" class="btn btn-rounded btn-primary"
                                                            data-toggle="modal" data-target="#viewModal"
                                                            data-fname="{{ $item->member->fname }}"
                                                            data-lname="{{ $item->member->lname }}"
                                                            data-cid="{{ $item->member->cid }}"
                                                            data-emp_id="{{ $item->member->emp_id }}"
                                                            data-address="{{ e($item->member->address) }}"
                                                            data-branch="{{ $item->member->branch ? $item->member->branch->name : 'N/A' }}"
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
                                                            data-loans='@json($item->member->loan_forecasts_data)'
                                                            data-savings='@json($item->member->savings)'
                                                            data-shares='@json($item->member->shares)'>
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
                                                <th>EMPID</th>
                                                <th>Name</th>
                                                <th>Branch</th>
                                                <th>Status</th>
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
                                            <div class="modal-header text-dark">
                                                <h5 class="modal-title">
                                                    <i class="fa fa-edit me-2"></i>Edit Member
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="mb-4">
                                                    <h6 class="section-title bg-light p-2 rounded">
                                                        <i class="fa fa-user me-2"></i> Member Profile
                                                    </h6>
                                                    <input type="hidden" name="id" id="edit-id">

                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-cid">CID</label>
                                                                <input type="text" class="form-control"
                                                                    name="cid" id="edit-cid">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-emp_id">Employee
                                                                    ID</label>
                                                                <input type="text" class="form-control"
                                                                    name="emp_id" id="edit-emp_id">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-fname">First
                                                                    Name</label>
                                                                <input type="text" class="form-control"
                                                                    name="fname" id="edit-fname">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-lname">Last
                                                                    Name</label>
                                                                <input type="text" class="form-control"
                                                                    name="lname" id="edit-lname">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <h6 class="section-title bg-light p-2 rounded">
                                                        <i class="fa fa-info-circle me-2"></i> Additional Information
                                                    </h6>
                                                    <div class="row g-3">
                                                        <div class="col-md-12">
                                                            <div class="form-group">
                                                                <label class="form-label"
                                                                    for="edit-address">Address</label>
                                                                <textarea class="form-control" name="address" id="edit-address" rows="2"></textarea>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-birth_date">Birth
                                                                    Date</label>
                                                                <input type="date" class="form-control"
                                                                    name="birth_date" id="edit-birth_date">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label"
                                                                    for="edit-date_registered">Date Registered</label>
                                                                <input type="date" class="form-control"
                                                                    name="date_registered" id="edit-date_registered">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label"
                                                                    for="edit-gender">Gender</label>
                                                                <select class="form-control" name="gender"
                                                                    id="edit-gender">
                                                                    <option value="">Select</option>
                                                                    <option value="male">Male</option>
                                                                    <option value="female">Female</option>
                                                                    <option value="other">Other</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label"
                                                                    for="edit-branch_id">Branch</label>
                                                                <select class="form-control" id="edit-branch_id"
                                                                    name="branch_id">
                                                                    <option value="">Select Branch</option>
                                                                    @foreach ($branches as $branch)
                                                                        <option value="{{ $branch->id }}">
                                                                            {{ $branch->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>

                                                        {{-- <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-account_status">Account Status</label>
                                                                <select class="form-control" name="account_status" id="edit-account_status">
                                                                    <option value="">Select Status</option>
                                                                    <option value="deduction">Deduction</option>
                                                                    <option value="non-deduction">Non-Deduction</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-approval_no">Approval Number</label>
                                                                <input type="text" class="form-control" name="approval_no" id="edit-approval_no">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-start_hold">Start Hold</label>
                                                                <input type="date" class="form-control" name="start_hold" id="edit-start_hold">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-expiry_date">Expiry Date</label>
                                                                <input type="date" class="form-control" name="expiry_date" id="edit-expiry_date">
                                                            </div>
                                                        </div> --}}

                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="section-title bg-light p-2 rounded mb-0">
                                                            <i class="fa fa-piggy-bank me-2"></i> Savings Accounts
                                                        </h6>
                                                        <button type="button"
                                                            class="btn btn-sm btn-primary bulk-edit"
                                                            onclick="showBulkEditModal('savings')">
                                                            Bulk Edit Savings
                                                        </button>
                                                    </div>
                                                    <div id="savings-counter" class="alert alert-info mb-3"></div>
                                                    <div id="edit-savings-container"></div>
                                                    <div class="d-flex justify-content-end mt-2 gap-2">
                                                        <button type="button" id="btnPrevSavings"
                                                            class="btn btn-outline-primary ml-2">
                                                            <i class="fa fa-arrow-left me-1"></i>Previous
                                                        </button>
                                                        <button type="button" id="btnNextSavings"
                                                            class="btn btn-outline-primary ml-2">
                                                            Next<i class="fa fa-arrow-right ms-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="section-title bg-light p-2 rounded mb-0">
                                                            <i class="fa fa-chart-pie me-2"></i> Share Accounts
                                                        </h6>
                                                        <button type="button"
                                                            class="btn btn-sm btn-primary bulk-edit"
                                                            onclick="showBulkEditModal('shares')">
                                                            Bulk Edit Shares
                                                        </button>
                                                    </div>
                                                    <div id="shares-counter" class="alert alert-info mb-3"></div>
                                                    <div id="edit-shares-container"></div>
                                                    <div class="d-flex justify-content-end mt-2">
                                                        <button type="button" id="btnPrevShares"
                                                            class="btn btn-outline-primary ml-2">
                                                            <i class="fa fa-arrow-left me-1"></i>Previous
                                                        </button>
                                                        <button type="button" id="btnNextShares"
                                                            class="btn btn-outline-primary ml-2">
                                                            Next<i class="fa fa-arrow-right ms-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="section-title bg-light p-2 rounded mb-0">
                                                            <i class="fa fa-file-invoice-dollar me-2"></i> Loan
                                                            Information
                                                        </h6>
                                                        <button type="button"
                                                            class="btn btn-sm btn-primary bulk-edit"
                                                            onclick="showBulkEditModal('loans')">
                                                            Bulk Edit Loans
                                                        </button>
                                                    </div>
                                                    <div id="loan-counter" class="alert alert-info mb-3"></div>
                                                    <div id="edit-loan-forecast-container"></div>
                                                </div>
                                            </div>

                                            <div class="modal-footer bg-light">
                                                <div class="me-auto">
                                                    <!-- Loan Navigation -->
                                                    <div class="btn-group me-2">
                                                        <button type="button" class="btn btn-outline-secondary"
                                                            id="btnPrev">
                                                            <i class="fa fa-arrow-left me-1"></i>Prev Loan
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary"
                                                            id="btnNext">
                                                            Next Loan<i class="fa fa-arrow-right ms-1"></i>
                                                        </button>
                                                    </div>



                                                </div>
                                                <div>

                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fa fa-save me-1"></i> Save Changes
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <style>
                                .modal-lg {
                                    max-width: 900px;
                                }

                                .section-title {
                                    color: #2c3e50;
                                    font-size: 1rem;
                                    margin-bottom: 1rem;
                                    border-left: 4px solid #3498db;
                                }

                                .form-label {
                                    font-weight: 500;
                                    color: #34495e;
                                    margin-bottom: 0.5rem;
                                }

                                .form-control {
                                    border-radius: 0.375rem;
                                    border: 1px solid #ddd;
                                    padding: 0.5rem 0.75rem;
                                }

                                .form-control:focus {
                                    border-color: #3498db;
                                    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
                                }

                                .input-group-text {
                                    background-color: #f8f9fa;
                                    border: 1px solid #ddd;
                                }

                                .modal-body {
                                    padding: 1.5rem;
                                }

                                .btn {
                                    padding: 0.5rem 1rem;
                                    border-radius: 0.375rem;
                                }

                                .btn-primary {
                                    background-color: #3498db;
                                    border-color: #3498db;
                                }

                                .btn-primary:hover {
                                    background-color: #2980b9;
                                    border-color: #2980b9;
                                }

                                .modal-content {
                                    border-radius: 0.5rem;
                                    overflow: hidden;
                                }

                                .alert-info {
                                    background-color: #ebf5fb;
                                    border-color: #3498db;
                                    color: #2c3e50;
                                }
                            </style>

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


                                                    </div>
                                                </div>

                                                <hr>

                                                <div>
                                                    <h6>Savings Accounts</h6>
                                                    <div id="savings-account-details" class="border p-3 rounded mb-2"
                                                        style="min-height: 150px;">
                                                        <!-- Savings details will be injected here -->
                                                    </div>

                                                    <div class="d-flex justify-content-between mt-2">
                                                        <button id="savings-view-prev"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="fa fa-arrow-left me-1"></i>Previous
                                                        </button>
                                                        <button id="savings-view-next"
                                                            class="btn btn-sm btn-outline-primary">
                                                            Next<i class="fa fa-arrow-right ms-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <hr>

                                                <div>
                                                    <h6>Share Accounts</h6>
                                                    <div id="shares-account-details" class="border p-3 rounded mb-2"
                                                        style="min-height: 150px;">
                                                        <!-- Shares details will be injected here -->
                                                    </div>

                                                    <div class="d-flex justify-content-between mt-2">
                                                        <button id="shares-view-prev"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="fa fa-arrow-left me-1"></i>Previous
                                                        </button>
                                                        <button id="shares-view-next"
                                                            class="btn btn-sm btn-outline-primary">
                                                            Next<i class="fa fa-arrow-right ms-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <hr>

                                                <div>
                                                    <h6>Loan Accounts</h6>
                                                    <div id="loan-account-details" class="border p-3 rounded mb-2"
                                                        style="min-height: 150px;">
                                                        <!-- Loan details will be injected here -->
                                                    </div>

                                                    <div class="d-flex justify-content-between mt-2">
                                                        <button id="loan-view-prev"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="fa fa-arrow-left me-1"></i>Previous
                                                        </button>
                                                        <button id="loan-view-next"
                                                            class="btn btn-sm btn-outline-primary">
                                                            Next<i class="fa fa-arrow-right ms-1"></i>
                                                        </button>
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

                                .card-header {
                                    border-bottom: 1px solid rgba(0, 0, 0, .125);
                                    box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
                                }

                                .input-group {
                                    min-width: 300px;
                                }

                                .btn-success {
                                    transition: all 0.3s ease;
                                }

                                .btn-success:hover {
                                    transform: translateY(-1px);
                                    box-shadow: 0 4px 6px rgba(0, 0, 0, .1);
                                }

                                .card-title {
                                    font-weight: 600;
                                    letter-spacing: 0.5px;
                                }
                            </style>

                            <div class="modal fade" id="bulkEditModal" tabindex="-1" role="dialog"
                                aria-labelledby="bulkEditModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="bulkEditTitle">Bulk Edit</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="mb-0">Select Accounts to Edit</label>
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input"
                                                            id="selectAllAccounts">
                                                        <label class="custom-control-label"
                                                            for="selectAllAccounts">Select All</label>
                                                    </div>
                                                </div>
                                                <div id="bulkEditAccounts" class="border p-2 rounded"
                                                    style="max-height: 200px; overflow-y: auto;">
                                                    <!-- Account checkboxes will be populated here -->
                                                </div>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Approval Number</label>
                                                <input type="text" class="form-control" id="bulkApprovalNo">
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Start Hold (YYYY-MM)</label>
                                                <input type="month" class="form-control" id="bulkStartHold">
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Expiry Date (YYYY-MM)</label>
                                                <input type="month" class="form-control" id="bulkExpiryDate">
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Request for Hold</label>
                                                <select class="form-control" id="bulkAccountStatus">
                                                    <option value="">No Change</option>
                                                    <option value="deduction">Deduction</option>
                                                    <option value="non-deduction">Non-Deduction</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                onclick="closeBulkEdit()">Cancel</button>
                                            <button type="button" class="btn btn-primary"
                                                onclick="applyBulkEdit()">Apply Changes</button>
                                        </div>
                                    </div>
                                </div>
                            </div>


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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Loan Products Data for JavaScript -->
    <script>
        window.loanProducts = @json($loanProducts);
        window.mortuaryProducts = @json($mortuaryProducts);

        // Function to get loan product info from loan account number
        function getLoanProductInfo(loanAcctNo) {
            if (!loanAcctNo) return { billing_type: 'Unknown', product_name: 'Unknown Product' };

            const segments = loanAcctNo.split('-');
            const productCode = segments[2];

            if (!productCode) return { billing_type: 'Unknown', product_name: 'Unknown Product' };

            const loanProduct = window.loanProducts.find(p => p.product_code === productCode);

            if (loanProduct) {
                return {
                    billing_type: loanProduct.billing_type,
                    product_name: loanProduct.product
                };
            }

            return { billing_type: 'Unknown', product_name: 'Unknown Product' };
        }
    </script>

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
        let loans = [];
        let savings = [];
        let shares = [];
        let currentLoanIndex = 0;
        let currentSavingsIndex = 0;
        let currentSharesIndex = 0;

        // Add the date formatting helper function
        function formatDate(dateString) {
            if (!dateString || dateString === 'null' || dateString === 'undefined') return '';

            // If it's already in YYYY-MM format, return as is
            if (/^\d{4}-\d{2}$/.test(dateString)) return dateString;

            // If it's already in YYYY-MM-DD format, return as is
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString;

            // Handle datetime format with timezone
            if (dateString.includes('T')) {
                // Extract just the date part without timezone conversion
                return dateString.split('T')[0];
            }

            // For other formats, parse without timezone conversion
            try {
                const parts = new Date(dateString).toISOString().split('T')[0];
                return parts;
            } catch (error) {
                console.error('Error formatting date:', error);
                return '';
            }
        }

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
            $('#edit-approval_no').val(button.data('approval_no'));

            // Properly format and set dates and status
            var expiry_date = button.data('expiry_date');
            var start_hold = button.data('start_hold');
            var account_status = button.data('account_status');

            // Set the dates and account status
            $('#edit-expiry_date').val(formatDate(expiry_date));
            $('#edit-start_hold').val(formatDate(start_hold));
            $('#edit-account_status').val(account_status);

            // Handle loans
            loans = button.data('loans') || [];
            if (typeof loans === 'string') {
                try {
                    loans = JSON.parse(loans);
                } catch {
                    loans = [];
                }
            }

            // Handle savings
            savings = button.data('savings') || [];
            if (typeof savings === 'string') {
                try {
                    savings = JSON.parse(savings);
                } catch {
                    savings = [];
                }
            }

            // Handle shares
            shares = button.data('shares') || [];
            if (typeof shares === 'string') {
                try {
                    shares = JSON.parse(shares);
                } catch {
                    shares = [];
                }
            }

            // Format dates for all accounts
            loans = loans.map(loan => ({
                ...loan,
                open_date: formatDate(loan.open_date),
                maturity_date: formatDate(loan.maturity_date),
                amortization_due_date: formatDate(loan.amortization_due_date),
                start_hold: formatDate(loan.start_hold),
                expiry_date: formatDate(loan.expiry_date)
            }));

            savings = savings.map(saving => ({
                ...saving,
                open_date: formatDate(saving.open_date),
                start_hold: formatDate(saving.start_hold),
                expiry_date: formatDate(saving.expiry_date)
            }));

            shares = shares.map(share => ({
                ...share,
                open_date: formatDate(share.open_date),
                start_hold: formatDate(share.start_hold),
                expiry_date: formatDate(share.expiry_date)
            }));

            // Reset indices
            currentLoanIndex = 0;
            currentSavingsIndex = 0;
            currentSharesIndex = 0;

            // Render initial views
            renderLoan(currentLoanIndex);
            renderSavings(currentSavingsIndex);
            renderShares(currentSharesIndex);

            // Update form action dynamically
            $('#editForm').attr('action', '/master/members/' + button.data('id'));

            updateNavButtons();
        });

        // Button click handlers for loans
        $('#btnNext').click(function() {
            if (currentLoanIndex < loans.length - 1) {
                // Save current form data before navigating
                saveCurrentLoanData();
                currentLoanIndex++;
                renderLoan(currentLoanIndex);
                updateNavButtons();
            }
        });

        $('#btnPrev').click(function() {
            if (currentLoanIndex > 0) {
                // Save current form data before navigating
                saveCurrentLoanData();
                currentLoanIndex--;
                renderLoan(currentLoanIndex);
                updateNavButtons();
            }
        });

        // Button click handlers for savings
        $('#btnNextSavings').click(function() {
            if (currentSavingsIndex < savings.length - 1) {
                // Save current form data before navigating
                saveCurrentSavingsData();
                currentSavingsIndex++;
                renderSavings(currentSavingsIndex);
                updateNavButtons();
            }
        });

        $('#btnPrevSavings').click(function() {
            if (currentSavingsIndex > 0) {
                // Save current form data before navigating
                saveCurrentSavingsData();
                currentSavingsIndex--;
                renderSavings(currentSavingsIndex);
                updateNavButtons();
            }
        });

        // Button click handlers for shares
        $('#btnNextShares').click(function() {
            if (currentSharesIndex < shares.length - 1) {
                // Save current form data before navigating
                saveCurrentSharesData();
                currentSharesIndex++;
                renderShares(currentSharesIndex);
                updateNavButtons();
            }
        });

        $('#btnPrevShares').click(function() {
            if (currentSharesIndex > 0) {
                // Save current form data before navigating
                saveCurrentSharesData();
                currentSharesIndex--;
                renderShares(currentSharesIndex);
                updateNavButtons();
            }
        });

        function updateNavButtons() {
            // Loans navigation
            $('#btnPrev').prop('disabled', currentLoanIndex <= 0 || loans.length === 0);
            $('#btnNext').prop('disabled', currentLoanIndex >= loans.length - 1 || loans.length === 0);

            // Savings navigation
            $('#btnPrevSavings').prop('disabled', currentSavingsIndex <= 0 || savings.length === 0);
            $('#btnNextSavings').prop('disabled', currentSavingsIndex >= savings.length - 1 || savings.length === 0);

            // Shares navigation
            $('#btnPrevShares').prop('disabled', currentSharesIndex <= 0 || shares.length === 0);
            $('#btnNextShares').prop('disabled', currentSharesIndex >= shares.length - 1 || shares.length === 0);
        }

        function renderSavings(index) {
            $('#edit-savings-container').empty();

            if (savings.length === 0) {
                $('#savings-counter').text('No savings accounts found.');
                return;
            }

            let saving = savings[index];
            if (!saving) return;

            // Debug log
            // console.log('Rendering savings data:', saving);

            // Count mortuary savings
            let mortuaryCount = countMortuarySavings(savings);
            let isCurrentMortuary = isMortuarySavings(saving);
            let mortuaryProduct = null;

            if (isCurrentMortuary && saving.account_number) {
                let segments = saving.account_number.split('-');
                let productCode = segments[2];
                mortuaryProduct = window.mortuaryProducts.find(p => p.product_code === productCode);
            }

            let html = `
            <div class="savings-item border p-3 mb-3 rounded">
                ${mortuaryCount > 0 ? `
                <div class="alert alert-info mb-3">
                    <strong>🏥 Mortuary Savings Summary:</strong> This member has ${mortuaryCount} mortuary savings account(s)
                </div>
                ` : ''}
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Account Number</label>
                        <input type="text" name="savings[${index}][account_number]" class="form-control" value="${saving.account_number || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Product Name</label>
                        <input type="text" class="form-control" value="${saving.product_name || 'N/A'}" readonly>
                    </div>
                    <div class="form-group col-md-6" style="display: none;">
                        <label>Current Balance</label>
                        <input type="number" step="0.01" name="savings[${index}][current_balance]" class="form-control" value="${saving.current_balance || '0.00'}">
                    </div>
                    <div class="form-group col-md-6" style="display: none;">
                        <label>Open Date</label>
                        <input type="date" name="savings[${index}][open_date]" class="form-control" value="${saving.open_date || ''}" readonly>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Start Hold</label>
                        <input type="month" name="savings[${index}][start_hold]" class="form-control" value="${saving.start_hold || ''}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Expiry Date</label>
                        <input type="month" name="savings[${index}][expiry_date]" class="form-control" value="${saving.expiry_date || ''}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Request for Hold</label>
                        <select name="savings[${index}][account_status]" class="form-control" required>
                            <option value="deduction" ${saving.account_status === 'deduction' ? 'selected' : ''}>Deduction</option>
                            <option value="non-deduction" ${saving.account_status === 'non-deduction' ? 'selected' : ''}>Non-Deduction</option>
                        </select>
                    </div>
                    ${isCurrentMortuary ? `
                    <div class="form-group col-md-6">
                        <label>Deduction Amount</label>
                        <input type="number" step="0.01" name="savings[${index}][deduction_amount]" class="form-control" value="${saving.deduction_amount !== undefined && saving.deduction_amount !== null ? saving.deduction_amount : ''}" placeholder="${mortuaryProduct && mortuaryProduct.amount_to_deduct ? mortuaryProduct.amount_to_deduct : ''}" disabled>
                    </div>
                    <div class="form-group col-md-12">
                        <div class="alert alert-warning">
                            <strong>🏥 Mortuary Product Detected!</strong><br>
                            <strong>Product:</strong> ${mortuaryProduct.product_name} (Code: ${mortuaryProduct.product_code})<br>
                            <strong>Default Amount:</strong> ${mortuaryProduct.amount_to_deduct}<br>

                        </div>
                    </div>
                    ` : `
                    <div class="form-group col-md-6">
                        <label>Deduction Amount</label>
                        <input type="number" step="0.01" name="savings[${index}][deduction_amount]" class="form-control" value="${saving.deduction_amount !== undefined && saving.deduction_amount !== null ? saving.deduction_amount : ''}">
                    </div>
                    `}
                     <div class="form-group col-md-6">
                        <label>Approval Number</label>
                        <input type="text" name="savings[${index}][approval_no]" class="form-control" value="${saving.approval_no || ''}">
                    </div>
                    <div class="form-group col-md-12">
                        <label>Remarks</label>
                        <textarea name="savings[${index}][remarks]" class="form-control" rows="2" placeholder="Remarks">${saving.remarks || ''}</textarea>
                    </div>
                </div>
            </div>`;

            $('#edit-savings-container').html(html);
            $('#savings-counter').text(`Savings Account ${index + 1} of ${savings.length}`);

            // Add form submission debugging
            $('#editForm').on('submit', function(e) {
                console.log('Form data being submitted:', $(this).serializeArray());
            });
        }

        function renderShares(index) {
            $('#edit-shares-container').empty();

            if (shares.length === 0) {
                $('#shares-counter').text('No share accounts found.');
                return;
            }

            let share = shares[index];
            if (!share) return;

            let html = `
            <div class="shares-item border p-3 mb-3 rounded">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Account Number</label>
                        <input type="text" name="shares[${index}][account_number]" class="form-control" value="${share.account_number || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Product Name</label>
                        <input type="text" class="form-control" value="${share.product_name || 'N/A'}" readonly>
                    </div>
                    <div class="form-group col-md-6" style="display: none;">
                        <label>Current Balance</label>
                        <input type="number" step="0.01" name="shares[${index}][current_balance]" class="form-control" value="${share.current_balance || ''}">
                    </div>
                    <div class="form-group col-md-6" style="display: none;">
                        <label>Open Date</label>
                        <input type="date" name="shares[${index}][open_date]" class="form-control" value="${share.open_date || ''}" readonly>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Start Hold</label>
                        <input type="month" name="shares[${index}][start_hold]" class="form-control" value="${share.start_hold || ''}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Expiry Date</label>
                        <input type="month" name="shares[${index}][expiry_date]" class="form-control" value="${share.expiry_date || ''}">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Request for Hold</label>
                        <select name="shares[${index}][account_status]" class="form-control">
                            <option value="deduction" ${share.account_status === 'deduction' ? 'selected' : ''}>Deduction</option>
                            <option value="non-deduction" ${share.account_status === 'non-deduction' ? 'selected' : ''}>Non-Deduction</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Deduction Amount</label>
                        <input type="number" step="0.01" name="shares[${index}][deduction_amount]" class="form-control" value="${share.deduction_amount || '0.00'}">
                    </div>
                       <div class="form-group col-md-6">
                        <label>Approval Number</label>
                        <input type="text" name="shares[${index}][approval_no]" class="form-control" value="${share.approval_no || ''}">
                    </div>
                    <div class="form-group col-md-12">
                        <label>Remarks</label>
                        <textarea name="shares[${index}][remarks]" class="form-control" rows="2" placeholder="Remarks">${share.remarks || ''}</textarea>
                    </div>
                </div>
            </div>`;

            $('#edit-shares-container').html(html);
            $('#shares-counter').text(`Share Account ${index + 1} of ${shares.length}`);
        }

        function renderLoan(index) {
            $('#edit-loan-forecast-container').empty();

            if (loans.length === 0) {
                $('#loan-counter').text('No loans found.');
                return;
            }

            let loan = loans[index];
            if (!loan) return;

            // console.log('Rendering loan data:', loan);

            // Get loan product info from loan account number
            let productInfo = getLoanProductInfo(loan.loan_acct_no);

            let html = `
    <div class="loan-item border p-3 mb-3 rounded position-relative">
        <input type="hidden" name="loan_forecasts[${index}][id]" value="${loan.id || ''}">
        <input type="hidden" name="loan_forecasts[${index}][billing_period]" value="${loan.billing_period || ''}">
        <input type="hidden" name="loan_forecasts[${index}][amount_due]" value="${loan.amount_due || 0}">
        <input type="hidden" name="loan_forecasts[${index}][open_date]" value="${loan.open_date || ''}">
        <input type="hidden" name="loan_forecasts[${index}][maturity_date]" value="${loan.maturity_date || ''}">
        <input type="hidden" name="loan_forecasts[${index}][amortization_due_date]" value="${loan.amortization_due_date || ''}">
        <input type="hidden" name="loan_forecasts[${index}][penalty_due]" value="${loan.penalty_due || 0}">
        <input type="hidden" name="loan_forecasts[${index}][deduction_amount]" value="${loan.deduction_amount || 0}">
        <div class="form-row">
            <div class="col-md-6">
                <label>Loan Account No.</label>
                <input type="text" name="loan_forecasts[${index}][loan_acct_no]" class="form-control" value="${loan.loan_acct_no || ''}" required>
            </div>
            <div class="col-md-6">
                <label>Product Name</label>
                <input type="text" class="form-control" value="${productInfo.product_name}" readonly>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-2 mt-2 mb-2 bg-light">
                    <label class="font-weight-bold">Original Billing</label>
                    <div class="form-group mb-2">
                        <label>Principal</label>
                        <input type="number" step="0.01" class="form-control" id="original_principal_due_${index}" value="${loan.original_principal_due || 0}" readonly tabindex="-1">
                    </div>
                    <div class="form-group mb-2">
                        <label>Interest</label>
                        <input type="number" step="0.01" class="form-control" id="original_interest_due_${index}" value="${loan.original_interest_due || 0}" readonly tabindex="-1">
                    </div>
                    <div class="form-group mb-0">
                        <label>Total Amort</label>
                        <input type="number" step="0.01" class="form-control" value="${loan.original_total_due || 0}" readonly tabindex="-1">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-2 mt-2 mb-2 bg-light">
                    <label class="font-weight-bold">Amort Due</label>
                    <div class="form-group mb-2">
                        <label>Principal</label>
                        <input type="number" step="0.01" name="loan_forecasts[${index}][principal_due]" class="form-control principal-due" id="principal_due_${index}" value="${loan.principal_due || 0}">
                    </div>
                    <div class="form-group mb-2">
                        <label>Interest</label>
                        <input type="number" step="0.01" name="loan_forecasts[${index}][interest_due]" class="form-control interest-due" id="interest_due_${index}" value="${loan.interest_due || 0}">
                    </div>
                    <div class="form-group mb-0">
                        <label>Total Amort</label>
                        <input type="number" step="0.01" name="loan_forecasts[${index}][total_due]" class="form-control" id="total_due_${index}" value="${loan.total_due || 0}" readonly tabindex="-1">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label>Billing Type</label>
                <input type="text" class="form-control" value="${productInfo.billing_type}" readonly>
            </div>
            <div class="col-md-6">
                <label>Account Status</label>
                <select name="loan_forecasts[${index}][account_status]" class="form-control">
                    <option value="deduction" ${loan.account_status === 'deduction' ? 'selected' : ''}>Deduction</option>
                    <option value="non-deduction" ${loan.account_status === 'non-deduction' ? 'selected' : ''}>Non-Deduction</option>
                </select>
            </div>
            <div class="col-md-6">
                <label>Start Hold</label>
                <input type="month" name="loan_forecasts[${index}][start_hold]" class="form-control" value="${loan.start_hold || ''}">
            </div>
            <div class="col-md-6">
                <label>Expiry Date</label>
                <input type="month" name="loan_forecasts[${index}][expiry_date]" class="form-control" value="${loan.expiry_date || ''}">
            </div>
            <div class="col-md-6">
                <label>Approval Number</label>
                <input type="text" name="loan_forecasts[${index}][approval_no]" class="form-control" value="${loan.approval_no || ''}">
            </div>
            <div class="col-md-12">
                <label>Remarks</label>
                <textarea name="loan_forecasts[${index}][remarks]" class="form-control" rows="2" placeholder="Remarks">${loan.remarks || ''}</textarea>
            </div>
        </div>
    </div>`;

            $('#edit-loan-forecast-container').html(html);
            $('#loan-counter').text(`Loan ${index + 1} of ${loans.length}`);

            // Add validation and automation for principal_due and interest_due
            const $principal = $(`#principal_due_${index}`);
            const $interest = $(`#interest_due_${index}`);
            const $total = $(`#total_due_${index}`);
            const originalPrincipal = parseFloat($(`#original_principal_due_${index}`).val()) || 0;
            const originalInterest = parseFloat($(`#original_interest_due_${index}`).val()) || 0;

            function updateTotalDue() {
                let p = parseFloat($principal.val()) || 0;
                let i = parseFloat($interest.val()) || 0;
                $total.val((p + i).toFixed(2));
            }

            $principal.on('input', function() {
                let val = parseFloat($principal.val()) || 0;
                if (val > originalPrincipal) {
                    $principal.val(originalPrincipal.toFixed(2));
                }
                updateTotalDue();
            });
            $interest.on('input', function() {
                let val = parseFloat($interest.val()) || 0;
                if (val > originalInterest) {
                    $interest.val(originalInterest.toFixed(2));
                }
                updateTotalDue();
            });
            // Initial calculation
            updateTotalDue();
        }

        // Function to save current loan data to the loans array
        function saveCurrentLoanData() {
            if (loans.length === 0 || currentLoanIndex >= loans.length) return;

            const currentLoan = loans[currentLoanIndex];
            if (!currentLoan) return;

            // Get form values
            const principalDue = parseFloat($(`#principal_due_${currentLoanIndex}`).val()) || 0;
            const interestDue = parseFloat($(`#interest_due_${currentLoanIndex}`).val()) || 0;
            const totalDue = parseFloat($(`#total_due_${currentLoanIndex}`).val()) || 0;
            const accountStatus = $(`select[name="loan_forecasts[${currentLoanIndex}][account_status]"]`).val();
            const startHold = $(`input[name="loan_forecasts[${currentLoanIndex}][start_hold]"]`).val();
            const expiryDate = $(`input[name="loan_forecasts[${currentLoanIndex}][expiry_date]"]`).val();
            const approvalNo = $(`input[name="loan_forecasts[${currentLoanIndex}][approval_no]"]`).val();

            // Update the loan object in the array
            currentLoan.principal_due = principalDue;
            currentLoan.interest_due = interestDue;
            currentLoan.total_due = totalDue;
            currentLoan.account_status = accountStatus;
            currentLoan.start_hold = startHold;
            currentLoan.expiry_date = expiryDate;
            currentLoan.approval_no = approvalNo;

            // console.log('Saved loan data for index', currentLoanIndex, ':', currentLoan);
        }

        // Function to save current savings data to the savings array
        function saveCurrentSavingsData() {
            if (savings.length === 0 || currentSavingsIndex >= savings.length) return;

            const currentSaving = savings[currentSavingsIndex];
            if (!currentSaving) return;

            // Get form values
            const startHold = $(`input[name="savings[${currentSavingsIndex}][start_hold]"]`).val();
            const expiryDate = $(`input[name="savings[${currentSavingsIndex}][expiry_date]"]`).val();
            const accountStatus = $(`select[name="savings[${currentSavingsIndex}][account_status]"]`).val();
            const deductionAmount = parseFloat($(`input[name="savings[${currentSavingsIndex}][deduction_amount]"]`).val()) || 0;
            const approvalNo = $(`input[name="savings[${currentSavingsIndex}][approval_no]"]`).val();
            const remarks = $(`textarea[name="savings[${currentSavingsIndex}][remarks]"]`).val();

            // Update the saving object in the array
            currentSaving.start_hold = startHold;
            currentSaving.expiry_date = expiryDate;
            currentSaving.account_status = accountStatus;
            currentSaving.deduction_amount = deductionAmount;
            currentSaving.approval_no = approvalNo;
            currentSaving.remarks = remarks;

            // console.log('Saved savings data for index', currentSavingsIndex, ':', currentSaving);
        }

        // Function to save current shares data to the shares array
        function saveCurrentSharesData() {
            if (shares.length === 0 || currentSharesIndex >= shares.length) return;

            const currentShare = shares[currentSharesIndex];
            if (!currentShare) return;

            // Get form values
            const startHold = $(`input[name="shares[${currentSharesIndex}][start_hold]"]`).val();
            const expiryDate = $(`input[name="shares[${currentSharesIndex}][expiry_date]"]`).val();
            const accountStatus = $(`select[name="shares[${currentSharesIndex}][account_status]"]`).val();
            const deductionAmount = parseFloat($(`input[name="shares[${currentSharesIndex}][deduction_amount]"]`).val()) || 0;
            const approvalNo = $(`input[name="shares[${currentSharesIndex}][approval_no]"]`).val();
            const remarks = $(`textarea[name="shares[${currentSharesIndex}][remarks]"]`).val();

            // Update the share object in the array
            currentShare.start_hold = startHold;
            currentShare.expiry_date = expiryDate;
            currentShare.account_status = accountStatus;
            currentShare.deduction_amount = deductionAmount;
            currentShare.approval_no = approvalNo;
            currentShare.remarks = remarks;

            // console.log('Saved shares data for index', currentSharesIndex, ':', currentShare);
        }

        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            // Set member info fields
            $('#view-fname').text(button.data('fname'));
            $('#view-lname').text(button.data('lname'));
            $('#view-cid').text(button.data('cid'));
            $('#view-emp_id').text(button.data('emp_id'));
            $('#view-address').text(button.data('address'));
            $('#view-branch').text(button.data('branch'));
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

            // Handle loans
            var loans = button.data('loans') || [];
            var currentLoanIndex = 0;

            function renderViewLoan(index) {
                var $container = $('#loan-account-details');

                if (loans.length === 0) {
                    $container.html('<p>No loan accounts found.</p>');
                    $('#loan-view-prev').prop('disabled', true);
                    $('#loan-view-next').prop('disabled', true);
                    return;
                }

                var loan = loans[index];
                if (!loan) return;

                // Get loan product info from loan account number
                var productInfo = getLoanProductInfo(loan.loan_acct_no);

                var html = `
                    <div class="loan-details">
                        <p><strong>Loan Account No.:</strong> ${loan.loan_acct_no || 'N/A'}</p>
                        <p><strong>Product Name:</strong> ${productInfo.product_name}</p>
                        <p><strong>Billing Type:</strong> <span class="badge ${productInfo.billing_type === 'special' ? 'badge-warning' : 'badge-info'}">${productInfo.billing_type}</span></p>

                        <p><strong>Open Date:</strong> ${loan.open_date || 'N/A'}</p>
                        <p><strong>Maturity Date:</strong> ${loan.maturity_date || 'N/A'}</p>
                        <p><strong>Amortization Due Date:</strong> ${loan.amortization_due_date || 'N/A'}</p>
                        <p><strong>Total Amort Due:</strong> ${loan.total_due || '0.00'}</p>
                        <p><strong>Total Billed:</strong> ${loan.original_total_due || '0.00'}</p>
                        <p><strong>Principal:</strong> ${loan.principal_due || '0.00'}</p>
                        <p><strong>Interest:</strong> ${loan.interest_due || '0.00'}</p>
                        <p><strong>Penalty Due:</strong> ${loan.penalty_due || '0.00'}</p>
                        <p><strong>Deduction Amount:</strong> ${loan.deduction_amount || '0.00'}</p>
                        <p><strong>Approval Number:</strong> ${loan.approval_no || 'N/A'}</p>
                        <p><strong>Start Hold:</strong> ${loan.start_hold || 'N/A'}</p>
                        <p><strong>Expiry Date:</strong> ${loan.expiry_date || 'N/A'}</p>
                        <p><strong>Account Status:</strong> ${loan.account_status || 'N/A'}</p>
                        <p><em>Loan ${index + 1} of ${loans.length}</em></p>
                    </div>`;

                $container.html(html);
                $('#loan-view-prev').prop('disabled', index === 0);
                $('#loan-view-next').prop('disabled', index === loans.length - 1);
            }

            // Handle savings accounts
            var savings = button.data('savings') || [];
            var currentSavingsIndex = 0;

            function renderViewSavings(index) {
                var $container = $('#savings-account-details');

                if (savings.length === 0) {
                    $container.html('<p>No savings accounts found.</p>');
                    $('#savings-view-prev').prop('disabled', true);
                    $('#savings-view-next').prop('disabled', true);
                    return;
                }

                var saving = savings[index];
                if (!saving) return;

                // Count mortuary savings
                let mortuaryCount = countMortuarySavings(savings);
                let isCurrentMortuary = isMortuarySavings(saving);
                let mortuaryProduct = null;

                if (isCurrentMortuary && saving.account_number) {
                    let segments = saving.account_number.split('-');
                    let productCode = segments[2];
                    mortuaryProduct = window.mortuaryProducts.find(p => p.product_code === productCode);
                }

                var html = `
                    <div class="savings-details">
                        ${mortuaryCount > 0 ? `
                        <div class="alert alert-info mb-3">
                            <strong>🏥 Mortuary Savings Summary:</strong> This member has ${mortuaryCount} mortuary savings account(s)
                        </div>
                        ` : ''}
                        <p><strong>Account Number:</strong> ${saving.account_number || 'N/A'}</p>
                        <p><strong>Product Code:</strong> ${saving.product_code || 'N/A'}</p>
                        <p><strong>Product Name:</strong> ${saving.product_name || 'N/A'}</p>
                        <p><strong>Current Balance:</strong> ${saving.current_balance || '0.00'}</p>
                        <p><strong>Available Balance:</strong> ${saving.available_balance || '0.00'}</p>
                        <p><strong>Interest:</strong> ${saving.interest || '0.00'}</p>
                        <p><strong>Open Date:</strong> ${saving.open_date || 'N/A'}</p>
                        <p><strong>Approval Number:</strong> ${saving.approval_no || 'N/A'}</p>
                        <p><strong>Start Hold:</strong> ${saving.start_hold || 'N/A'}</p>
                        <p><strong>Expiry Date:</strong> ${saving.expiry_date || 'N/A'}</p>
                        <p><strong>Account Status:</strong> ${saving.account_status || 'N/A'}</p>
                        ${isCurrentMortuary ? `
                        <div class="alert alert-warning">
                            <strong>🏥 Mortuary Product Detected!</strong><br>
                            <strong>Product:</strong> ${mortuaryProduct.product_name}<br>
                            <strong>Default Amount:</strong> ${mortuaryProduct.amount_to_deduct}<br>
                            <strong>Prioritization:</strong> ${mortuaryProduct.prioritization}<br>
                            <strong>Deduction Amount:</strong> ${saving.deduction_amount || '0.00'}
                        </div>
                        ` : ''}
                        <p><em>Savings Account ${index + 1} of ${savings.length}</em></p>
                    </div>`;

                $container.html(html);
                $('#savings-view-prev').prop('disabled', index === 0);
                $('#savings-view-next').prop('disabled', index === savings.length - 1);
            }

            // Handle shares accounts
            var shares = button.data('shares') || [];
            var currentSharesIndex = 0;

            function renderViewShares(index) {
                var $container = $('#shares-account-details');

                if (shares.length === 0) {
                    $container.html('<p>No share accounts found.</p>');
                    $('#shares-view-prev').prop('disabled', true);
                    $('#shares-view-next').prop('disabled', true);
                    return;
                }

                var share = shares[index];
                if (!share) return;

                var html = `
                    <div class="shares-details">
                        <p><strong>Account Number:</strong> ${share.account_number || 'N/A'}</p>
                        <p><strong>Product Code:</strong> ${share.product_code || 'N/A'}</p>
                        <p><strong>Product Name:</strong> ${share.product_name || 'N/A'}</p>
                        <p><strong>Current Balance:</strong> ${share.current_balance || '0.00'}</p>
                        <p><strong>Available Balance:</strong> ${share.available_balance || '0.00'}</p>
                        <p><strong>Interest:</strong> ${share.interest || '0.00'}</p>
                        <p><strong>Open Date:</strong> ${share.open_date || 'N/A'}</p>
                        <p><strong>Approval Number:</strong> ${share.approval_no || 'N/A'}</p>
                        <p><strong>Start Hold:</strong> ${share.start_hold || 'N/A'}</p>
                        <p><strong>Expiry Date:</strong> ${share.expiry_date || 'N/A'}</p>
                        <p><strong>Account Status:</strong> ${share.account_status || 'N/A'}</p>
                        <p><em>Share Account ${index + 1} of ${shares.length}</em></p>
                    </div>`;

                $container.html(html);
                $('#shares-view-prev').prop('disabled', index === 0);
                $('#shares-view-next').prop('disabled', index === shares.length - 1);
            }

            // Initial render for all accounts
            renderViewSavings(currentSavingsIndex);
            renderViewShares(currentSharesIndex);
            renderViewLoan(currentLoanIndex);

            // Savings navigation
            $('#savings-view-prev').off('click').on('click', function() {
                if (currentSavingsIndex > 0) {
                    currentSavingsIndex--;
                    renderViewSavings(currentSavingsIndex);
                }
            });

            $('#savings-view-next').off('click').on('click', function() {
                if (currentSavingsIndex < savings.length - 1) {
                    currentSavingsIndex++;
                    renderViewSavings(currentSavingsIndex);
                }
            });

            // Shares navigation
            $('#shares-view-prev').off('click').on('click', function() {
                if (currentSharesIndex > 0) {
                    currentSharesIndex--;
                    renderViewShares(currentSharesIndex);
                }
            });

            $('#shares-view-next').off('click').on('click', function() {
                if (currentSharesIndex < shares.length - 1) {
                    currentSharesIndex++;
                    renderViewShares(currentSharesIndex);
                }
            });

            // Loan navigation
            $('#loan-view-prev').off('click').on('click', function() {
                if (currentLoanIndex > 0) {
                    currentLoanIndex--;
                    renderViewLoan(currentLoanIndex);
                }
            });

            $('#loan-view-next').off('click').on('click', function() {
                if (currentLoanIndex < loans.length - 1) {
                    currentLoanIndex++;
                    renderViewLoan(currentLoanIndex);
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

    <script>
        $(document).ready(function() {
            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('.branch-item').click(function(e) {
                e.preventDefault();
                var branchName = $(this).text();
                var branchId = $(this).data('id');
                $('#branchDropdownBtn').text(branchName);
                $('#branch_id').val(branchId);
            });

            // Add form submission handler
            $('#editForm').on('submit', function(e) {
                e.preventDefault();

                var formData = $(this).serializeArray();
                console.log('Form data:', formData);

                // Create a more readable version of the savings data
                var savingsData = formData.filter(item => item.name.startsWith('savings'));
                console.log('Savings data being submitted:', savingsData);

                // Show loading state
                Swal.fire({
                    title: 'Saving changes...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Submit the form via AJAX
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Changes saved successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        console.error('Error response:', xhr);
                        let errorMessage = 'An error occurred while saving changes.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    }
                });
            });
        });

        function applyBulkEdit() {
            const approvalNo = $('#bulkApprovalNo').val();
            const startHold = $('#bulkStartHold').val();
            const expiryDate = $('#bulkExpiryDate').val();
            const accountStatus = $('#bulkAccountStatus').val();

            // Get selected account indices
            const selectedIndices = [];
            $('#bulkEditAccounts input:checked').each(function() {
                selectedIndices.push($(this).data('index'));
            });

            if (selectedIndices.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Accounts Selected',
                    text: 'Please select at least one account to edit.',
                });
                return;
            }

            // Debug selected accounts before changes
            const beforeChanges = {
                loans: JSON.parse(JSON.stringify(loans)),
                savings: JSON.parse(JSON.stringify(savings)),
                shares: JSON.parse(JSON.stringify(shares))
            };

            // Apply changes to selected accounts
            selectedIndices.forEach(index => {
                if (currentBulkEditType === 'loans') {
                    if (approvalNo !== '') loans[index].approval_no = approvalNo;
                    if (startHold !== '') loans[index].start_hold = startHold;
                    if (expiryDate !== '') loans[index].expiry_date = expiryDate;
                    if (accountStatus !== '') loans[index].account_status = accountStatus;
                } else if (currentBulkEditType === 'savings') {
                    if (approvalNo !== '') savings[index].approval_no = approvalNo;
                    if (startHold !== '') savings[index].start_hold = startHold;
                    if (expiryDate !== '') savings[index].expiry_date = expiryDate;
                    if (accountStatus !== '') savings[index].account_status = accountStatus;
                } else if (currentBulkEditType === 'shares') {
                    if (approvalNo !== '') shares[index].approval_no = approvalNo;
                    if (startHold !== '') shares[index].start_hold = startHold;
                    if (expiryDate !== '') shares[index].expiry_date = expiryDate;
                    if (accountStatus !== '') shares[index].account_status = accountStatus;
                }
            });

            // Debug data after changes
            const afterChanges = {
                loans: JSON.parse(JSON.stringify(loans)),
                savings: JSON.parse(JSON.stringify(savings)),
                shares: JSON.parse(JSON.stringify(shares))
            };

            // Re-render the current view
            if (currentBulkEditType === 'loans') {
                renderLoan(currentLoanIndex);
            } else if (currentBulkEditType === 'savings') {
                renderSavings(currentSavingsIndex);
            } else if (currentBulkEditType === 'shares') {
                renderShares(currentSharesIndex);
            }

            // Get the member ID from the edit form
            const memberId = $('#edit-id').val();

            // Show loading state
            Swal.fire({
                title: 'Saving changes...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Prepare the form data
            let formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            // Only include the data for the current type being edited
            if (currentBulkEditType === 'loans') {
                selectedIndices.forEach((index, i) => {
                    const loan = loans[index];
                    Object.entries(loan).forEach(([key, value]) => {
                        if (value !== null && value !== undefined) {
                            formData.append(`loan_forecasts[${i}][${key}]`, value);
                        }
                    });
                    // Ensure required fields are present
                    formData.append(`loan_forecasts[${i}][account_status]`, loan.account_status || accountStatus);
                    formData.append(`loan_forecasts[${i}][approval_no]`, loan.approval_no || approvalNo);
                    formData.append(`loan_forecasts[${i}][start_hold]`, loan.start_hold || startHold);
                    formData.append(`loan_forecasts[${i}][expiry_date]`, loan.expiry_date || expiryDate);
                });
            } else if (currentBulkEditType === 'savings') {
                selectedIndices.forEach((index, i) => {
                    const saving = savings[index];
                    Object.entries(saving).forEach(([key, value]) => {
                        if (value !== null && value !== undefined) {
                            formData.append(`savings[${i}][${key}]`, value);
                        }
                    });
                    // Ensure required fields are present
                    formData.append(`savings[${i}][account_status]`, saving.account_status || accountStatus);
                    formData.append(`savings[${i}][approval_no]`, saving.approval_no || approvalNo);
                    formData.append(`savings[${i}][start_hold]`, saving.start_hold || startHold);
                    formData.append(`savings[${i}][expiry_date]`, saving.expiry_date || expiryDate);
                });
            } else if (currentBulkEditType === 'shares') {
                selectedIndices.forEach((index, i) => {
                    const share = shares[index];
                    Object.entries(share).forEach(([key, value]) => {
                        if (value !== null && value !== undefined) {
                            formData.append(`shares[${i}][${key}]`, value);
                        }
                    });
                    // Ensure required fields are present
                    formData.append(`shares[${i}][account_status]`, share.account_status || accountStatus);
                    formData.append(`shares[${i}][approval_no]`, share.approval_no || approvalNo);
                    formData.append(`shares[${i}][start_hold]`, share.start_hold || startHold);
                    formData.append(`shares[${i}][expiry_date]`, share.expiry_date || expiryDate);
                });
            }

            // Add debug information to form data
            formData.append('debug_info', JSON.stringify({
                selectedIndices,
                beforeChanges,
                afterChanges,
                currentBulkEditType
            }));

            // Submit the changes to the server
            $.ajax({
                url: `/master/members/${memberId}`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    // Close the bulk edit modal
                    $('#bulkEditModal').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'Changes Saved Successfully',
                        text: 'All selected accounts have been updated.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error response:', xhr);
                    console.error('Status:', status);
                    console.error('Error:', error);

                    let errorMessage = 'An error occurred while saving changes.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                }
            });
        }
    </script>


    <script>
        let currentBulkEditType = '';

        function showBulkEditModal(type) {
            event.preventDefault();
            event.stopPropagation();

            currentBulkEditType = type;
            let accounts = [];
            let title = '';

            if (type === 'loans') {
                accounts = loans;
                title = 'Bulk Edit Loans';
            } else if (type === 'savings') {
                accounts = savings;
                title = 'Bulk Edit Savings';
            } else if (type === 'shares') {
                accounts = shares;
                title = 'Bulk Edit Shares';
            }

            $('#bulkEditTitle').text(title);

            // Clear previous values
            $('#bulkApprovalNo').val('');
            $('#bulkStartHold').val('');
            $('#bulkExpiryDate').val('');
            $('#bulkAccountStatus').val('');
            $('#selectAllAccounts').prop('checked', false);

            // Populate accounts list
            const accountsContainer = $('#bulkEditAccounts');
            accountsContainer.empty();

            let checkedCount = 0;
            accounts.forEach((account, index) => {
                const accountNo = type === 'loans' ? account.loan_acct_no : account.account_number;

                // Check if account has non-deduction status or dates set
                const hasNonDeduction = account.account_status === 'non-deduction';
                const hasStartHold = account.start_hold && account.start_hold !== '';
                const hasExpiryDate = account.expiry_date && account.expiry_date !== '';
                const shouldBeChecked = hasNonDeduction || hasStartHold || hasExpiryDate;

                if (shouldBeChecked) checkedCount++;

                // Add status indicators for existing values
                let statusInfo = '';
                if (hasNonDeduction || hasStartHold || hasExpiryDate) {
                    statusInfo = '<small class="text-muted ml-2">(';
                    let statuses = [];
                    if (hasNonDeduction) statuses.push('Non-deduction');
                    if (hasStartHold) statuses.push(`Hold: ${account.start_hold}`);
                    if (hasExpiryDate) statuses.push(`Expiry: ${account.expiry_date}`);
                    statusInfo += statuses.join(', ') + ')</small>';
                }

                accountsContainer.append(`
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input account-checkbox"
                           id="bulk${type}${index}" data-index="${index}"
                           ${shouldBeChecked ? 'checked' : ''}>
                    <label class="custom-control-label" for="bulk${type}${index}">
                        ${accountNo}${statusInfo}
                    </label>
                </div>
            `);
            });

            // Set select all checkbox state based on pre-checked items
            const totalAccounts = accounts.length;
            $('#selectAllAccounts').prop('checked', checkedCount === totalAccounts);

            // Handle select all functionality
            $('#selectAllAccounts').off('change').on('change', function() {
                const isChecked = $(this).prop('checked');
                $('.account-checkbox').prop('checked', isChecked);
            });

            // Handle individual checkbox changes
            $('.account-checkbox').off('change').on('change', function() {
                const allChecked = $('.account-checkbox:checked').length === $('.account-checkbox').length;
                $('#selectAllAccounts').prop('checked', allChecked);
            });

            // Show summary of pre-selected accounts if any
            // if (checkedCount > 0) {
            //     const summaryHtml = `
        //         <div class="alert alert-info mt-2">
        //             <small>${checkedCount} account(s) automatically selected due to existing non-deduction status or hold dates.</small>
        //         </div>
        //     `;
            //     accountsContainer.after(summaryHtml);
            // }

            $('#bulkEditModal').modal('show');
        }

        function closeBulkEdit() {
            $('#bulkEditModal').modal('hide');
            // Restore scrolling to the main edit modal
            $('body').addClass('modal-open');
            // Remove any inline styles that might have been added to body
            $('body').css('overflow', '');
            $('body').css('padding-right', '');
        }

        // Add event handler for bulk edit modal hidden event
        $('#bulkEditModal').on('hidden.bs.modal', function () {
            // Restore scrolling to the main edit modal
            $('body').addClass('modal-open');
            // Remove any inline styles that might have been added to body
            $('body').css('overflow', '');
            $('body').css('padding-right', '');
        });
    </script>

    <script>
        // Pass mortuary products data from PHP to JavaScript
        window.mortuaryProducts = @json($mortuaryProducts ?? []);

        // Function to count mortuary savings for a member
        function countMortuarySavings(savings) {
            if (!savings || !window.mortuaryProducts) return 0;

            let count = 0;
            savings.forEach(saving => {
                if (saving.account_number) {
                    let segments = saving.account_number.split('-');
                    if (segments.length >= 3) {
                        let productCode = segments[2];
                        let mortuaryProduct = window.mortuaryProducts.find(p => p.product_code === productCode);
                        if (mortuaryProduct && mortuaryProduct.product_type === 'mortuary') {
                            count++;
                        }
                    }
                }
            });
            return count;
        }

        // Function to check if a savings account is a mortuary product
        function isMortuarySavings(saving) {
            if (!saving.account_number || !window.mortuaryProducts) return false;

            let segments = saving.account_number.split('-');
            if (segments.length < 3) return false;

            let productCode = segments[2];
            let mortuaryProduct = window.mortuaryProducts.find(p => p.product_code === productCode);
            return mortuaryProduct && mortuaryProduct.product_type === 'mortuary';
        }

        // File upload enhancement
        $(document).ready(function() {
            // Add file validation for CoreID upload
            $('input[name="coreid_file"]').on('change', function() {
                const file = this.files[0];
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
                        this.value = '';
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
                        this.value = '';
                        return;
                    }

                    // Show file info
                    Swal.fire({
                        icon: 'info',
                        title: 'File Selected',
                        html: `
                            <p><strong>File:</strong> ${file.name}</p>
                            <p><strong>Size:</strong> ${fileSize.toFixed(2)} MB</p>
                            <p><strong>Type:</strong> ${file.type || 'Unknown'}</p>
                        `,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });

            // Add loading state for form submission
            $('form[action*="upload-coreid"]').on('submit', function() {
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();

                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fa fa-spinner fa-spin me-1"></i>Uploading...');

                // Show loading message
                Swal.fire({
                    title: 'Uploading File...',
                    html: 'Please wait while we process your file. This may take a few moments.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            });
        });
    </script>

</body>

</html>
