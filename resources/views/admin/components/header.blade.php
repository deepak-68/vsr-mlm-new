<div class="nav-header">
    <a href="{{ route('dashboard') }}" class="brand-logo d-flex align-items-center">
        <!-- Logo Image -->
        {{-- <img src="{{ asset('admin/images/favicon.png') }}" alt="Logo" class="logo-abbr"> --}}

        @php
            $settings = App\Models\Setting::first();
        @endphp
        <!-- Optional: Brand Title next to logo -->
        <span class="brand-title ms-2">
            <img src="{{ $settings->backend_logo ? asset('storage/' . $settings->backend_logo) : asset('images/logo/hozlogo.png') }}"
                alt="img">
        </span>
    </a>

    <div class="nav-control">
        <div class="hamburger">
            <span class="line"></span>
            <span class="line"></span>
            <span class="line"></span>
</div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    const badge = $('#notificationBadge');
    const list = $('#notificationList');
    const loading = $('#notificationLoading');

    function loadRecentNotifications() {
        loading.show();
        $.ajax({
            url: '{{ route("notification-logs.recent") }}',
            method: 'GET',
            success: function (res) {
                loading.hide();
                list.empty();
                const notifications = res.data?.data || [];
                if (!notifications.length) {
                    list.html('<div class="text-center py-3 text-muted small"><i class="fas fa-bell-slash d-block mb-1 fs-5"></i>No new notifications</div>');
                    return;
                }
                notifications.forEach(function (n) {
                    const item = $('<a class="list-group-item list-group-item-action d-flex align-items-start gap-3 px-4 py-3 border-bottom ' + (n.is_read ? '' : 'bg-light') + '" href="' + '{{ route("notification-logs.index") }}"' + ' style="word-break: break-word; overflow-wrap: break-word;">');
                    const iconMap = {
                        purchase: 'fa-shopping-bag', income: 'fa-wallet', rank: 'fa-trophy',
                        reward: 'fa-gift', registration: 'fa-user-plus', withdrawal: 'fa-credit-card', ticket: 'fa-ticket-alt'
                    };
                    const icon = iconMap[n.type] || 'fa-bell';
                    const time = n.created_at ? new Date(n.created_at).toLocaleString('en-IN', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';
                    item.html(
                        '<span class="badge bg-primary rounded-circle p-2 flex-shrink-0" style="font-size:12px"><i class="fas ' + icon + '"></i></span>' +
                        '<div class="flex-grow-1" style="min-width:0">' +
                            '<div class="d-flex justify-content-between align-items-start gap-2">' +
                                '<strong class="' + (n.is_read ? '' : 'fw-bold') + '" style="font-size:14px">' + (n.title || '') + '</strong>' +
                                '<small class="text-muted flex-shrink-0" style="font-size:11px; white-space:nowrap">' + time + '</small>' +
                            '</div>' +
                            '<div class="text-muted mt-1" style="font-size:13px; line-height:1.4">' + (n.message || '') + '</div>' +
                        '</div>'
                    );
                    if (!n.is_read) {
                        const markBtn = $('<button class="btn btn-sm btn-link p-1 flex-shrink-0 align-self-start mark-read-header" data-id="' + n.id + '" title="Mark read"><i class="fas fa-check-circle text-success" style="font-size:18px"></i></button>');
                        markBtn.on('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const id = $(this).data('id');
                            $.ajax({
                                url: '{{ route("notification-logs.mark-read", ["id" => "_id_"]) }}'.replace('_id_', id),
                                method: 'POST',
                                data: { _token: '{{ csrf_token() }}' },
                                success: function () {
                                    loadRecentNotifications();
                                    updateBadge();
                                }
                            });
                        });
                        item.append(markBtn);
                    }
                    list.append(item);
                });
            },
            error: function () {
                loading.hide();
                list.html('<div class="text-center py-3 text-muted small">Failed to load notifications.</div>');
            }
        });
    }

    function updateBadge() {
        $.ajax({
            url: '{{ route("notification-logs.unread-count") }}',
            method: 'GET',
            success: function (res) {
                const count = res.unread_count || 0;
                badge.text(count).toggle(count > 0);
            }
        });
    }

    // Load recent notifications when dropdown opens
    $(document).on('shown.bs.dropdown', '.notification_dropdown:first', function () {
        loadRecentNotifications();
    });

    // Initial badge load
    updateBadge();
    // Poll every 30s
    setInterval(updateBadge, 30000);
});
</script>
@endpush
</div>


<div class="header">
    <div class="header-content">
        <nav class="navbar navbar-expand">
            <div class="collapse navbar-collapse justify-content-between">
                <div class="header-left">
                    <div class="dashboard_bar">Dashboard</div>
                </div>

                <ul class="navbar-nav header-right">

                    <li class="nav-item dropdown notification_dropdown">
                        <a class="nav-link bell position-relative" href="javascript:void(0);" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span id="notificationBadge" class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill" style="font-size: 10px; display: none;">0</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" id="notificationDropdown" style="min-width: 420px; max-height: 480px;">
                            <div class="dropdown-header d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
                                <strong class="fs-6">Notifications</strong>
                                <a href="{{ route('notification-logs.index') }}" class="text-primary small fw-medium">View All</a>
                            </div>
                            <div class="list-group list-group-flush overflow-y-auto" id="notificationList" style="max-height: 400px;">
                                <div class="text-center py-4 text-muted small" id="notificationLoading">
                                    <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>Loading...
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item dropdown notification_dropdown">
                        <a class="nav-link bell dz-theme-mode" href="javascript:void(0);">
                            <i id="icon-light" class="fas fa-sun"></i>
                            <i id="icon-dark" class="fas fa-moon"></i>
                        </a>
                    </li>
                    <li class="nav-item dropdown header-profile">
                        <a class="nav-link" href="javascript:;" role="button" data-bs-toggle="dropdown">
                            @php
                                $user = auth()->user();
                            @endphp

                            <img src="{{ $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : url('images/avatar/1.png') }}"
                                onerror="this.onerror=null; this.src='{{ asset('assets/images/avatar/1.png') }}';"
                                class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;"
                                alt="{{ $user->name }}">
                            <div class="header-info">
                                <span>Hello, <strong class="">{{ auth()->user()->name }}</strong></span>
                            </div>

                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="{{ route('profile.update') }}" class="dropdown-item ai-icon">
                                <svg id="icon-user1" xmlns="http://www.w3.org/2000/svg" class="text-primary"
                                    width="18" height="18" viewbox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span class="ms-2">Profile </span>
                            </a>
                            {{-- <a href="email-inbox.html" class="dropdown-item ai-icon">
                                <svg id="icon-inbox" xmlns="http://www.w3.org/2000/svg" class="text-success" width="18"
                                    height="18" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z">
                                    </path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <span class="ms-2">Inbox </span>
                            </a> --}}
                            <a href="#" class="dropdown-item ai-icon"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <svg id="icon-logout" xmlns="http://www.w3.org/2000/svg" class="text-danger"
                                    width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                <span class="ms-2">Logout </span>
                            </a>

                            <!-- Hidden logout form -->
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>

                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>
