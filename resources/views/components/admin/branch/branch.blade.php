<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Branches</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">

    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body>

    <div id="main-wrapper">

        @include('layouts.partials.header')

        @include('layouts.partials.sidebar')

        <div class="content-body">
            <div class="container-fluid">
                <div class="row page-titles mx-0">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4>Branches</h4>
                            <span class="ml-1">Datatable</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active"><a href="{{ route('branch') }}">Branch</a></li>
                        </ol>
                    </div>
                </div>

                <!-- Add Branch Modal -->
                <div class="modal fade" id="addBranchModal" tabindex="-1" aria-labelledby="addBranchModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form action="{{ route('branches.store') }}" method="POST">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header text-dark">
                                    <h5 class="modal-title" id="addBranchModalLabel">Add Branch</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label class="form-label">Branch Name</label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Branch Code</label>
                                        <input type="text" name="code" class="form-control" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Add Branch</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Branch Datatable</h4>
                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addBranchModal">
                                    <i class="fa fa-plus"></i> Add Branch
                                </button>
                            </div>

                            <style>
                                tr {
                                    text-align: center;
                                }
                            </style>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Code</th>
                                                <th>Members</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($branches as $branch)
                                                @php
                                                    $branchUser = App\Models\User::where('branch_id', $branch->id)->first();
                                                @endphp
                                                <tr>
                                                    <td>{{ $branch->name }}</td>
                                                    <td>{{ $branch->code }}</td>
                                                    <td>{{ $branch->members->count() }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $branch->status === 'approved' ? 'success' : ($branch->status === 'N/A' ? 'secondary' : 'warning') }}">
                                                            {{ $branch->status }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">

                                                               <!-- Edit Button -->
                                                            <button class="btn btn-rounded btn-primary" data-toggle="modal"
                                                                data-target="#editModal" data-id="{{ $branch->id }}"
                                                                data-name="{{ $branch->name }}"
                                                                data-code="{{ $branch->code }}">Edit</button>

                                                            <!-- View Button -->
                                                            <button class="btn btn-rounded btn-info" data-toggle="modal"
                                                                data-target="#viewModal" data-id="{{ $branch->id }}"
                                                                data-name="{{ $branch->name }}"
                                                                data-code="{{ $branch->code }}"
                                                                data-members="{{ $branch->members->count() }}">View</button>

                                                            <!-- Delete Button -->
                                                            <button class="btn btn-rounded btn-danger" data-toggle="modal"
                                                                data-target="#deleteModal"
                                                                data-id="{{ $branch->id }}">Delete</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>

                                        <tfoot>
                                            <tr>
                                                <th>Name</th>
                                                <th>Code</th>
                                                <th>Members</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <h5 class="modal-title" id="viewModalLabel">Branch Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Name: </strong><span id="viewBranchName"></span></p>
                    <p><strong>Code: </strong><span id="viewBranchCode"></span></p>
                    <p><strong>Members: </strong><span id="viewBranchMembers"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Branch</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST" id="editForm">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="name">Branch Name</label>
                            <input type="text" class="form-control" id="editBranchName" name="name">
                        </div>
                        <div class="form-group">
                            <label for="code">Branch Code</label>
                            <input type="text" class="form-control" id="editBranchCode" name="code">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assign Member to Branch</label>
                            <form action="{{ route('branches.assignMember') }}" method="POST" class="d-flex align-items-center flex-column">
                                @csrf
                                <input type="hidden" name="branch_id" id="assignBranchId" value="">
                                <input type="text" class="form-control mb-2" id="assignMemberSearch" placeholder="Search member by name or CID...">
                                <select name="member_id" class="form-control me-2" required id="assignMemberSelect">
                                    <option value="">Select Member</option>
                                    @foreach (App\Models\Member::whereNull('branch_id')->get() as $member)
                                        <option value="{{ $member->id }}">{{ $member->fname }} {{ $member->lname }} ({{ $member->cid }})</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary ms-2 mt-2">Assign</button>
                            </form>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" form="editForm">Save changes</button>
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
                    <h5 class="modal-title" id="deleteModalLabel">Delete Branch</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this branch?</p>
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
            var name = button.data('name');
            var code = button.data('code');
            var modal = $(this);
            modal.find('#editBranchName').val(name);
            modal.find('#editBranchCode').val(code);
            modal.find('#editForm').attr('action', '/branches/' + id);
            modal.find('#assignBranchId').val(id); // Set branch ID for the assign form
            modal.find('#assignMemberSearch').val('');
            modal.find('#assignMemberSelect option').show();
        });

        // Assign Member Search Filtering
        $(document).on('input', '#assignMemberSearch', function() {
            var search = $(this).val().toLowerCase();
            $('#assignMemberSelect option').each(function() {
                var text = $(this).text().toLowerCase();
                if (search === '' || text.includes(search)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // View Modal
        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var name = button.data('name');
            var code = button.data('code');
            var members = button.data('members');

            var modal = $(this);
            modal.find('#viewBranchName').text(name);
            modal.find('#viewBranchCode').text(code);
            modal.find('#viewBranchMembers').text(members);
        });

        // Delete Modal
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var modal = $(this);
            modal.find('#deleteForm').attr('action', '/branches/' + id);
        });
    </script>

</body>

</html>
