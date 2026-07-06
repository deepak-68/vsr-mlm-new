@extends('admin.layout.admin-master')
@section('title', 'User Ranks')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">User Ranks</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-trophy me-2"></i>User Rank History
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="userRanksTable">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>User Name</th>
                                <th>Rank Name</th>
                                <th>CC at Time</th>
                                <th>Is Current</th>
                                <th>Achieved At</th>
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
window.userRanksIndexRoute = '{{ route("user-ranks.index") }}';

$(document).ready(function () {
    $('#userRanksTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: window.userRanksIndexRoute,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'user_name', name: 'mlm_users.first_name' },
            { data: 'rank_name', name: 'ranks.name' },
            { data: 'current_cc_at_time', name: 'current_cc_at_time' },
            { data: 'is_current', name: 'is_current' },
            { data: 'achieved_at', name: 'achieved_at' },
        ]
    });
});
</script>
@endpush
