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

                {{-- modal --}}

                <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">View User</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p><strong>ID:</strong> <span id="view-id"></span></p>
                                <p><strong>Name:</strong> <span id="view-name"></span></p>
                                <p><strong>Email:</strong> <span id="view-email"></span></p>
                                <p><strong>Role:</strong> <span id="view-role"></span></p>
                                <p><strong>Status:</strong> <span id="view-status"></span></p>
                                <p><strong>Branch:</strong> <span id="view-branch"></span></p>
                                <p><strong>Created At:</strong> <span id="view-created"></span></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <form method="POST" action="{{ route('users.update') }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="id" id="edit-id">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit User</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" name="name" id="edit-name" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Email</label>
                                                <input type="email" name="email" id="edit-email" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input type="password" name="password" class="form-control"
                                                    placeholder="Leave blank to keep current password">
                                                <small class="text-muted">Minimum 8 characters</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Role</label>
                                                <select name="role" id="edit-role" class="form-control">
                                                    <option value="admin">Admin</option>
                                                    <option value="branch">Branch</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" id="edit-status" class="form-control">
                                                    <option value="pending">Pending</option>
                                                    <option value="approved">Approved</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Branch</label>
                                                <select name="branch_id" id="edit-branch" class="form-control">
                                                    <option value="">No Branch</option>
                                                    @foreach($branches as $branch)
                                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                    @endforeach
                                                </select>
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

                <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <form method="POST" action="{{ route('users.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="id" id="delete-id">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Delete Confirmation</h5>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to delete this user?
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-danger" type="submit">Delete</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Add User Modal --}}
                <div class="modal fade" id="addModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <form method="POST" action="{{ route('users.store') }}">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add New User</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" name="name" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Email</label>
                                                <input type="email" name="email" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input type="password" name="password" class="form-control" required>
                                                <small class="text-muted">Minimum 8 characters</small>
                                            </div>
                                            <div class="form-group">
                                                <label>Confirm Password</label>
                                                <input type="password" name="password_confirmation" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Role</label>
                                                <select name="role" class="form-control" required>
                                                    <option value="">Select Role</option>
                                                    <option value="admin">Admin</option>
                                                    <option value="branch">Branch</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control" required>
                                                    <option value="" disabled>Select Status</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="approved">Approved</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Branch</label>
                                                <select name="branch_id" class="form-control">
                                                    <option value="">No Branch</option>
                                                    @foreach($branches as $branch)
                                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Create User</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Member Datatable</h4>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addModal">
                                    <i class="fa fa-plus"></i> Add User
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>Branch</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($users as $user)
                                                <tr>
                                                    <td>{{ $user->id }}</td>
                                                    <td>{{ $user->name }}</td>
                                                    <td>{{ $user->email }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $user->role === 'admin' ? 'primary' : 'info' }}">
                                                            {{ ucfirst($user->role) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-{{ $user->status === 'approved' ? 'success' : 'warning' }}">
                                                            {{ ucfirst($user->status) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $user->branch ? $user->branch->name : 'No Branch' }}</td>
                                                    <td>
                                                        <!-- Edit Button -->
                                                        <button type="button" class="btn btn-primary btn-rounded"
                                                            data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $user->id }}"
                                                            data-name="{{ $user->name }}"
                                                            data-email="{{ $user->email }}"
                                                            data-role="{{ $user->role }}"
                                                            data-status="{{ $user->status }}"
                                                            data-branch-id="{{ $user->branch_id }}">
                                                            <i class="fa fa-pencil"></i> Edit
                                                        </button>

                                                        <!-- View Button -->
                                                        <button type="button" class="btn btn-info btn-rounded"
                                                            data-toggle="modal" data-target="#viewModal"
                                                            data-id="{{ $user->id }}"
                                                            data-name="{{ $user->name }}"
                                                            data-email="{{ $user->email }}"
                                                            data-role="{{ $user->role }}"
                                                            data-status="{{ $user->status }}"
                                                            data-branch="{{ $user->branch ? $user->branch->name : 'No Branch' }}"
                                                            data-created="{{ $user->created_at->format('M d, Y H:i:s') }}">
                                                            <i class="fa fa-eye"></i> View
                                                        </button>

                                                        <!-- Delete Button -->
                                                        <button type="button" class="btn btn-danger btn-rounded"
                                                            data-toggle="modal" data-target="#deleteModal"
                                                            data-id="{{ $user->id }}">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>

                                        <tfoot>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>Branch</th>
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

    <script>
        $('#editModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var user = {
                id: button.data('id'),
                name: button.data('name'),
                email: button.data('email'),
                role: button.data('role'),
                status: button.data('status'),
                branch_id: button.data('branch-id')
            };

            var modal = $(this)
            modal.find('#edit-id').val(user.id);
            modal.find('#edit-name').val(user.name);
            modal.find('#edit-email').val(user.email);
            modal.find('#edit-role').val(user.role);
            modal.find('#edit-status').val(user.status);
            modal.find('#edit-branch').val(user.branch_id);
        });

        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            $('#view-id').text(button.data('id'));
            $('#view-name').text(button.data('name'));
            $('#view-email').text(button.data('email'));
            $('#view-role').text(button.data('role'));
            $('#view-status').text(button.data('status'));
            $('#view-branch').text(button.data('branch'));
            $('#view-created').text(button.data('created'));
        });
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            $('#delete-id').val(button.data('id'));
        });
        // SweetAlert2 error for validation errors (e.g., password mismatch)
        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: `{!! implode('<br>', $errors->all()) !!}`,
                confirmButtonColor: '#d33',
            });
        @endif
    </script>
</body>

</html>
