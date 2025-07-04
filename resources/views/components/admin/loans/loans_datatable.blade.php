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
                            <h4>Loans List</h4>
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
                                <h4 class="card-title mb-0">Loan Datatable</h4>
                                <div>
                                    <a href="{{ route('loans.export') }}" class="btn btn-success me-2">
                                        <i class="fa fa-file-excel-o"></i> Export to Excel
                                    </a>
                                    <button type="button" class="btn btn-rounded btn-primary" data-toggle="modal"
                                        data-target="#addLoanModal">
                                        <span class="btn-icon-left text-primary">
                                            <i class="fa fa-plus"></i>
                                        </span>
                                        Add Loan
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="addLoanModal" tabindex="-1" role="dialog"
                                aria-labelledby="addLoanModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <form id="addLoanForm" method="POST" action="{{ route('loans.store') }}">
                                        @csrf
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="addLoanModalLabel">Add New Loan</h5>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label for="add-product">Product</label>
                                                    <input type="text" class="form-control" name="product"
                                                        id="add-product" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="add-product">Product code</label>
                                                    <input type="text" class="form-control" name="product_code"
                                                        id="add-product-code" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="add-prioritization">Prioritization</label>
                                                    <input type="text" class="form-control" name="prioritization"
                                                        id="add-prioritization" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="add-billing_type">Billing Type</label>
                                                    <select class="form-control" name="billing_type" id="add-billing_type">
                                                        <option value="">Select Billing Type</option>
                                                        <option value="regular">Regular</option>
                                                        <option value="special">Special</option>
                                                    </select>
                                                </div>

                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-success">Add Loan</button>
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Cancel</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="example" class="display" style="min-width: 845px">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Product Code</th>
                                                    <th>Prioritization</th>
                                                    <th>Billing Type</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($loans as $loan)
                                                    <tr>
                                                        <td>{{ $loan->product ?? '' }}</td>
                                                        <td>{{ $loan->product_code ?? '' }}</td>
                                                        <td>{{ $loan->prioritization ?? '' }}</td>
                                                        <td>{{ $loan->billing_type ?? '' }}</td>
                                                        <td>
                                                            <button type="button" class="btn btn-rounded btn-primary"
                                                                data-toggle="modal" data-target="#editModal"
                                                                data-id="{{ $loan->id }}"
                                                                data-product="{{ $loan->product }}"
                                                                data-product_code="{{ $loan->product_code }}"
                                                                data-prioritization="{{ $loan->prioritization }}"
                                                                data-billing_type="{{ $loan->billing_type }}"
                                                               >
                                                                Edit
                                                            </button>

                                                            <button type="button" class="btn btn-rounded btn-info"
                                                                data-toggle="modal" data-target="#viewModal"
                                                                data-id="{{ $loan->id }}"
                                                                data-product="{{ $loan->product }}"
                                                                data-prioritization="{{ $loan->prioritization }}"
                                                                data-billing_type="{{ $loan->billing_type }}"
                                                               >
                                                                View
                                                            </button>

                                                            <button type="button" class="btn btn-rounded btn-danger"
                                                                data-toggle="modal" data-target="#deleteModal"
                                                                data-id="{{ $loan->id }}">
                                                                Delete
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                   <th>Product</th>
                                                    <th>Product Code</th>
                                                    <th>Prioritization</th>
                                                    <th>Billing Type</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-lg" role="document">
                                    <form id="editForm" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Loan</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>

                                            <div class="modal-body row">
                                                <input type="hidden" name="id" id="edit-id">

                                                <div class="form-group col-md-6">
                                                    <label for="edit-product">Product</label>
                                                    <input type="text" class="form-control" name="product"
                                                        id="edit-product">
                                                </div>

                                                 <div class="form-group col-md-6">
                                                    <label for="edit-product_code">Product Code</label>
                                                    <input type="text" class="form-control" name="product_code"
                                                        id="edit-product_code">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-prioritization">Prioritization</label>
                                                    <input type="text" class="form-control" name="prioritization"
                                                        id="edit-prioritization">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="edit-billing_type">Billing Type</label>
                                                    <select class="form-control" name="billing_type" id="edit-billing_type">
                                                        <option value="">Select Billing Type</option>
                                                        <option value="regular">Regular</option>
                                                        <option value="special">Special</option>
                                                    </select>
                                                </div>

                                                <div class="form-group col-md-12">
                                                    <label for="edit-remarks">Remarks</label>
                                                    <textarea class="form-control" name="remarks" id="edit-remarks" rows="2" placeholder="Remarks"></textarea>
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
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">View Loan Details</h5>
                                            <button type="button" class="close"
                                                data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="container-fluid">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <p><strong>Loan Product:</strong> <span
                                                                id="view-loan_product"></span></p>
                                                        <p><strong>Prioritization:</strong> <span
                                                                id="view-loan_prioritization"></span></p>
                                                        <p><strong>Loan Amount:</strong> <span
                                                                id="view-loan_amount"></span></p>
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
        // Edit modal fill
        $('#editModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#edit-id').val(button.data('id'));
            $('#edit-product').val(button.data('product'));
            $('#edit-product_code').val(button.data('product_code'));
            $('#edit-prioritization').val(button.data('prioritization'));
            $('#edit-billing_type').val(button.data('billing_type'));
            $('#edit-remarks').val(button.data('remarks'));


            // Update form action URL (adjust if needed)
            $('#editForm').attr('action', '/loans/' + button.data('id'));
        });

        // View modal fill
        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#view-product').text(button.data('product'));
            $('#view-product-code').text(button.data('product'));
            $('#view-prioritization').text(button.data('prioritization'));
            $('#view-billing_type').text(button.data('billing_type'));

        });

        // Delete modal fill
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#delete-id').val(button.data('id'));
            $('#deleteForm').attr('action', '/loans/' + button.data('id'));
        });
    </script>


</body>

</html>
