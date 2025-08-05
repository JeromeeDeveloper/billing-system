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
                                <div class="d-flex flex-wrap mt-3">
                                    <a href="{{ route('loans.export') }}" class="btn btn-success mr-2 mb-2">
                                        <i class="fa fa-file-excel-o mr-1"></i> Export Excel
                                    </a>
                                    <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#addLoanModal">
                                        <i class="fa fa-plus mr-1"></i> Add Loan
                                    </button>
                                </div>
                            </div>

                            <!-- Search and Filter Section -->
                            <div class="card-body">
                                <form method="GET" action="{{ route('loans') }}" id="searchForm">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="search"
                                                    placeholder="Search loans..." value="{{ request('search') }}">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="submit">
                                                        <i class="fa fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-control" name="per_page" onchange="this.form.submit()">
                                                <option value="10"
                                                    {{ request('per_page', 10) == 10 ? 'selected' : '' }}>Show 10
                                                    entries</option>
                                                <option value="25"
                                                    {{ request('per_page') == 25 ? 'selected' : '' }}>Show 25 entries
                                                </option>
                                                <option value="50"
                                                    {{ request('per_page') == 50 ? 'selected' : '' }}>Show 50 entries
                                                </option>
                                                <option value="100"
                                                    {{ request('per_page') == 100 ? 'selected' : '' }}>Show 100 entries
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            @if (request('search'))
                                                <a href="{{ route('loans') }}" class="btn btn-outline-secondary">
                                                    <i class="fa fa-times"></i> Clear Search
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </form>

                                <!-- Results Summary -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="text-muted">
                                            Showing {{ $loans->firstItem() ?? 0 }} to {{ $loans->lastItem() ?? 0 }}
                                            of {{ $loans->total() }} entries
                                            @if (request('search'))
                                                for "<strong>{{ request('search') }}</strong>"
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <!-- Data Table -->
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Product</th>
                                                <th>Product Code</th>
                                                <th>Prioritization</th>
                                                <th>Billing Type</th>
                                                <th width="200">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($loans as $loan)
                                                <tr>
                                                    <td>{{ $loan->product ?? '' }}</td>
                                                    <td>{{ $loan->product_code ?? '' }}</td>
                                                    <td>{{ $loan->prioritization ?? '' }}</td>
                                                    <td>
                                                        <span
                                                            class="badge badge-{{ $loan->billing_type == 'regular' ? 'success' : ($loan->billing_type == 'special' ? 'warning' : 'secondary') }}">
                                                            {{ ucfirst($loan->billing_type ?? 'N/A') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-primary"
                                                                data-toggle="modal" data-target="#editModal"
                                                                data-id="{{ $loan->id }}"
                                                                data-product="{{ $loan->product }}"
                                                                data-product_code="{{ $loan->product_code }}"
                                                                data-prioritization="{{ $loan->prioritization }}"
                                                                data-billing_type="{{ $loan->billing_type }}"
                                                                data-remarks="{{ $loan->remarks ?? '' }}">
                                                                <i class="fa fa-edit"></i> Edit
                                                            </button>

                                                            <button type="button" class="btn btn-sm btn-info"
                                                                data-toggle="modal" data-target="#viewModal"
                                                                data-id="{{ $loan->id }}"
                                                                data-product="{{ $loan->product }}"
                                                                data-product_code="{{ $loan->product_code }}"
                                                                data-prioritization="{{ $loan->prioritization }}"
                                                                data-billing_type="{{ $loan->billing_type }}"
                                                                data-remarks="{{ $loan->remarks ?? '' }}">
                                                                <i class="fa fa-eye"></i> View
                                                            </button>

                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                data-toggle="modal" data-target="#deleteModal"
                                                                data-id="{{ $loan->id }}"
                                                                data-product="{{ $loan->product }}">
                                                                <i class="fa fa-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">
                                                        <div class="alert alert-info mb-0">
                                                            <i class="fa fa-info-circle"></i> No loans found.
                                                            @if (request('search'))
                                                                Try adjusting your search criteria.
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <style>
                                    p.text-muted.mb-2.mb-md-0 {
                                        display: none;
                                    }

                                    .flex.justify-between.flex-1.sm\:hidden {
                                        display: none;
                                    }
                                </style>
                                <!-- Pagination -->
                                @if ($loans->hasPages())
                                    <div class="mt-4">
                                        <div
                                            class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-2 text-center">
                                            <p class="text-muted mb-2 mb-md-0">
                                                Showing <strong>{{ $loans->firstItem() ?? 0 }}</strong> to
                                                <strong>{{ $loans->lastItem() ?? 0 }}</strong>
                                                of <strong>{{ $loans->total() }}</strong> entries
                                            </p>
                                            <div>
                                                {{ $loans->appends(request()->except('page'))->links() }}
                                            </div>
                                        </div>
                                    </div>
                                @endif

                            </div>

                            <!-- Add Loan Modal -->
                            <div class="modal fade" id="addLoanModal" tabindex="-1" role="dialog"
                                aria-labelledby="addLoanModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <form id="addLoanForm" method="POST" action="{{ route('loans.store') }}">
                                        @csrf
                                        <!-- Preserve search and pagination parameters -->
                                        @if (request('search'))
                                            <input type="hidden" name="search" value="{{ request('search') }}">
                                        @endif
                                        @if (request('per_page'))
                                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                        @endif
                                        @if (request('page'))
                                            <input type="hidden" name="page" value="{{ request('page') }}">
                                        @endif

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
                                                    <label for="add-product">Product <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="product"
                                                        id="add-product" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="add-product-code">Product Code <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="product_code"
                                                        id="add-product-code" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="add-prioritization">Prioritization <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="prioritization"
                                                        id="add-prioritization" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="add-billing_type">Billing Type</label>
                                                    <select class="form-control" name="billing_type"
                                                        id="add-billing_type">
                                                        <option value="">Select Billing Type</option>
                                                        <option value="regular">Regular</option>
                                                        <option value="special">Special</option>
                                                        <option value="not_billed">Not Billed</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fa fa-save"></i> Submit
                                                </button>
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Cancel</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-lg" role="document">
                                    <form id="editForm" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <!-- Preserve search and pagination parameters -->
                                        @if (request('search'))
                                            <input type="hidden" name="search" value="{{ request('search') }}">
                                        @endif
                                        @if (request('per_page'))
                                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                        @endif
                                        @if (request('page'))
                                            <input type="hidden" name="page" value="{{ request('page') }}">
                                        @endif

                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Loan</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>

                                            <div class="modal-body">
                                                <input type="hidden" name="id" id="edit-id">

                                                <div class="row">
                                                    <div class="form-group col-md-6">
                                                        <label for="edit-product">Product <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="product"
                                                            id="edit-product" required>
                                                    </div>

                                                    <div class="form-group col-md-6">
                                                        <label for="edit-product_code">Product Code <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" class="form-control"
                                                            name="product_code" id="edit-product_code" required>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="form-group col-md-6">
                                                        <label for="edit-prioritization">Prioritization <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text" class="form-control"
                                                            name="prioritization" id="edit-prioritization" required>
                                                    </div>

                                                    <div class="form-group col-md-6">
                                                        <label for="edit-billing_type">Billing Type</label>
                                                        <select class="form-control" name="billing_type"
                                                            id="edit-billing_type">
                                                            <option value="">Select Billing Type</option>
                                                            <option value="regular">Regular</option>
                                                            <option value="special">Special</option>
                                                            <option value="not_billed">Not Billed</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="edit-remarks">Remarks</label>
                                                    <textarea class="form-control" name="remarks" id="edit-remarks" rows="3"
                                                        placeholder="Enter any additional remarks..."></textarea>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fa fa-save"></i> Save Changes
                                                </button>
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Cancel</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">View Loan Details</h5>
                                            <button type="button" class="close"
                                                data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Product:</strong> <span id="view-product"></span></p>
                                                    <p><strong>Product Code:</strong> <span
                                                            id="view-product-code"></span></p>
                                                    <p><strong>Prioritization:</strong> <span
                                                            id="view-prioritization"></span></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Billing Type:</strong> <span
                                                            id="view-billing_type"></span></p>
                                                    <p><strong>Remarks:</strong> <span id="view-remarks"></span></p>
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

                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <form id="deleteForm" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <!-- Preserve search and pagination parameters -->
                                        @if (request('search'))
                                            <input type="hidden" name="search" value="{{ request('search') }}">
                                        @endif
                                        @if (request('per_page'))
                                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                        @endif
                                        @if (request('page'))
                                            <input type="hidden" name="page" value="{{ request('page') }}">
                                        @endif

                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Delete Loan Product</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-warning">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                    <strong>Warning:</strong> This action cannot be undone.
                                                </div>
                                                <p>Are you sure you want to delete the loan product "<strong
                                                        id="delete-product-name"></strong>"?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Cancel</button>
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
                title: 'Success!',
                text: '{{ session('success') }}',
                timer: 3000,
                timerProgressBar: true
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
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

            // Update form action URL
            $('#editForm').attr('action', '/loans/' + button.data('id'));
        });

        // View modal fill
        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#view-product').text(button.data('product'));
            $('#view-product-code').text(button.data('product_code'));
            $('#view-prioritization').text(button.data('prioritization'));
            $('#view-billing_type').text(button.data('billing_type') ? button.data('billing_type').charAt(0)
                .toUpperCase() + button.data('billing_type').slice(1) : 'N/A');
            $('#view-remarks').text(button.data('remarks') || 'No remarks');
        });

        // Delete modal fill
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#delete-product-name').text(button.data('product'));
            $('#deleteForm').attr('action', '/loans/' + button.data('id'));
        });

        // Auto-submit form when per_page changes
        $('select[name="per_page"]').on('change', function() {
            $('#searchForm').submit();
        });

        // Form validation
        $('#addLoanForm, #editForm').on('submit', function() {
            var isValid = true;
            $(this).find('[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            return isValid;
        });

        // Remove validation styling on input
        $('input, select').on('input change', function() {
            if ($(this).val()) {
                $(this).removeClass('is-invalid');
            }
        });
    </script>

</body>

</html>
