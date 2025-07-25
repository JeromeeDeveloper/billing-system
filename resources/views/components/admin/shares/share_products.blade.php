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
                            <h4>Share Products</h4>
                            <span class="ml-1">Management</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active"><a href="{{ route('share-products.index') }}">Share Products</a></li>
                        </ol>
                    </div>
                </div>

                <div class="alert alert-info alert-dismissible fade show mb-4">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h5><i class="fa fa-info-circle"></i> Share Products Management Flow</h5>
                    <ol class="mb-2">
                        <li><strong>Add Product:</strong> Admin can add new share products to the system.</li>
                        <li><strong>Edit Product:</strong> Edit existing share products as needed.</li>
                        <li><strong>View Product:</strong> View product details and associated members.</li>
                        <li><strong>Delete Product:</strong> Remove share products that are no longer needed.</li>
                    </ol>
                    <ul class="mb-2">
                        <li><strong>Impact:</strong> Share products affect available options for member shares and remittance processing.</li>
                    </ul>
                    <p class="mb-0"><small><strong>Note:</strong> Manage share products carefully to ensure accurate billing and remittance options for members.</small></p>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Share Products</h4>
                                <button type="button" class="btn btn-rounded btn-primary" data-toggle="modal"
                                    data-target="#addProductModal">
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
                                                <th>Amount to Deduct</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($shareProducts as $product)
                                            <tr>
                                                <td>{{ $product->product_name }}</td>
                                                <td>{{ $product->product_code }}</td>
                                                <td>{{ $product->amount_to_deduct}}</td>
                                                <td>
                                                    <button type="button" class="btn btn-rounded btn-info"
                                                        data-toggle="modal" data-target="#viewModal{{ $product->id }}">
                                                        View
                                                    </button>

                                                    <button type="button" class="btn btn-rounded btn-primary"
                                                        data-toggle="modal" data-target="#editModal{{ $product->id }}">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-rounded btn-danger"
                                                        data-toggle="modal" data-target="#deleteModal{{ $product->id }}">
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
                        target="_blank">MASS-SPECC COOPERATIVE</a>2025</p>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="{{ route('share-products.store') }}" method="POST">
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
                            <label>Amount to Deduct</label>
                            <input type="number" step="0.01" class="form-control" name="amount_to_deduct" value="{{ isset($product) && $product->amount_to_deduct > 0 ? $product->amount_to_deduct : '' }}" placeholder="Enter amount (optional)">
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

    @foreach($shareProducts as $product)
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal{{ $product->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="{{ route('share-products.update', $product->id) }}" method="POST">
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
                            <input type="text" class="form-control" name="product_name" value="{{ $product->product_name }}" required>
                        </div>
                        <div class="form-group">
                            <label>Product Code</label>
                            <input type="text" class="form-control" name="product_code" value="{{ $product->product_code }}" required>
                        </div>
                        <div class="form-group">
                            <label>Amount to Deduct</label>
                            <input type="number" step="0.01" class="form-control" name="amount_to_deduct" value="{{ $product->amount_to_deduct }}" placeholder="Enter amount (optional)">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- View Members Modal -->
    <div class="modal fade" id="viewModal{{ $product->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Members with {{ $product->product_name }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Member Name</th>
                                <th>Account Number</th>
                                <th>Current Balance</th>
                                <th>Available Balance</th>
                                <th>Interest</th>
                                <th>Open Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->members as $member)
                            <tr>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->pivot->account_number }}</td>
                                <td>₱{{ number_format($member->pivot->current_balance, 2) }}</td>
                                <td>₱{{ number_format($member->pivot->available_balance, 2) }}</td>
                                <td>{{ $member->pivot->interest }}%</td>
                                <td>{{ $member->pivot->open_date }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal{{ $product->id }}" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="{{ route('share-products.destroy', $product->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Share Product</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this share product? This will also remove all member associations.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endforeach

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
</body>
</html>
