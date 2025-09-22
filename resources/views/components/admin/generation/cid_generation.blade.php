<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>CID Generation</title>

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
                            <h4>CID Generation</h4>
                            <span class="ml-1">Generate CIDs from Excel file</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active"><a href="{{ route('admin.cid-generation') }}">CID Generation</a></li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">CID Generation</h4>
                                <p class="text-muted">Upload an Excel file with member names to generate CIDs. The file should have "NAME" in Column A (header) and names in "LASTNAME, FIRSTNAME" format below.</p>
                            </div>
                            <div class="card-body">
                                <form id="cidGenerationForm" action="{{ route('admin.cid-generation.process') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="excel_file">Excel File</label>
                                                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                                                <small class="form-text text-muted">Upload Excel file with member names. Expected format: Column A header "NAME", data: "ABAD, EDMELYN PEREZ"</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="name_format">Name Format</label>
                                                <select class="form-control" id="name_format" name="name_format">
                                                    <option value="auto" selected>Auto-detect (recommended)</option>
                                                    <option value="last_first">LASTNAME, FIRSTNAME</option>
                                                    <option value="first_last">FIRSTNAME LASTNAME</option>
                                                    <option value="two_columns">Two columns: LASTNAME (A) + FIRSTNAME (B)</option>
                                                </select>
                                                <small class="form-text text-muted">Choose input name layout.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button type="submit" class="btn btn-primary btn-block" id="generateBtn">
                                                    <i class="bi bi-file-earmark-excel"></i> Generate CIDs
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                @if(session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                                        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                @endif

                                @if($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                                        <ul class="mb-0">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">How it works</h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h5><i class="bi bi-info-circle"></i> Process Flow:</h5>
                                    <ol>
                                        <li>Upload your Excel file with "NAME" column containing names in "LASTNAME, FIRSTNAME" format</li>
                                        <li>System will match names with existing members in the database</li>
                                        <li>Download the generated Excel file with all original data plus a new "Matched CID" column</li>
                                        <li>Matched CIDs will be populated for successful matches, empty for no matches</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}
            </div>
        </div>

        @include('layouts.partials.footer')

    </div>

    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('vendor/chart.js/Chart.bundle.min.js') }}"></script>
    <script src="{{ asset('js/custom.min.js') }}"></script>
    <script src="{{ asset('js/deznav-init.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins-init/datatables.init.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Form submission with simple alert
            $('#cidGenerationForm').on('submit', function(e) {
                const fileInput = $('#excel_file')[0];
                if (!fileInput.files.length) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'No File Selected',
                        text: 'Please select an Excel file to upload.',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }

                // Show simple processing message in upper right
                Swal.fire({
                    title: 'Processing Request',
                    text: 'Your file will be downloaded shortly',
                    icon: 'info',
                    timer: 2000,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true
                });
            });

            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        });
    </script>

</body>

</html>
