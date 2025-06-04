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
                                                    <input type="text" name="fname" class="form-control" required>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Last Name</label>
                                                    <input type="text" name="lname" class="form-control" required>
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
                                                    <input type="text" name="customer_classification" class="form-control">
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

                                            <!-- Financial Information Section -->
                                            <div class="col-12 mb-3 mt-4">
                                                <h5>Financial Information</h5>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Savings Balance</label>
                                                    <input type="number" step="0.01" name="savings_balance" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Share Balance</label>
                                                    <input type="number" step="0.01" name="share_balance" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">Loan Balance</label>
                                                    <input type="number" step="0.01" name="loan_balance" class="form-control">
                                                </div>
                                            </div>

                                            <!-- Deduction Settings Section -->
                                            <div class="col-12 mb-3 mt-4">
                                                <h5>Deduction Settings</h5>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Account Status</label>
                                                    <select class="form-control" name="account_status">
                                                        <option value="">Select Status</option>
                                                        <option value="deduction">Deduction</option>
                                                        <option value="non-deduction">Non-Deduction</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Approval Number</label>
                                                    <input type="text" name="approval_no" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Start Hold</label>
                                                    <input type="date" name="start_hold" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label">Expiry Date</label>
                                                    <input type="date" name="expiry_date" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
                            <div class="card-header d-flex justify-content-between align-items-center bg-light py-3">
                                <div>
                                    <h4 class="card-title mb-0 text-primary">Member Datatable</h4>
                                    <small class="text-muted">Manage all members in the system</small>
                                </div>

                                <div class="d-flex align-items-center" style="gap: 15px;">
                                    <form method="GET" action="{{ url()->current() }}">
                                        <div class="input-group">
                                            <input type="text"
                                                name="search"
                                                value="{{ request('search') }}"
                                                class="form-control"
                                                placeholder="Search members..."
                                                style="width: 250px; height: 40px;" />
                                            <button type="submit"
                                                class="btn btn-primary d-flex align-items-center"
                                                style="height: 40px;">
                                                <i class="fa fa-search me-2"></i>
                                                Search
                                            </button>
                                        </div>
                                    </form>

                                    <a href="#"
                                        class="btn btn-success d-flex align-items-center"
                                        data-toggle="modal"
                                        data-target="#addModal"
                                        style="height: 40px;">
                                        <i class="fa fa-plus-circle me-2"></i>
                                        Add New Member
                                    </a>
                                </div>
                            </div>

                            <div class="card-body">
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


                                                        <button type="button" class="btn btn-rounded btn-primary"
                                                            data-toggle="modal"
                                                            data-target="#editModal"
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
                                                            data-shares='{!! json_encode($item->member->shares) !!}'>
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
                                                <th>Name</th>
                                                <th>Branch</th>
                                                <th>Savings</th>
                                                <th>Share Balance</th>
                                                <th>Loan Balance</th>

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

                                            </div>

                                            <div class="modal-body">
                                                <div class="mb-4">
                                                    <h6 class="section-title bg-light p-2 rounded">
                                                        <i class="fa fa-user me-2"></i>Member Profile
                                                    </h6>
                                                    <input type="hidden" name="id" id="edit-id">

                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-cid">CID</label>
                                                                <input type="text" class="form-control" name="cid" id="edit-cid">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-emp_id">Employee ID</label>
                                                                <input type="text" class="form-control" name="emp_id" id="edit-emp_id">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-fname">First Name</label>
                                                                <input type="text" class="form-control" name="fname" id="edit-fname">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-lname">Last Name</label>
                                                                <input type="text" class="form-control" name="lname" id="edit-lname">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <h6 class="section-title bg-light p-2 rounded">
                                                        <i class="fa fa-info-circle me-2"></i>Additional Information
                                                    </h6>
                                                    <div class="row g-3">
                                                        <div class="col-md-12">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-address">Address</label>
                                                                <textarea class="form-control" name="address" id="edit-address" rows="2"></textarea>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-birth_date">Birth Date</label>
                                                                <input type="date" class="form-control" name="birth_date" id="edit-birth_date">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-date_registered">Date Registered</label>
                                                                <input type="date" class="form-control" name="date_registered" id="edit-date_registered">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-gender">Gender</label>
                                                                <select class="form-control" name="gender" id="edit-gender">
                                                                    <option value="">Select</option>
                                                                    <option value="male">Male</option>
                                                                    <option value="female">Female</option>
                                                                    <option value="other">Other</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label" for="edit-branch_id">Branch</label>
                                                                <select class="form-control" id="edit-branch_id" name="branch_id">
                                                                    <option value="">Select Branch</option>
                                                                    @foreach ($branches as $branch)
                                                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
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
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <h6 class="section-title bg-light p-2 rounded">
                                                        <i class="fa fa-piggy-bank me-2"></i>Savings Accounts
                                                    </h6>
                                                    <div id="savings-counter" class="alert alert-info mb-3"></div>
                                                    <div id="edit-savings-container"></div>
                                                    <div class="d-flex justify-content-between mt-2">
                                                        <button type="button" id="btnPrevSavings" class="btn btn-outline-primary">
                                                            <i class="fa fa-arrow-left me-1"></i>Previous
                                                        </button>
                                                        <button type="button" id="btnNextSavings" class="btn btn-outline-primary">
                                                            Next<i class="fa fa-arrow-right ms-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <h6 class="section-title bg-light p-2 rounded">
                                                        <i class="fa fa-chart-pie me-2"></i>Share Accounts
                                                    </h6>
                                                    <div id="shares-counter" class="alert alert-info mb-3"></div>
                                                    <div id="edit-shares-container"></div>
                                                    <div class="d-flex justify-content-between mt-2">
                                                        <button type="button" id="btnPrevShares" class="btn btn-outline-primary">
                                                            <i class="fa fa-arrow-left me-1"></i>Previous
                                                        </button>
                                                        <button type="button" id="btnNextShares" class="btn btn-outline-primary">
                                                            Next<i class="fa fa-arrow-right ms-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <h6 class="section-title bg-light p-2 rounded">
                                                        <i class="fa fa-file-invoice-dollar me-2"></i>Loan Information
                                                    </h6>
                                                    <div id="loan-counter" class="alert alert-info mb-3"></div>
                                                    <div id="edit-loan-forecast-container"></div>
                                                </div>
                                            </div>

                                            <div class="modal-footer bg-light">
                                                <div class="me-auto">
                                                    <!-- Loan Navigation -->
                                                    <div class="btn-group me-2">
                                                        <button type="button" class="btn btn-outline-secondary" id="btnPrev">
                                                            <i class="fa fa-arrow-left me-1"></i>Prev Loan
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary" id="btnNext">
                                                            Next Loan<i class="fa fa-arrow-right ms-1"></i>
                                                        </button>
                                                    </div>



                                                </div>
                                                <div>
                                                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fa fa-save me-1"></i>Save Changes
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
                                                    <h6>Savings Accounts</h6>
                                                    <div id="savings-account-details" class="border p-3 rounded mb-2" style="min-height: 150px;">
                                                        <!-- Savings details will be injected here -->
                                                    </div>

                                                    <div class="d-flex justify-content-between mt-2">
                                                        <button id="savings-view-prev" class="btn btn-sm btn-outline-primary">
                                                            <i class="fa fa-arrow-left me-1"></i>Previous
                                                        </button>
                                                        <button id="savings-view-next" class="btn btn-sm btn-outline-primary">
                                                            Next<i class="fa fa-arrow-right ms-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <hr>

                                                <div>
                                                    <h6>Share Accounts</h6>
                                                    <div id="shares-account-details" class="border p-3 rounded mb-2" style="min-height: 150px;">
                                                        <!-- Shares details will be injected here -->
                                                    </div>

                                                    <div class="d-flex justify-content-between mt-2">
                                                        <button id="shares-view-prev" class="btn btn-sm btn-outline-primary">
                                                            <i class="fa fa-arrow-left me-1"></i>Previous
                                                        </button>
                                                        <button id="shares-view-next" class="btn btn-sm btn-outline-primary">
                                                            Next<i class="fa fa-arrow-right ms-1"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <hr>

                                                <div>
                                                    <h6>Loan Accounts</h6>
                                                    <div id="loan-account-details" class="border p-3 rounded mb-2" style="min-height: 150px;">
                                                        <!-- Loan details will be injected here -->
                                                    </div>

                                                    <div class="d-flex justify-content-between mt-2">
                                                        <button id="loan-view-prev" class="btn btn-sm btn-outline-primary">
                                                            <i class="fa fa-arrow-left me-1"></i>Previous
                                                        </button>
                                                        <button id="loan-view-next" class="btn btn-sm btn-outline-primary">
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
                                    border-bottom: 1px solid rgba(0,0,0,.125);
                                    box-shadow: 0 1px 3px rgba(0,0,0,.05);
                                }

                                .input-group {
                                    min-width: 300px;
                                }

                                .btn-success {
                                    transition: all 0.3s ease;
                                }

                                .btn-success:hover {
                                    transform: translateY(-1px);
                                    box-shadow: 0 4px 6px rgba(0,0,0,.1);
                                }

                                .card-title {
                                    font-weight: 600;
                                    letter-spacing: 0.5px;
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
        let loans = [];
        let savings = [];
        let shares = [];
        let currentLoanIndex = 0;
        let currentSavingsIndex = 0;
        let currentSharesIndex = 0;

        $('#editModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            // Debug the data being passed
            console.log('Loan data:', button.data('loans'));
            console.log('Account Status:', button.data('account_status'));
            console.log('Start Hold:', button.data('start_hold'));
            console.log('Expiry Date:', button.data('expiry_date'));

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

            // Format dates for loans - ensure we handle both full datetime and date-only formats
            loans = loans.map(loan => ({
                ...loan,
                open_date: formatDate(loan.open_date),
                maturity_date: formatDate(loan.maturity_date),
                amortization_due_date: formatDate(loan.amortization_due_date),
                start_hold: formatDate(loan.start_hold),
                expiry_date: formatDate(loan.expiry_date)
            }));

            // Reset indices
            currentLoanIndex = 0;
            currentSavingsIndex = 0;
            currentSharesIndex = 0;

            // Render initial views
            renderLoan(currentLoanIndex);
            renderSavings(currentSavingsIndex);
            renderShares(currentSharesIndex);

            // Set form action dynamically
            $('#editForm').attr('action', '/members/' + button.data('id'));

            updateNavButtons();

            // Debug output to help troubleshoot
            console.log('Account Status:', account_status);
            console.log('Expiry Date:', expiry_date);
            console.log('Start Hold:', start_hold);
        });

        // Add helper function to format dates consistently
        function formatDate(dateString) {
            if (!dateString) return '';
            // Handle both full datetime and date-only formats
            return dateString.split(' ')[0];  // This will return YYYY-MM-DD part
        }

        // Button click handlers for loans
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

        // Button click handlers for savings
        $('#btnNextSavings').click(function() {
            if (currentSavingsIndex < savings.length - 1) {
                currentSavingsIndex++;
                renderSavings(currentSavingsIndex);
                updateNavButtons();
            }
        });

        $('#btnPrevSavings').click(function() {
            if (currentSavingsIndex > 0) {
                currentSavingsIndex--;
                renderSavings(currentSavingsIndex);
                updateNavButtons();
            }
        });

        // Button click handlers for shares
        $('#btnNextShares').click(function() {
            if (currentSharesIndex < shares.length - 1) {
                currentSharesIndex++;
                renderShares(currentSharesIndex);
                updateNavButtons();
            }
        });

        $('#btnPrevShares').click(function() {
            if (currentSharesIndex > 0) {
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

            let html = `
            <div class="savings-item border p-3 mb-3 rounded">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Account Number</label>
                        <input type="text" class="form-control" value="${saving.account_number || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Product Code</label>
                        <input type="text" class="form-control" value="${saving.product_code || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Product Name</label>
                        <input type="text" class="form-control" value="${saving.product_name || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Current Balance</label>
                        <input type="number" step="0.01" class="form-control" value="${saving.current_balance || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Available Balance</label>
                        <input type="number" step="0.01" class="form-control" value="${saving.available_balance || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Interest</label>
                        <input type="number" step="0.01" class="form-control" value="${saving.interest || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Open Date</label>
                        <input type="date" class="form-control" value="${saving.open_date || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Approval Number</label>
                        <input type="text" class="form-control" value="${saving.approval_no || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Start Hold</label>
                        <input type="date" class="form-control" value="${saving.start_hold || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Expiry Date</label>
                        <input type="date" class="form-control" value="${saving.expiry_date || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Account Status</label>
                        <select class="form-control" disabled>
                            <option value="deduction" ${saving.account_status === 'deduction' ? 'selected' : ''}>Deduction</option>
                            <option value="non-deduction" ${saving.account_status === 'non-deduction' ? 'selected' : ''}>Non-Deduction</option>
                        </select>
                    </div>
                </div>
            </div>`;

            $('#edit-savings-container').html(html);
            $('#savings-counter').text(`Savings Account ${index + 1} of ${savings.length}`);
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
                        <input type="text" class="form-control" value="${share.account_number || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Product Code</label>
                        <input type="text" class="form-control" value="${share.product_code || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Product Name</label>
                        <input type="text" class="form-control" value="${share.product_name || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Current Balance</label>
                        <input type="number" step="0.01" class="form-control" value="${share.current_balance || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Available Balance</label>
                        <input type="number" step="0.01" class="form-control" value="${share.available_balance || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Interest</label>
                        <input type="number" step="0.01" class="form-control" value="${share.interest || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Open Date</label>
                        <input type="date" class="form-control" value="${share.open_date || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Approval Number</label>
                        <input type="text" class="form-control" value="${share.approval_no || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Start Hold</label>
                        <input type="date" class="form-control" value="${share.start_hold || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Expiry Date</label>
                        <input type="date" class="form-control" value="${share.expiry_date || ''}" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Account Status</label>
                        <select class="form-control" disabled>
                            <option value="deduction" ${share.account_status === 'deduction' ? 'selected' : ''}>Deduction</option>
                            <option value="non-deduction" ${share.account_status === 'non-deduction' ? 'selected' : ''}>Non-Deduction</option>
                        </select>
                    </div>
                </div>
            </div>`;

            $('#edit-shares-container').html(html);
            $('#shares-counter').text(`Share Account ${index + 1} of ${shares.length}`);
        }

        function renderLoan(index) {
            $('#edit-loan-forecast-container').empty();

            let loan = loans.length > 0 ? loans[index] : {};

            // Debug the loan data
            console.log('Rendering loan data:', loan);

            let html = `
    <div class="loan-item border p-3 mb-3 rounded position-relative">
        <input type="hidden" name="loan_forecasts[${index}][id]" value="${loan.id || ''}">
        <input type="hidden" name="loan_forecasts[${index}][billing_period]" value="${loan.billing_period || ''}">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Loan Account No.</label>
                <input type="text" name="loan_forecasts[${index}][loan_acct_no]" class="form-control" value="${loan.loan_acct_no || ''}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Total Due</label>
                <input type="number" step="0.01" name="loan_forecasts[${index}][total_due]" class="form-control" value="${loan.total_due || ''}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Amount Due</label>
                <input type="number" step="0.01" name="loan_forecasts[${index}][amount_due]" class="form-control" value="${loan.amount_due || ''}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Open Date</label>
                <input type="date" name="loan_forecasts[${index}][open_date]" class="form-control" value="${loan.open_date || ''}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Maturity Date</label>
                <input type="date" name="loan_forecasts[${index}][maturity_date]" class="form-control" value="${loan.maturity_date || ''}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Amortization Due Date</label>
                <input type="date" name="loan_forecasts[${index}][amortization_due_date]" class="form-control" value="${loan.amortization_due_date || ''}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Principal Due</label>
                <input type="number" step="0.01" name="loan_forecasts[${index}][principal_due]" class="form-control" value="${loan.principal_due || ''}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Interest Due</label>
                <input type="number" step="0.01" name="loan_forecasts[${index}][interest_due]" class="form-control" value="${loan.interest_due || ''}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Penalty Due</label>
                <input type="number" step="0.01" name="loan_forecasts[${index}][penalty_due]" class="form-control" value="${loan.penalty_due || ''}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Approval Number</label>
                <input type="text" name="loan_forecasts[${index}][approval_no]" class="form-control" value="${loan.approval_no || ''}">
            </div>
            <div class="form-group col-md-6">
                <label>Start Hold Date</label>
                <input type="date" name="loan_forecasts[${index}][start_hold]" class="form-control" value="${loan.start_hold || ''}">
            </div>
            <div class="form-group col-md-6">
                <label>Expiry Date</label>
                <input type="date" name="loan_forecasts[${index}][expiry_date]" class="form-control" value="${loan.expiry_date || ''}">
            </div>
            <div class="form-group col-md-6">
                <label>Account Status</label>
                <select name="loan_forecasts[${index}][account_status]" class="form-control" required>
                    <option value="">Select Status</option>
                    <option value="deduction" ${loan.account_status === 'deduction' ? 'selected' : ''}>Deduction</option>
                    <option value="non-deduction" ${loan.account_status === 'non-deduction' ? 'selected' : ''}>Non-Deduction</option>
                </select>
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

                var html = `
                    <div class="loan-details">
                        <p><strong>Loan Account No.:</strong> ${loan.loan_acct_no || 'N/A'}</p>
                        <p><strong>Amount Due:</strong> ${loan.amount_due || '0.00'}</p>
                        <p><strong>Open Date:</strong> ${loan.open_date || 'N/A'}</p>
                        <p><strong>Maturity Date:</strong> ${loan.maturity_date || 'N/A'}</p>
                        <p><strong>Amortization Due Date:</strong> ${loan.amortization_due_date || 'N/A'}</p>
                        <p><strong>Total Due:</strong> ${loan.total_due || '0.00'}</p>
                        <p><strong>Principal Due:</strong> ${loan.principal_due || '0.00'}</p>
                        <p><strong>Interest Due:</strong> ${loan.interest_due || '0.00'}</p>
                        <p><strong>Penalty Due:</strong> ${loan.penalty_due || '0.00'}</p>
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

                var html = `
                    <div class="savings-details">
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
            $('.branch-item').click(function(e) {
                e.preventDefault();
                var branchName = $(this).text();
                var branchId = $(this).data('id');
                $('#branchDropdownBtn').text(branchName);
                $('#branch_id').val(branchId);
            });
        });
    </script>
</body>

</html>
