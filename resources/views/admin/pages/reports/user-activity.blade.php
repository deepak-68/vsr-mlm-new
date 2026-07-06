@extends('admin.layout.admin-master')
@section('title', 'User Activity Report')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                <li class="breadcrumb-item active">User Activity Report</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-activity me-2"></i>User Activity Report
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reports.user-activity') }}" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from', now()->startOfMonth()->toDateString()) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to', now()->toDateString()) }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <a href="{{ route('reports.user-activity') }}" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <ul class="nav nav-tabs mb-3" id="activityTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                            <i class="fas fa-bell me-1"></i> Notifications
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
                            <i class="fas fa-shopping-cart me-1"></i> Orders
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="activityTabContent">
                    <div class="tab-pane fade show active" id="notifications" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover w-100" id="notificationTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Activity Type</th>
                                        <th>Description</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="orders" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover w-100" id="orderActivityTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Activity Type</th>
                                        <th>Description</th>
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
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var notificationTable = $('#notificationTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: '{{ route('reports.user-activity') }}',
            data: function (d) {
                d.date_from = $('input[name="date_from"]').val();
                d.date_to = $('input[name="date_to"]').val();
                d.tab = 'notifications';
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'user_name', name: 'user_name' },
            { data: 'activity_type', name: 'activity_type' },
            { data: 'description', name: 'description' },
            { data: 'date', name: 'created_at' },
        ],
        drawCallback: function () {
            var api = this.api();
            var json = api.ajax.json();
            if (json) {
                var footer = $(api.table().footer());
                if (!footer.length) {
                    $('#notificationTable').append('<tfoot><tr><th colspan="5" class="text-center"></th></tr></tfoot>');
                    footer = $(api.table().footer());
                }
                footer.find('th').html(
                    '<strong>Total Activities: ' + json.totalActivities + '</strong>'
                );
            }
        }
    });

    var orderActivityTable = $('#orderActivityTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: '{{ route('reports.user-activity') }}',
            data: function (d) {
                d.date_from = $('input[name="date_from"]').val();
                d.date_to = $('input[name="date_to"]').val();
                d.tab = 'orders';
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'user_name', name: 'user_name' },
            { data: 'activity_type', name: 'activity_type' },
            { data: 'description', name: 'description' },
            { data: 'date', name: 'created_at' },
        ],
        drawCallback: function () {
            var api = this.api();
            var json = api.ajax.json();
            if (json) {
                var footer = $(api.table().footer());
                if (!footer.length) {
                    $('#orderActivityTable').append('<tfoot><tr><th colspan="5" class="text-center"></th></tr></tfoot>');
                    footer = $(api.table().footer());
                }
                footer.find('th').html(
                    '<strong>Total Activities: ' + json.totalActivities + '</strong>'
                );
            }
        }
    });

    $('form').on('submit', function (e) {
        e.preventDefault();
        notificationTable.ajax.reload();
        orderActivityTable.ajax.reload();
    });
});
</script>
@endpush
