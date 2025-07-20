<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Contra Account - Admin</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff;
            border: 1px solid #007bff;
            color: #fff;
        }
    </style>
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
                            <h4>Contra Account (Admin)</h4>
                            <span class="ml-1">Create Contra Account Link</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                @if(session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                <form method="POST" action="{{ route('admin.contra') }}">
                                    @csrf
                                    <div class="form-group">
                                        <label for="type">Type</label>
                                        <select class="form-control" id="type" name="type" required>
                                            <option value="">Select Type</option>
                                            <option value="shares">Shares</option>
                                            <option value="savings">Savings</option>
                                            <option value="loans">Loans</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="account-input-group">
                                        <label for="account_numbers">GL Account Number(s)</label>
                                        <input type="text" class="form-control" id="account_numbers" name="account_numbers" placeholder="Enter GL account number(s), comma-separated if multiple" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Contra Table Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Contra Account Links</h5>
                                <form method="GET" action="" class="form-inline">
                                    <input type="text" name="search" class="form-control mr-2" placeholder="Search type or account..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-outline-primary">Search</button>
                                </form>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Type</th>
                                                <th>GL Accounts</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($contraAccs as $contra)
                                                <tr>
                                                    <td>{{ $contra->id }}</td>
                                                    <td>{{ ucfirst($contra->type) }}</td>
                                                    <td>{{ $contra->account_number ?? '-' }}</td>
                                                    <td>{{ $contra->created_at ? \Carbon\Carbon::parse($contra->created_at)->format('Y-m-d H:i') : '-' }}</td>
                                                    <td>
                                                        <!-- View Button -->
                                                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#viewContraModal{{ $contra->id }}">View</button>
                                                        <!-- Edit Button -->
                                                        <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editContraModal{{ $contra->id }}">Edit</button>
                                                        <!-- Delete Button -->
                                                        <button type="button" class="btn btn-sm btn-danger delete-contra-btn" data-id="{{ $contra->id }}">Delete</button>
                                                        <form id="delete-contra-form-{{ $contra->id }}" action="{{ route('admin.contra.delete', $contra->id) }}" method="POST" style="display:none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    </td>
                                                </tr>
                                                <!-- View Modal -->
                                                <div class="modal fade" id="viewContraModal{{ $contra->id }}" tabindex="-1" role="dialog" aria-labelledby="viewContraModalLabel{{ $contra->id }}" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="viewContraModalLabel{{ $contra->id }}">Contra Account Details</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p><strong>ID:</strong> {{ $contra->id }}</p>
                                                                <p><strong>Type:</strong> {{ ucfirst($contra->type) }}</p>
                                                                <p><strong>GL Account Number:</strong> {{ $contra->account_number ?? '-' }}</p>
                                                                <p><strong>Created At:</strong> {{ $contra->created_at ? \Carbon\Carbon::parse($contra->created_at)->format('Y-m-d H:i') : '-' }}</p>
                                                                <p><strong>Updated At:</strong> {{ $contra->updated_at ? \Carbon\Carbon::parse($contra->updated_at)->format('Y-m-d H:i') : '-' }}</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Edit Modal -->
                                                <div class="modal fade" id="editContraModal{{ $contra->id }}" tabindex="-1" role="dialog" aria-labelledby="editContraModalLabel{{ $contra->id }}" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <form method="POST" action="{{ route('admin.contra.update', $contra->id) }}">
                                                                @csrf
                                                                @method('PUT')
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="editContraModalLabel{{ $contra->id }}">Edit Contra Account</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="form-group">
                                                                        <label for="type{{ $contra->id }}">Type</label>
                                                                        <select class="form-control" id="type{{ $contra->id }}" name="type" required>
                                                                            <option value="shares" {{ $contra->type == 'shares' ? 'selected' : '' }}>Shares</option>
                                                                            <option value="savings" {{ $contra->type == 'savings' ? 'selected' : '' }}>Savings</option>
                                                                            <option value="loans" {{ $contra->type == 'loans' ? 'selected' : '' }}>Loans</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="account_number{{ $contra->id }}">GL Account Number</label>
                                                                        <input type="text" class="form-control" id="account_number{{ $contra->id }}" name="account_number" value="{{ $contra->account_number }}" required>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">No contra account links found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-center">
                                    {{ $contraAccs->links('pagination::bootstrap-4') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.partials.footer')
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Removed Select2 and related JS for account_numbers -->
    <script>
        $(document).ready(function() {
            $('#type').on('change', function() {
                var type = $(this).val();
                // No longer needed for account_numbers as it's a plain input
            });
            // SweetAlert2 for delete confirmation
            $('.delete-contra-btn').on('click', function(e) {
                e.preventDefault();
                var contraId = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This will permanently delete the contra account.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#delete-contra-form-' + contraId).submit();
                    }
                });
            });
        });
    </script>
</body>
</html>
