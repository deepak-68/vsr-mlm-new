@extends('admin.layout.admin-master')
@section('title', 'Notifications')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">Notifications</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bell me-2"></i>Notification Logs
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="notificationsTable">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>User Name</th>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Read</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
window.csrfToken = '{{ csrf_token() }}';
window.notificationsIndexRoute = '{{ route("notifications.index") }}';

$(document).ready(function () {
    $('#notificationsTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: window.notificationsIndexRoute,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'user_name', name: 'mlm_users.first_name' },
            { data: 'type', name: 'notifications.type' },
            { data: 'title', name: 'notifications.title' },
            { data: 'message', name: 'notifications.message' },
            { data: 'is_read', name: 'notifications.is_read' },
            { data: 'created_at', name: 'notifications.created_at' },
        ]
    });
});
</script>
@endpush
