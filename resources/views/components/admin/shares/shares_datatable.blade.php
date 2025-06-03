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
                            <h4>Share Products</h4>
                            <span class="ml-1">Datatable</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active"><a href="{{ route('shares') }}">Shares</a></li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Share Products</h4>
                                <button type="button" class="btn btn-rounded btn-primary" data-toggle="modal"
                                    data-target="#addSharesModal">
                                    <span class="btn-icon-left text-primary">
                                        <i class="fa fa-plus"></i>
                                    </span>
                                    Add Share Product
                                </button>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Product Name</th>
                                                <th>Product Code</th>
                                                <th>Members Count</th>
                                                <th>Account Number</th>
                                                <th>Current Balance</th>
                                                <th>Available Balance</th>
                                                <th>Interest</th>
                                                <th>Open Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($shares as $share)
                                                <tr>
                                                    <td>{{ $share->product_name }}</td>
                                                    <td>{{ $share->product_code }}</td>
                                                    <td>{{ $share->member_count }}</td>
                                                    <td>{{ $share->account_number }}</td>
                                                    <td>₱{{ number_format($share->current_balance, 2) }}</td>
                                                    <td>₱{{ number_format($share->available_balance, 2) }}</td>
                                                    <td>₱{{ number_format($share->interest, 2) }}</td>
                                                    <td>{{ $share->open_date }}</td>
                                                    <td>
                                                        <button type="button" class="btn btn-rounded btn-primary"
                                                            data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $share->id }}"
                                                            data-product_name="{{ $share->product_name }}"
                                                            data-product_code="{{ $share->product_code }}"
                                                            data-account_number="{{ $share->account_number }}"
                                                            data-current_balance="{{ $share->current_balance }}"
                                                            data-available_balance="{{ $share->available_balance }}"
                                                            data-interest="{{ $share->interest }}"
                                                            data-open_date="{{ $share->open_date }}">
                                                            Edit
                                                        </button>

                                                        <button type="button" class="btn btn-rounded btn-info"
                                                            data-toggle="modal" data-target="#viewModal"
                                                            data-product_name="{{ $share->product_name }}"
                                                            data-product_code="{{ $share->product_code }}"
                                                            data-account_number="{{ $share->account_number }}"
                                                            data-current_balance="{{ $share->current_balance }}"
                                                            data-available_balance="{{ $share->available_balance }}"
                                                            data-interest="{{ $share->interest }}"
                                                            data-open_date="{{ $share->open_date }}">
                                                            View
                                                        </button>

                                                        <button type="button" class="btn btn-rounded btn-danger"
                                                            data-toggle="modal" data-target="#deleteModal"
                                                            data-id="{{ $share->id }}">
                                                            Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
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

    <!-- Add Modal -->
    <div class="modal fade" id="addSharesModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="{{ route('shares.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Share Product</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" class="form-control" name="product_name" required>
                        </div>
                        <div class="form-group">
                            <label>Product Code</label>
                            <input type="text" class="form-control" name="product_code" required>
                        </div>
                        <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" class="form-control" name="account_number" required>
                        </div>
                        <div class="form-group">
                            <label>Current Balance</label>
                            <input type="number" step="0.01" class="form-control" name="current_balance" required>
                        </div>
                        <div class="form-group">
                            <label>Available Balance</label>
                            <input type="number" step="0.01" class="form-control" name="available_balance" required>
                        </div>
                        <div class="form-group">
                            <label>Interest Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" name="interest" required>
                        </div>
                        <div class="form-group">
                            <label>Open Date</label>
                            <input type="date" class="form-control" name="open_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Share Product</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" class="form-control" name="product_name" id="edit-product_name" required>
                        </div>
                        <div class="form-group">
                            <label>Product Code</label>
                            <input type="text" class="form-control" name="product_code" id="edit-product_code" required>
                        </div>
                        <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" class="form-control" name="account_number" id="edit-account_number" required>
                        </div>
                        <div class="form-group">
                            <label>Current Balance</label>
                            <input type="number" step="0.01" class="form-control" name="current_balance" id="edit-current_balance" required>
                        </div>
                        <div class="form-group">
                            <label>Available Balance</label>
                            <input type="number" step="0.01" class="form-control" name="available_balance" id="edit-available_balance" required>
                        </div>
                        <div class="form-group">
                            <label>Interest Rate (%)</label>
                            <input type="number" step="0.01" class="form-control" name="interest" id="edit-interest" required>
                        </div>
                        <div class="form-group">
                            <label>Open Date</label>
                            <input type="date" class="form-control" name="open_date" id="edit-open_date" required>
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
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Share Product</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p><strong>Product Name:</strong> <span id="view-product_name"></span></p>
                    <p><strong>Product Code:</strong> <span id="view-product_code"></span></p>
                    <p><strong>Account Number:</strong> <span id="view-account_number"></span></p>
                    <p><strong>Current Balance:</strong> ₱<span id="view-current_balance"></span></p>
                    <p><strong>Available Balance:</strong> ₱<span id="view-available_balance"></span></p>
                    <p><strong>Interest Rate:</strong> <span id="view-interest"></span>%</p>
                    <p><strong>Open Date:</strong> <span id="view-open_date"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Share Product</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this share product?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="./vendor/global/global.min.js"></script>
    <script src="./js/quixnav-init.js"></script>
    <script src="./js/custom.min.js"></script>
    <script src="./vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="./js/plugins-init/datatables.init.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
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
            var id = button.data('id');

            $('#edit-product_name').val(button.data('product_name'));
            $('#edit-product_code').val(button.data('product_code'));
            $('#edit-account_number').val(button.data('account_number'));
            $('#edit-current_balance').val(button.data('current_balance'));
            $('#edit-available_balance').val(button.data('available_balance'));
            $('#edit-interest').val(button.data('interest'));
            $('#edit-open_date').val(button.data('open_date'));

            $('#editForm').attr('action', '/shares/' + id);
        });

        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#view-product_name').text(button.data('product_name'));
            $('#view-product_code').text(button.data('product_code'));
            $('#view-account_number').text(button.data('account_number'));
            $('#view-current_balance').text(button.data('current_balance'));
            $('#view-available_balance').text(button.data('available_balance'));
            $('#view-interest').text(button.data('interest'));
            $('#view-open_date').text(button.data('open_date'));
        });

        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            $('#deleteForm').attr('action', '/shares/' + id);
        });
    </script>


</body>

</html>
