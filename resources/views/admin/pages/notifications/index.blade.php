@extends('admin.layout.admin-master')
@section('title', 'Notification Logs')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">Notification Logs</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bell me-2"></i>Notification Logs
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <label for="typeFilter" class="form-label mb-0 text-muted small">Type:</label>
                        <select id="typeFilter" class="form-select form-select-sm" style="width:auto">
                            <option value="all">All Types</option>
                            <option value="purchase">Purchase</option>
                            <option value="income">Income</option>
                            <option value="rank">Rank</option>
                            <option value="reward">Reward</option>
                            <option value="registration">Registration</option>
                            <option value="withdrawal">Withdrawal</option>
                            <option value="ticket">Ticket</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline-primary" id="markAllReadBtn">
                        <i class="fas fa-check-double me-1"></i> Mark All Read
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="notificationsTable">
                        <thead class="bg-light">
                            <tr>
                                <th style="width:50px">#</th>
                                <th>User Name</th>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th style="width:90px">Status</th>
                                <th style="width:150px">Date</th>
                                <th style="width:60px">Action</th>
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
window.notificationsIndexRoute = '{{ route("notification-logs.index") }}';

$(document).ready(function () {
    const table = $('#notificationsTable').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        scrollX: true,
        ajax: {
            url: window.notificationsIndexRoute,
            data: function (d) {
                d.type = $('#typeFilter').val() || 'all';
            }
        },
        columns: [
            { data: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'user_name', name: 'mlm_users.first_name' },
            { data: 'type_display', name: 'type' },
            { data: 'title', name: 'notifications.title' },
            { data: 'message', name: 'notifications.message' },
            { data: 'is_read', name: 'notifications.is_read' },
            { data: 'created_at', name: 'notifications.created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        drawCallback: function () {
            $('.mark-read').off('click').on('click', function () {
                const id = $(this).data('id');
                const btn = $(this);
                $.ajax({
                    url: '{{ route("notification-logs.mark-read", ["id" => "_id_"]) }}'.replace('_id_', id),
                    method: 'POST',
                    data: { _token: window.csrfToken },
                    success: function () {
                        btn.closest('tr').remove();
                        Swal.fire({ icon: 'success', title: 'Marked as read', timer: 1500, showConfirmButton: false });
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Failed to mark as read', timer: 1500, showConfirmButton: false });
                    }
                });
            });
        }
    });

    $('#typeFilter').on('change', function () {
        table.ajax.reload();
    });

    $('#markAllReadBtn').on('click', function () {
        const btn = $(this).prop('disabled', true);
        $.ajax({
            url: '{{ route("notification-logs.mark-all-read") }}',
            method: 'POST',
            data: { _token: window.csrfToken },
            success: function () {
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'All marked as read', timer: 1500, showConfirmButton: false });
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Failed to mark all as read', timer: 1500, showConfirmButton: false });
            }
        }).always(function () {
            btn.prop('disabled', false);
        });
    });
});
</script>
@endpush
