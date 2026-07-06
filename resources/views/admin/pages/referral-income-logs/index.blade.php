@extends('admin.layout.admin-master')
@section('title', 'Referral Income Logs')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">Referral Income Logs</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>Income Logs
                </h5>
                <div>
                    <select id="incomeTypeFilter" class="form-control form-control-sm d-inline-block" style="width: auto;">
                        <option value="">All Types</option>
                        @foreach(\App\Models\IncomeLog::distinct('income_type')->pluck('income_type') as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="incomeLogsTable">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>From User</th>
                                <th>Income Type</th>
                                <th>CC Amount</th>
                                <th>Currency</th>
                                <th>Balance After</th>
                                <th>Reference</th>
                                <th>Order No</th>
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
window.incomeLogsIndexRoute = '{{ route("referral-income-logs.index") }}';

$(document).ready(function () {

    const table = $('#incomeLogsTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: window.incomeLogsIndexRoute,
            data: function (d) {
                d.income_type = $('#incomeTypeFilter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'user', name: 'user', sortable: false },
            { data: 'from_user', name: 'from_user', sortable: false },
            { data: 'income_type', name: 'income_logs.income_type' },
            { data: 'cc_amount', name: 'income_logs.cc_amount' },
            { data: 'currency_amount', name: 'income_logs.currency_amount' },
            { data: 'balance_after', name: 'income_logs.balance_after' },
            { data: 'reference_type', name: 'income_logs.reference_type' },
            { data: 'order_number', name: 'income_logs.order_number' },
            { data: 'created_at', name: 'income_logs.created_at' },
        ]
    });

    $('#incomeTypeFilter').on('change', function () {
        table.ajax.reload();
    });
});
</script>
@endpush
