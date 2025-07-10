<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">

    <link href="{{ asset('vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <title>File Retention Dashboard</title>
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
                            <h4>File Retention Dashboard</h4>
                            <span class="ml-1">Manage uploaded files and storage</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">File Retention</li>
                        </ol>
                    </div>
                </div>

                <!-- Overall Statistics -->
                <div class="row">
                    @php
                        $totalFiles = 0;
                        $totalSize = 0;
                        $filesOverLimit = 0;
                        foreach($stats as $data) {
                            $totalFiles += $data['count'];
                            $totalSize += $data['total_size_bytes'];
                            $filesOverLimit += $data['files_over_limit'];
                        }
                        $totalSizeMB = round($totalSize / 1024 / 1024, 2);
                    @endphp
                    <div class="col-xl-3 col-lg-6 col-sm-6">
                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">
                                    <span class="mr-3 bgl-primary text-primary">
                                        <i class="fa fa-file"></i>
                                    </span>
                                    <div class="media-body">
                                        <h3 class="mb-0 text-black"><span class="counter ml-0">{{ $totalFiles }}</span></h3>
                                        <p class="mb-0">Total Files</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-sm-6">
                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">
                                    <span class="mr-3 bgl-warning text-warning">
                                        <i class="fa fa-hdd-o"></i>
                                    </span>
                                    <div class="media-body">
                                        <h3 class="mb-0 text-black"><span class="counter ml-0">{{ $totalSizeMB }}</span> MB</h3>
                                        <p class="mb-0">Total Size</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-sm-6">
                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">
                                    <span class="mr-3 bgl-danger text-danger">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </span>
                                    <div class="media-body">
                                        <h3 class="mb-0 text-black"><span class="counter ml-0">{{ $filesOverLimit }}</span></h3>
                                        <p class="mb-0">Files Over Limit</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-sm-6">
                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">
                                    <span class="mr-3 bgl-info text-info">
                                        <i class="fa fa-company"></i>
                                    </span>
                                    <div class="media-body">
                                        <h3 class="mb-0 text-black"><span class="counter ml-0">{{ $maxFilesPerType }}</span></h3>
                                        <p class="mb-0">Max Files Per Type</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Type Statistics -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">File Type Statistics</h4>
                                <div class="d-flex">
                                    <button type="button" class="btn btn-primary mr-2" onclick="refreshStats()">
                                        <i class="fa fa-refresh"></i> Refresh
                                    </button>
                                    <button type="button" class="btn btn-success mr-2" onclick="createBackup()">
                                        <i class="fa fa-download"></i> Create Backup
                                    </button>

                                    <button type="button" class="btn btn-danger" onclick="performCleanupAll()">
                                        <i class="fa fa-trash"></i> Cleanup All
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Document Type</th>
                                                <th>File Count</th>
                                                <th>Total Size (MB)</th>
                                                <th>Oldest File</th>
                                                <th>Newest File</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($stats as $type => $data)
                                            <tr>
                                                <td>{{ $type }}</td>
                                                <td>{{ $data['count'] }}</td>
                                                <td>{{ $data['total_size_mb'] }}</td>
                                                <td>
                                                    @if($data['oldest_file'])
                                                        @if($type === 'Billing Exports')
                                                            {{ \Carbon\Carbon::parse($data['oldest_file'])->format('Y-m-d H:i') }}
                                                        @else
                                                            {{ $data['oldest_file']->format('Y-m-d H:i') }}
                                                        @endif
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($data['newest_file'])
                                                        @if($type === 'Billing Exports')
                                                            {{ \Carbon\Carbon::parse($data['newest_file'])->format('Y-m-d H:i') }}
                                                        @else
                                                            {{ $data['newest_file']->format('Y-m-d H:i') }}
                                                        @endif
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($data['at_limit'])
                                                        <span class="badge badge-warning">At Limit</span>
                                                    @elseif($data['files_over_limit'] > 0)
                                                        <span class="badge badge-danger">{{ $data['files_over_limit'] }} Over</span>
                                                    @else
                                                        <span class="badge badge-success">OK</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($data['count'] > 0)
                                                        <button type="button" class="btn btn-sm btn-info" onclick="viewFiles('{{ $type }}')">
                                                            <i class="fa fa-eye"></i> View Files
                                                        </button>
                                                    @endif
                                                    @if($data['files_over_limit'] > 0)

                                                        <button type="button" class="btn btn-sm btn-danger" onclick="performCleanup('{{ $type }}')">
                                                            <i class="fa fa-trash"></i> Cleanup
                                                        </button>
                                                    @else
                                                        <span class="text-muted">No action needed</span>
                                                    @endif
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
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/" target="_blank">MASS-SPECC COOPERATIVE</a> 2025</p>
            </div>
        </div>
    </div>

    <!-- Files Modal -->
    <div class="modal fade" id="filesModal" tabindex="-1" role="dialog" aria-labelledby="filesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filesModalLabel">Files for <span id="modalDocumentType"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Filter Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="billingPeriodFilter">Filter by Billing Period:</label>
                            <select id="billingPeriodFilter" class="form-control" onchange="filterFiles()">
                                <option value="">All Billing Periods</option>
                                <!-- Billing periods will be populated here -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="searchFilter">Search Files:</label>
                            <input type="text" id="searchFilter" class="form-control" placeholder="Search by filename..." onkeyup="filterFiles()">
                        </div>
                    </div>
                    <div id="filesList" class="table-responsive">
                        <!-- Files will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('js/quixnav-init.js') }}"></script>
    <script src="{{ asset('js/custom.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function refreshStats() {
            fetch('/admin/file-retention/stats')
                .then(response => response.json())
                .then(data => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to refresh statistics', 'error');
                });
        }

        let allFiles = []; // Global variable to store all files for filtering

        function viewFiles(documentType) {
            // Show loading state
            $('#filesModalLabel span').text(documentType);
            $('#filesList').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading files...</div>');
            $('#filesModal').modal('show');

            // Fetch files for this document type
            fetch('/admin/file-retention/files?document_type=' + encodeURIComponent(documentType))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allFiles = data.files; // Store all files globally
                        displayFiles(data.files, documentType);
                        populateBillingPeriodFilter(data.files);
                    } else {
                        $('#filesList').html('<div class="alert alert-danger">' + data.message + '</div>');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    $('#filesList').html('<div class="alert alert-danger">Failed to load files</div>');
                });
        }

                function displayFiles(files, documentType) {
            if (files.length === 0) {
                $('#filesList').html('<div class="alert alert-info">No files found for this document type.</div>');
                return;
            }

            let tableHtml = `
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Billing Period</th>
                            <th>Size</th>
                            <th>Upload Date</th>
                            <th>Uploaded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            files.forEach(file => {
                const fileSize = formatFileSize(file.size);
                const uploadDate = new Date(file.upload_date).toLocaleString();
                const uploadedBy = file.uploaded_by_name || 'Unknown';
                const billingPeriod = file.billing_period || 'N/A';

                tableHtml += `
                    <tr data-billing-period="${billingPeriod}" data-filename="${file.filename.toLowerCase()}">
                        <td>${file.filename}</td>
                        <td><span class="badge badge-info">${billingPeriod}</span></td>
                        <td>${fileSize}</td>
                        <td>${uploadDate}</td>
                        <td>${uploadedBy}</td>
                        <td>
                            <a href="${file.download_url}" class="btn btn-sm btn-primary" target="_blank">
                                <i class="fa fa-download"></i> Download
                            </a>
                        </td>
                    </tr>
                `;
            });

            tableHtml += `
                    </tbody>
                </table>
            `;

            $('#filesList').html(tableHtml);
        }

        function populateBillingPeriodFilter(files) {
            const billingPeriods = [...new Set(files.map(file => file.billing_period).filter(period => period))];
            billingPeriods.sort().reverse(); // Sort in descending order (newest first)

            const select = $('#billingPeriodFilter');
            select.find('option:not(:first)').remove(); // Remove existing options except "All"

            billingPeriods.forEach(period => {
                select.append(`<option value="${period}">${period}</option>`);
            });
        }

        function filterFiles() {
            const billingPeriodFilter = $('#billingPeriodFilter').val();
            const searchFilter = $('#searchFilter').val().toLowerCase();

            $('#filesList tbody tr').each(function() {
                const row = $(this);
                const billingPeriod = row.data('billing-period');
                const filename = row.data('filename');

                let showRow = true;

                // Apply billing period filter
                if (billingPeriodFilter && billingPeriod !== billingPeriodFilter) {
                    showRow = false;
                }

                // Apply search filter
                if (searchFilter && !filename.includes(searchFilter)) {
                    showRow = false;
                }

                row.toggle(showRow);
            });

            // Update row count
            const visibleRows = $('#filesList tbody tr:visible').length;
            const totalRows = $('#filesList tbody tr').length;

            if (visibleRows !== totalRows) {
                $('#filesList').prepend(`<div class="alert alert-info mb-3">Showing ${visibleRows} of ${totalRows} files</div>`);
            } else {
                $('#filesList .alert-info').remove();
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function previewFile(filename, downloadUrl) {
            // For now, just open the file in a new tab
            // You could implement a more sophisticated preview here
            window.open(downloadUrl, '_blank');
        }

        function previewCleanup(documentType = null) {
            const url = '/admin/file-retention/preview';
            const data = documentType ? { document_type: documentType } : {};

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const info = data.data;
                    Swal.fire({
                        title: 'Cleanup Preview',
                        html: `
                            <div class="text-left">
                                <p><strong>Document Type:</strong> ${info.document_type}</p>
                                <p><strong>Total Files:</strong> ${info.total_files}</p>
                                <p><strong>Files Over Limit:</strong> ${info.files_over_limit}</p>
                                <p><strong>Max Files Allowed:</strong> ${info.max_files_allowed}</p>
                                <p><strong>Total Size:</strong> ${info.total_size_mb} MB</p>
                                <p><strong>Oldest File:</strong> ${info.oldest_file || 'N/A'}</p>
                                <p><strong>Newest File:</strong> ${info.newest_file || 'N/A'}</p>
                            </div>
                        `,
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to preview cleanup', 'error');
            });
        }

        function performCleanup(documentType) {
            Swal.fire({
                title: 'Confirm Cleanup',
                text: `Are you sure you want to clean up old ${documentType} files?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, clean up!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const url = '/admin/file-retention/cleanup-type';
                    const data = { document_type: documentType };

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Failed to perform cleanup', 'error');
                    });
                }
            });
        }

        function performCleanupAll() {
            Swal.fire({
                title: 'Confirm Cleanup All',
                text: 'Are you sure you want to clean up all old files? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, clean up all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const url = '/admin/file-retention/cleanup-all';

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', data.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Failed to perform cleanup', 'error');
                    });
                }
            });
        }

        function createBackup() {
            Swal.fire({
                title: 'Creating Backup',
                html: 'Please wait while we create a backup of all files...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('/admin/file-retention/backup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Backup Created Successfully!',
                        html: `
                            <div class="text-left">
                                <p><strong>Filename:</strong> ${data.filename}</p>
                                <p><strong>Total Files:</strong> ${data.total_files}</p>
                                <p><strong>Total Size:</strong> ${data.total_size_mb} MB</p>
                            </div>
                        `,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Download Backup',
                        cancelButtonText: 'Close'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Create a temporary link to download the file
                            const link = document.createElement('a');
                            link.href = data.download_url;
                            link.download = data.filename;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        }
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to create backup', 'error');
            });
        }
    </script>

</body>

</html>
