<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Remittance Upload - Billing and Collection</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">
    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
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
                            <h4>Remittance Upload</h4>
                            <span class="ml-1">Upload Remittance Data</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Remittance Upload</li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Upload Remittance Excel File</h4>
                            </div>
                            <div class="card-body">
                                @if(session('success'))
                                    <div class="alert alert-success">
                                        {{ session('success') }}
                                    </div>
                                @endif

                                @if(session('error'))
                                    <div class="alert alert-danger">
                                        {{ session('error') }}
                                    </div>
                                @endif

                                <form action="{{ route('remittance.upload') }}" method="POST" enctype="multipart/form-data" class="mb-4">
                                    @csrf
                                    <div class="form-group">
                                        <label>Excel File</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" name="file" id="file" accept=".xlsx,.xls" required>
                                            <label class="custom-file-label" for="file">Choose file</label>
                                        </div>
                                        <small class="form-text text-muted">
                                            File must be Excel with headers: EmpId, Name, Loans, Regular Savings, Savings 2
                                        </small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Upload and Process</button>
                                </form>

                                @if(session('preview'))
                                    <div class="preview-section mt-4">
                                        <h4>Upload Preview</h4>
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <div class="card bg-success text-white">
                                                    <div class="card-body">
                                                        <h5>Matched Records</h5>
                                                        <h3>{{ session('stats.matched') ?? 0 }}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-danger text-white">
                                                    <div class="card-body">
                                                        <h5>Unmatched Records</h5>
                                                        <h3>{{ session('stats.unmatched') ?? 0 }}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-info text-white">
                                                    <div class="card-body">
                                                        <h5>Total Amount</h5>
                                                        <h3>₱{{ number_format(session('stats.total_amount') ?? 0, 2) }}</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Status</th>
                                                        <th>EmpId</th>
                                                        <th>Name</th>
                                                        <th>Loans</th>
                                                        <th>Regular Savings</th>
                                                        <th>Savings 2</th>
                                                        <th>Message</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach(session('preview') as $row)
                                                        <tr class="{{ $row['status'] === 'success' ? 'table-success' : 'table-danger' }}">
                                                            <td>
                                                                @if($row['status'] === 'success')
                                                                    <span class="badge badge-success">Matched</span>
                                                                @else
                                                                    <span class="badge badge-danger">Unmatched</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $row['emp_id'] }}</td>
                                                            <td>{{ $row['name'] }}</td>
                                                            <td>₱{{ number_format($row['loans'], 2) }}</td>
                                                            <td>₱{{ number_format($row['regular_savings'], 2) }}</td>
                                                            <td>₱{{ number_format($row['savings_2'], 2) }}</td>
                                                            <td>{{ $row['message'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/" target="_blank">MASS-SPECC COOPERATIVE</a>2025</p>
            </div>
        </div>
    </div>

    @include('layouts.partials.footer')

    <script>
        // Update custom file input label
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });

        // Initialize DataTable if preview exists
        $(document).ready(function() {
            if ($('.table').length) {
                $('.table').DataTable({
                    pageLength: 25,
                    ordering: true
                });
            }
        });
    </script>
</body>
</html>
