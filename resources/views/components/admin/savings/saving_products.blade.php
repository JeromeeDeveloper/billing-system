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

<style>
    p.text-muted.mb-2.mb-md-0 {
    display: none;
}
.flex.justify-between.flex-1.sm\:hidden {
    display: none;
}
</style>

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
                            <h4>Saving Products</h4>
                            <span class="ml-1">Management</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active"><a href="{{ route('saving-products.index') }}">Saving Products</a></li>
                        </ol>
                    </div>
                </div>

               

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Saving Products</h4>
                                <div class="d-flex flex-wrap mt-3">
                                    <button type="button" class="btn btn-primary mb-2" data-toggle="modal" data-target="#addProductModal">
                                        <i class="fa fa-plus mr-1"></i> Add Saving Product
                                    </button>
                                </div>
                            </div>

                            <!-- Search and Filter Section -->
                            <div class="card-body">
                                <form method="GET" action="{{ route('saving-products.index') }}" id="searchForm">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="search"
                                                       placeholder="Search saving products..."
                                                       value="{{ request('search') }}">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="submit">
                                                        <i class="fa fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-control" name="per_page" onchange="this.form.submit()">
                                                <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>Show 10 entries</option>
                                                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>Show 25 entries</option>
                                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>Show 50 entries</option>
                                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>Show 100 entries</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            @if(request('search'))
                                                <a href="{{ route('saving-products.index') }}" class="btn btn-outline-secondary">
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
                                            Showing {{ $savingProducts->firstItem() ?? 0 }} to {{ $savingProducts->lastItem() ?? 0 }}
                                            of {{ $savingProducts->total() }} entries
                                            @if(request('search'))
                                                for "<strong>{{ request('search') }}</strong>"
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <!-- Data Table -->
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="thead-primary">
                                            <tr>
                                                <th>Product Name</th>
                                                <th>Product Code</th>
                                                <th>Product Type</th>
                                                <th>Amount to Deduct</th>
                                                <th>Prioritization</th>
                                                <th width="200">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($savingProducts as $product)
                                                <tr>
                                                    <td>{{ $product->product_name }}</td>
                                                    <td>{{ $product->product_code }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $product->product_type === 'mortuary' ? 'danger' : ($product->product_type === 'atm' ? 'warning' : 'success') }}">
                                                            {{ ucfirst($product->product_type ?? 'N/A') }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $product->amount_to_deduct ? number_format($product->amount_to_deduct, 2) : 'N/A' }}</td>
                                                    <td>{{ $product->prioritization ?? 'N/A' }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-info"
                                                                data-toggle="modal" data-target="#viewModal"
                                                                data-id="{{ $product->id }}"
                                                                data-product_name="{{ $product->product_name }}"
                                                                data-product_code="{{ $product->product_code }}"
                                                                data-product_type="{{ $product->product_type }}"
                                                                data-amount_to_deduct="{{ $product->amount_to_deduct }}"
                                                                data-prioritization="{{ $product->prioritization }}">
                                                                <i class="fa fa-eye"></i> View
                                                            </button>

                                                            <button type="button" class="btn btn-sm btn-primary"
                                                                data-toggle="modal" data-target="#editModal"
                                                                data-id="{{ $product->id }}"
                                                                data-product_name="{{ $product->product_name }}"
                                                                data-product_code="{{ $product->product_code }}"
                                                                data-product_type="{{ $product->product_type }}"
                                                                data-amount_to_deduct="{{ $product->amount_to_deduct }}"
                                                                data-prioritization="{{ $product->prioritization }}">
                                                                <i class="fa fa-edit"></i> Edit
                                                            </button>

                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                data-toggle="modal" data-target="#deleteModal"
                                                                data-id="{{ $product->id }}"
                                                                data-product_name="{{ $product->product_name }}">
                                                                <i class="fa fa-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">
                                                        <div class="alert alert-info mb-0">
                                                            <i class="fa fa-info-circle"></i> No saving products found.
                                                            @if(request('search'))
                                                                Try adjusting your search criteria.
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                @if($savingProducts->hasPages())
                                    <div class="mt-4">
                                        <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-2 text-center">
                                            <p class="text-muted mb-2 mb-md-0">
                                                Showing <strong>{{ $savingProducts->firstItem() ?? 0 }}</strong> to
                                                <strong>{{ $savingProducts->lastItem() ?? 0 }}</strong>
                                                of <strong>{{ $savingProducts->total() }}</strong> entries
                                            </p>
                                            <div>
                                                {{ $savingProducts->appends(request()->except('page'))->links() }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Add Product Modal -->
                            <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <form action="{{ route('saving-products.store') }}" method="POST" id="addProductForm">
                                        @csrf
                                        <!-- Preserve search and pagination parameters -->
                                        @if(request('search'))
                                            <input type="hidden" name="search" value="{{ request('search') }}">
                                        @endif
                                        @if(request('per_page'))
                                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                        @endif
                                        @if(request('page'))
                                            <input type="hidden" name="page" value="{{ request('page') }}">
                                        @endif

                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Add New Saving Product</h5>
                                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Product Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="product_name" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Product Code <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="product_code" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Product Type</label>
                                                    <select class="form-control" name="product_type">
                                                        <option value="">Select Product Type</option>
                                                        <option value="regular">Regular</option>
                                                        <option value="mortuary">Mortuary</option>
                                                        <option value="atm">ATM</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Amount to Deduct</label>
                                                    <input type="number" step="0.01" class="form-control" name="amount_to_deduct" placeholder="Enter amount (optional)">
                                                </div>
                                                <div class="form-group">
                                                    <label>Prioritization</label>
                                                    <input type="number" class="form-control" name="prioritization" placeholder="Enter prioritization number">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fa fa-save"></i> Submit
                                                </button>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
                                        @if(request('search'))
                                            <input type="hidden" name="search" value="{{ request('search') }}">
                                        @endif
                                        @if(request('per_page'))
                                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                        @endif
                                        @if(request('page'))
                                            <input type="hidden" name="page" value="{{ request('page') }}">
                                        @endif

                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Saving Product</h5>
                                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="form-group col-md-6">
                                                        <label>Product Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="product_name" id="edit-product_name" required>
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <label>Product Code <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" name="product_code" id="edit-product_code" required>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="form-group col-md-6">
                                                        <label>Product Type</label>
                                                        <select class="form-control" name="product_type" id="edit-product_type">
                                                            <option value="">Select Product Type</option>
                                                            <option value="regular">Regular</option>
                                                            <option value="mortuary">Mortuary</option>
                                                            <option value="atm">ATM</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <label>Amount to Deduct</label>
                                                        <input type="number" step="0.01" class="form-control" name="amount_to_deduct" id="edit-amount_to_deduct" placeholder="Enter amount (optional)">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Prioritization</label>
                                                    <input type="number" class="form-control" name="prioritization" id="edit-prioritization" placeholder="Enter prioritization number">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fa fa-save"></i> Save Changes
                                                </button>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
                                            <h5 class="modal-title">View Saving Product Details</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Product Name:</strong> <span id="view-product_name"></span></p>
                                                    <p><strong>Product Code:</strong> <span id="view-product_code"></span></p>
                                                    <p><strong>Product Type:</strong> <span id="view-product_type"></span></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Amount to Deduct:</strong> <span id="view-amount_to_deduct"></span></p>
                                                    <p><strong>Prioritization:</strong> <span id="view-prioritization"></span></p>
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
                            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <form id="deleteForm" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <!-- Preserve search and pagination parameters -->
                                        @if(request('search'))
                                            <input type="hidden" name="search" value="{{ request('search') }}">
                                        @endif
                                        @if(request('per_page'))
                                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                                        @endif
                                        @if(request('page'))
                                            <input type="hidden" name="page" value="{{ request('page') }}">
                                        @endif

                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Delete Saving Product</h5>
                                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-warning">
                                                    <i class="fa fa-exclamation-triangle"></i>
                                                    <strong>Warning:</strong> This action cannot be undone.
                                                </div>
                                                <p>Are you sure you want to delete the saving product "<strong id="delete-product-name"></strong>"?</p>
                                                <p class="text-muted"><small>This will also remove all member associations.</small></p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
                        target="_blank">MASS-SPECC COOPERATIVE</a>2025</p>
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

            $('#edit-product_name').val(button.data('product_name'));
            $('#edit-product_code').val(button.data('product_code'));
            $('#edit-product_type').val(button.data('product_type'));
            $('#edit-amount_to_deduct').val(button.data('amount_to_deduct'));
            $('#edit-prioritization').val(button.data('prioritization'));

            // Update form action URL
            $('#editForm').attr('action', '/saving-products/' + button.data('id'));
        });

        // View modal fill
        $('#viewModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#view-product_name').text(button.data('product_name'));
            $('#view-product_code').text(button.data('product_code'));
            $('#view-product_type').text(button.data('product_type') ? button.data('product_type').charAt(0).toUpperCase() + button.data('product_type').slice(1) : 'N/A');
            $('#view-amount_to_deduct').text(button.data('amount_to_deduct') ? parseFloat(button.data('amount_to_deduct')).toFixed(2) : 'N/A');
            $('#view-prioritization').text(button.data('prioritization') || 'N/A');
        });

        // Delete modal fill
        $('#deleteModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);

            $('#delete-product-name').text(button.data('product_name'));
            $('#deleteForm').attr('action', '/saving-products/' + button.data('id'));
        });

        // Auto-submit form when per_page changes
        $('select[name="per_page"]').on('change', function() {
            $('#searchForm').submit();
        });

        // Form validation
        $('#addProductForm, #editForm').on('submit', function() {
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
