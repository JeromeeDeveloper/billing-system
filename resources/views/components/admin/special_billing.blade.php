<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Special Billing - Billing and Collection</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">
    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>
<body>
    <div id="main-wrapper">
        @include('layouts.partials.header')
        @include('layouts.partials.sidebar')
        <div class="content-body">
            <div class="container-fluid">
                <div class="row page-titles mx-0 mb-3">
                    <div class="col-sm-6 p-md-0">
                        <div class="welcome-text">
                            <h4>Special Billing</h4>
                            <span class="ml-1">Upload and Process Special Billing Data</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Special Billing</li>
                        </ol>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Upload Special Billing Excel File</h4>
                                <a href="{{ route('special-billing.export') }}" class="btn btn-success">
                                    <i class="fa fa-file-excel"></i> Export Special Billing
                                </a>
                            </div>
                            <div class="card-body">
                                @if (session('success'))
                                    <div class="alert alert-success alert-dismissible fade show">
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <strong><i class="fa fa-check-circle"></i> Success!</strong>
                                        {{ session('success') }}
                                    </div>
                                @endif
                                @if (session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                                        <strong><i class="fa fa-exclamation-circle"></i> Error!</strong>
                                        {{ session('error') }}
                                    </div>
                                @endif
                                <form action="{{ route('special-billing.import') }}" method="POST" enctype="multipart/form-data" class="mb-4">
                                    @csrf
                                    <div class="form-group">
                                        <label class="font-weight-bold">Forecast File</label>
                                        <div class="custom-file mb-2">
                                            <input type="file" class="custom-file-input" name="forecast_file" id="forecast_file" accept=".xlsx,.xls,.csv" required>
                                            <label class="custom-file-label" for="forecast_file">Choose forecast file</label>
                                        </div>
                                        <label class="font-weight-bold">Detail File</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" name="detail_file" id="detail_file" accept=".xlsx,.xls,.csv" required>
                                            <label class="custom-file-label" for="detail_file">Choose detail file</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-info">
                                        <i class="fa fa-upload"></i> Upload and Process Special Billing
                                    </button>
                                </form>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Employee ID</th>
                                                <th>Name</th>
                                                <th>Amortization</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                                <th>Gross</th>
                                                <th>Office</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($specialBillings as $billing)
                                            <tr>
                                                <td>{{ $billing->employee_id }}</td>
                                                <td>{{ $billing->name }}</td>
                                                <td>{{ number_format($billing->amortization, 2) }}</td>
                                                <td>{{ $billing->start_date }}</td>
                                                <td>{{ $billing->end_date }}</td>
                                                <td>{{ number_format($billing->gross, 2) }}</td>
                                                <td>{{ $billing->office }}</td>
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
                <p>Copyright © Designed & Developed by <a href="https://mass-specc.coop/" target="_blank">MASS-SPECC COOPERATIVE</a>2025</p>
            </div>
        </div>
    </div>
    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('js/quixnav-init.js') }}"></script>
    <script src="{{ asset('js/custom.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 1500
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
</body>
</html>
