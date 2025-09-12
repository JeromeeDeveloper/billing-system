<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Notifications - Billing and Collection</title>

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logomsp.png') }}">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        .filter-controls {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .filter-controls label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .filter-controls .form-control {
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .filter-controls .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .results-summary {
            background-color: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 6px;
        }

        .custom-date-range {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
        }

        .btn-filter {
            margin-right: 10px;
        }

        .pagination {
            justify-content: center;
            margin-top: 20px;
        }

        .page-link {
            color: #007bff;
            border: 1px solid #dee2e6;
        }

        .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
        }

        .flex.justify-between.flex-1.sm\:hidden {
            display: none;
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
                            <h4>Notifications</h4>
                            <span class="ml-1">All Notifications</span>
                        </div>
                    </div>
                    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Notifications</li>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="card-title">All Notifications</h4>
                                    <div>
                                        <a href="{{ route('notifications.export') }}" class="btn btn-success mr-2">
                                            <i class="bi bi-file-earmark-excel"></i> Export to Excel
                                        </a>
                                        <button class="btn btn-primary" id="mark-all-read">Mark All as Read</button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Filter Controls -->
                                <div class="row mb-3 filter-controls">
                                    <div class="col-md-3">
                                        <label for="time_filter">Time Filter:</label>
                                        <select class="form-control" id="time_filter" name="time_filter">
                                            <option value="all" {{ $timeFilter == 'all' ? 'selected' : '' }}>All Time
                                            </option>
                                            <option value="today" {{ $timeFilter == 'today' ? 'selected' : '' }}>Today
                                            </option>
                                            <option value="yesterday"
                                                {{ $timeFilter == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                            <option value="week" {{ $timeFilter == 'week' ? 'selected' : '' }}>This
                                                Week</option>
                                            <option value="month" {{ $timeFilter == 'month' ? 'selected' : '' }}>This
                                                Month</option>
                                            <option value="last_month"
                                                {{ $timeFilter == 'last_month' ? 'selected' : '' }}>Last Month</option>
                                            <option value="custom" {{ $timeFilter == 'custom' ? 'selected' : '' }}>
                                                Custom Range</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="type_filter">Type Filter:</label>
                                        <select class="form-control" id="type_filter" name="type_filter">
                                            <option value="all" {{ $typeFilter == 'all' ? 'selected' : '' }}>All
                                                Types</option>
                                            @foreach ($notificationTypes as $type)
                                                <option value="{{ $type }}"
                                                    {{ $typeFilter == $type ? 'selected' : '' }}>
                                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="status_filter">Status Filter:</label>
                                        <select class="form-control" id="status_filter" name="status_filter">
                                            <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>All
                                                Status</option>
                                            <option value="unread" {{ $statusFilter == 'unread' ? 'selected' : '' }}>
                                                Unread</option>
                                            <option value="read" {{ $statusFilter == 'read' ? 'selected' : '' }}>Read
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="perPage">Per Page:</label>
                                        <select class="form-control" id="perPage" name="perPage">
                                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                            <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15</option>
                                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Custom Date Range (hidden by default) -->
                                <div class="row mb-3 custom-date-range" id="custom_date_range"
                                    style="display: {{ $timeFilter == 'custom' ? 'flex' : 'none' }};">
                                    <div class="col-md-3">
                                        <label for="start_date">Start Date:</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date"
                                            value="{{ request('start_date') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="end_date">End Date:</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                            value="{{ request('end_date') }}">
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="button" class="btn btn-secondary btn-filter"
                                            id="apply_filters">Apply Filters</button>
                                        <button type="button" class="btn btn-outline-secondary btn-filter"
                                            id="clear_filters">Clear Filters</button>
                                    </div>
                                </div>

                                <!-- Results Summary -->
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="alert alert-info results-summary text-dark">
                                            <strong>Showing {{ $notifications->firstItem() ?? 0 }} to
                                                {{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }}
                                                notifications</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Message</th>
                                                <th>User</th>
                                                <th>Billing Period</th>
                                                <th>Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($notifications as $notification)
                                                <tr class="{{ $notification->is_read ? '' : 'table-light' }}">
                                                    <td>
                                                        @if ($notification->type === 'document_upload')
                                                            <span class="badge badge-success">
                                                                <i class="ti-file"></i> Document Upload
                                                            </span>
                                                        @elseif($notification->type === 'billing_report')
                                                            <span class="badge badge-primary">
                                                                <i class="ti-receipt"></i> Billing Report
                                                            </span>
                                                        @elseif($notification->type === 'billing_period_update')
                                                            <span class="badge badge-warning">
                                                                <i class="ti-calendar"></i> Billing Period Update
                                                            </span>
                                                        @elseif($notification->type === 'billing_approval')
                                                            <span class="badge badge-success">
                                                                <i class="ti-check"></i> Billing Approval
                                                            </span>
                                                        @elseif($notification->type === 'billing_approval_cancelled')
                                                            <span class="badge badge-danger">
                                                                <i class="ti-close"></i> Approval Cancelled
                                                            </span>
                                                        @elseif($notification->type === 'file_backup')
                                                            <span class="badge badge-info">
                                                                <i class="ti-download"></i> File Backup
                                                            </span>
                                                        @else
                                                            <span class="badge badge-secondary">
                                                                <i class="ti-info"></i>
                                                                {{ ucfirst($notification->type) }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $notification->message }}</td>
                                                    <td>{{ $notification->user->name }}</td>
                                                    <td>
                                                        @if ($notification->billing_period)
                                                            <span class="badge badge-info">
                                                                {{ \Carbon\Carbon::parse($notification->billing_period)->format('F Y') }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-secondary">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $notification->created_at->diffForHumans() }}</td>
                                                    <td>
                                                        @if ($notification->is_read)
                                                            <span class="badge badge-success">Read</span>
                                                        @else
                                                            <span class="badge badge-warning">Unread</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">No notifications found.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div class="row">
                                    <div class="col-12 text-center">
                                        {{ $notifications->links() }}
                                    </div>
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

    @include('layouts.partials.footer')

    <script>
        $(document).ready(function() {
            // Handle time filter change to show/hide custom date range
            $('#time_filter').change(function() {
                if ($(this).val() === 'custom') {
                    $('#custom_date_range').show();
                } else {
                    $('#custom_date_range').hide();
                }
            });

            // Handle filter changes
            $('#time_filter, #type_filter, #status_filter, #perPage').change(function() {
                applyFilters();
            });

            // Apply filters button
            $('#apply_filters').click(function() {
                applyFilters();
            });

            // Clear filters button
            $('#clear_filters').click(function() {
                window.location.href = '{{ route('notifications.index') }}';
            });

            // Function to apply filters
            function applyFilters() {
                var params = new URLSearchParams();

                // Add filter values
                params.append('time_filter', $('#time_filter').val());
                params.append('type_filter', $('#type_filter').val());
                params.append('status_filter', $('#status_filter').val());
                params.append('perPage', $('#perPage').val());

                // Add custom date range if selected
                if ($('#time_filter').val() === 'custom') {
                    params.append('start_date', $('#start_date').val());
                    params.append('end_date', $('#end_date').val());
                }

                // Redirect with parameters
                window.location.href = '{{ route('notifications.index') }}?' + params.toString();
            }

            // Mark all as read functionality
            $('#mark-all-read').click(function() {
                $.post('{{ route('notifications.mark-read') }}', {
                    _token: '{{ csrf_token() }}'
                }, function() {
                    location.reload();
                });
            });

            // Auto-submit form when custom date range is filled
            $('#start_date, #end_date').change(function() {
                if ($('#time_filter').val() === 'custom' && $('#start_date').val() && $('#end_date')
                .val()) {
                    // Don't auto-submit, let user click apply button
                }
            });
        });
    </script>
</body>

</html>
