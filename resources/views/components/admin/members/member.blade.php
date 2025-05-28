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
                            </style>
                            <div class="card-body">
                                 <form method="GET" action="{{ url()->current() }}"
                                    class="mb-3 d-flex justify-content-center">
                                    <input type="text" name="search" value="{{ request('search') }}"
                                        class="form-control w-50" placeholder="Search by CID, Name, Branch..." />
                                    <button type="submit" class="btn btn-primary ms-2">Search</button>
                                </form>
                                <div class="table-responsive">
                                    <table class="display table table-striped" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Branch</th>
                                                <th>CID</th>
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
                                                    <td>
                                                        <!-- Edit Button -->
                                                        <button type="button" class="btn btn-rounded btn-primary"
                                                            data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $member->id }}"
                                                            data-fname="{{ $member->fname }}"
                                                            data-lname="{{ $member->lname }}"
                                                            data-cid="{{ $member->cid }}"
                                                            data-branch="{{ $member->branch_id }}">
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Member</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" id="editForm">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="fname">First Name</label>
                            <input type="text" class="form-control" id="editFname" name="fname">
                        </div>
                        <div class="form-group">
                            <label for="lname">Last Name</label>
                            <input type="text" class="form-control" id="editLname" name="lname">
                        </div>
                        <div class="form-group">
                            <label for="cid">CID</label>
                            <input type="text" class="form-control" id="editCid" name="cid">
                        </div>
                        <div class="form-group">
                            <label for="branch_id">Branch</label>
                            <select class="form-control" id="editBranch" name="branch_id">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Member Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Full Name: </strong><span id="viewFullName"></span></p>
                    <p><strong>Branch: </strong><span id="viewBranch"></span></p>
                    <p><strong>CID: </strong><span id="viewCid"></span></p>
                    <p><strong>Employee ID: </strong><span id="viewEmpId"></span></p>
                    <p><strong>Address: </strong><span id="viewAddress"></span></p>
                    <p><strong>Savings Balance: </strong><span id="viewSavings"></span></p>
                    <p><strong>Share Balance: </strong><span id="viewShare"></span></p>
                    <p><strong>Loan Balance: </strong><span id="viewLoan"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Member</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this member?</p>
                </div>
                <div class="modal-footer">
                    <form action="" method="POST" id="deleteForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
            var fname = button.data('fname');
            var lname = button.data('lname');
            var cid = button.data('cid');
            var branch = button.data('branch');

            var modal = $(this);
            modal.find('#editFname').val(fname);
            modal.find('#editLname').val(lname);
            modal.find('#editCid').val(cid);
            modal.find('#editBranch').val(branch);
            modal.find('#editForm').attr('action', '/members/' + id);
        });

        // View Modal
        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var modal = $(this);

            var fullName = button.data('fname') + ' ' + button.data('lname');
            modal.find('#viewFullName').text(fullName);
            modal.find('#viewBranch').text(button.data('branch'));
            modal.find('#viewCid').text(button.data('cid'));
            modal.find('#viewEmpId').text(button.data('emp_id'));
            modal.find('#viewAddress').text(button.data('address'));
            modal.find('#viewSavings').text(button.data('savings'));
            modal.find('#viewShare').text(button.data('share'));
            modal.find('#viewLoan').text(button.data('loan'));
        });


        // Delete Modal
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var modal = $(this);
            modal.find('#deleteForm').attr('action', '/members/' + id);
        });
    </script>

</body>

</html>
