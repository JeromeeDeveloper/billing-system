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
                                <h4 class="card-title">All Notifications</h4>
                                <button class="btn btn-primary" id="mark-all-read">Mark All as Read</button>
                            </div>
                            <div class="card-body">
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
                                            @foreach($notifications as $notification)
                                                <tr class="{{ $notification->is_read ? '' : 'table-light' }}">
                                                    <td>
                                                        @if($notification->type === 'document_upload')
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
                                                                <i class="ti-info"></i> {{ ucfirst($notification->type) }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $notification->message }}</td>
                                                    <td>{{ $notification->user->name }}</td>
                                                    <td>
                                                        @if($notification->billing_period)
                                                            <span class="badge badge-info">
                                                                {{ \Carbon\Carbon::parse($notification->billing_period)->format('F Y') }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-secondary">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $notification->created_at->diffForHumans() }}</td>
                                                    <td>
                                                        @if($notification->is_read)
                                                            <span class="badge badge-success">Read</span>
                                                        @else
                                                            <span class="badge badge-warning">Unread</span>
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
                <p>Copyright © Designed &amp; Developed by <a href="https://mass-specc.coop/" target="_blank">MASS-SPECC COOPERATIVE</a>2025</p>
            </div>
        </div>
    </div>

    @include('layouts.partials.footer')

    <script>
        $(document).ready(function() {
            $('#mark-all-read').click(function() {
                $.post('{{ route("notifications.mark-read") }}', {
                    _token: '{{ csrf_token() }}'
                }, function() {
                    location.reload();
                });
            });
        });
    </script>
</body>
</html>
