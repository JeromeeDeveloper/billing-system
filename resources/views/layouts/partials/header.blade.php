    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <div class="nav-header">

        @if (Auth::check())
            @if (Auth::user()->role === 'admin')
                <a href="{{ route('dashboard') }}" class="brand-logo">
                    <img class="logomsp img-fluid" src="{{ asset('images/logomsp.png') }}" alt="Branch or User Logo">
                </a>
            @elseif (Auth::user()->role === 'branch')
                <a href="{{ route('dashboard_branch') }}" class="brand-logo">
                    <img class="logomsp img-fluid" src="{{ asset('images/logomsp.png') }}" alt="Branch or User Logo">
                </a>
            @endif
        @endif


        <div class="nav-control">
            <div class="hamburger">
                <span class="line"></span><span class="line"></span><span class="line"></span>
            </div>
        </div>
    </div>
    <div class="header">
        <div class="header-content">
            <nav class="navbar navbar-expand">
                <div class="collapse navbar-collapse justify-content-between">
                    <div class="header-left">
                        <div class="search_bar dropdown">
                            <span class="search_icon p-3 c-pointer" data-toggle="dropdown">
                                <i class="bi bi-search"></i>
                            </span>
                            <div class="dropdown-menu p-0 m-0">
                                <form>
                                    <input class="form-control" type="search" placeholder="Search" aria-label="Search">
                                </form>
                            </div>
                        </div>
                    </div>

                    <ul class="navbar-nav header-right">
                        <li class="nav-item d-flex align-items-center mr-3">
                            <span class="badge badge-info mr-2">
                                Billing Period: {{ Auth::user()->billing_period ? \Carbon\Carbon::parse(Auth::user()->billing_period)->format('F Y') : 'N/A' }}
                            </span>
                            <span class="badge badge-secondary mr-2">
                                Role: {{ ucfirst(Auth::user()->role) }}
                                @if(Auth::user()->role === 'branch' && Auth::user()->branch)
                                    ({{ Auth::user()->branch->name }})
                                @endif
                            </span>
                        </li>
                        <li class="nav-item dropdown notification_dropdown">
                            <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                                <i class="bi bi-bell"></i>
                                <div class="pulse-css" id="notification-pulse"></div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <ul class="list-unstyled" id="notification-list">
                                    <!-- Notifications will be dynamically inserted here -->
                                </ul>
                                <a class="all-notification" href="{{ route('notifications.index') }}">See all notifications <i class="ti-arrow-right"></i></a>
                            </div>
                        </li>
                        <li class="nav-item dropdown header-profile">
                            <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                                <i class="bi bi-person"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a href="{{ route('profile') }}" class="dropdown-item">
                                    <i class="icon-user"></i>
                                    <span class="ml-2">Profile</span>
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>

                                <a href="#" class="dropdown-item"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span class="ml-2">Logout</span>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </div>

@push('scripts')
<script>
function updateNotifications() {
    $.get('{{ route("notifications.latest") }}', function(data) {
        var notificationList = $('#notification-list');
        notificationList.empty();

        data.forEach(function(notification) {
            var icon, statusClass;

            if (notification.type === 'document_upload') {
                icon = 'ti-file';
                statusClass = 'success';
            } else if (notification.type === 'billing_report') {
                icon = 'ti-receipt';
                statusClass = 'primary';
            } else if (notification.type === 'billing_period_update') {
                icon = 'ti-calendar';
                statusClass = 'warning';
            } else if (notification.type === 'billing_approval') {
                icon = 'ti-check';
                statusClass = 'success';
            } else if (notification.type === 'billing_approval_cancelled') {
                icon = 'ti-close';
                statusClass = 'danger';
            } else if (notification.type === 'file_backup') {
                icon = 'ti-download';
                statusClass = 'info';
            } else {
                icon = 'ti-info';
                statusClass = 'secondary';
            }

            var html = `
                <li class="media dropdown-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                    <span class="${statusClass}"><i class="${icon}"></i></span>
                    <div class="media-body">
                        <a href="#">
                            <p><strong>${notification.user_name}</strong> ${notification.message}</p>
                            <small class="text-muted">
                                <i class="ti-calendar"></i> Billing Period: ${notification.billing_period}
                            </small>
                        </a>
                    </div>
                    <span class="notify-time">${notification.time}</span>
                </li>
            `;
            notificationList.append(html);
        });
    });

    // Update unread count
    $.get('{{ route("notifications.unread.count") }}', function(data) {
        if (data.count > 0) {
            $('#notification-pulse').show();
        } else {
            $('#notification-pulse').hide();
        }
    });
}

$(document).ready(function() {
    // Initial load
    updateNotifications();

    // Update every 30 seconds
    setInterval(updateNotifications, 30000);

    // Mark as read when clicked
    $('#notification-list').on('click', '.dropdown-item', function() {
        var notificationId = $(this).data('id');
        $.post('{{ route("notifications.mark-read") }}', {
            _token: '{{ csrf_token() }}',
            notification_id: notificationId
        });
        $(this).removeClass('unread');
    });

    // Mark all as read
    $('.all-notification').click(function() {
        $.post('{{ route("notifications.mark-read") }}', {
            _token: '{{ csrf_token() }}'
        });
    });
});
</script>

<style>
.unread {
    background-color: #f8f9fa;
}
.notify-time {
    font-size: 0.8rem;
    color: #6c757d;
}
</style>
@endpush
