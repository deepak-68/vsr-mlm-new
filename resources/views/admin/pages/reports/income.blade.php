@extends('admin.layout.admin-master')
@section('title', 'Income Report')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                <li class="breadcrumb-item active">Income Report</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-wallet me-2"></i>Income Report
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reports.income') }}" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from', now()->startOfMonth()->toDateString()) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to', now()->toDateString()) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Income Type</label>
                            <select name="income_type" class="form-select">
                                <option value="">All Types</option>
                                @php
                                    $types = \App\Models\IncomeLog::distinct('income_type')->pluck('income_type');
                                @endphp
                                @foreach($types as $type)
                                    <option value="{{ $type }}" {{ request('income_type') == $type ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('_', ' ', $type)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <a href="{{ route('reports.income') }}" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover w-100" id="incomeTable">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>From User</th>
                                <th>Income Type</th>
                                <th>CC Amount</th>
                                <th>₹ Amount</th>
                                <th>Reference</th>
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
$(document).ready(function () {
    var table = $('#incomeTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: '{{ route('reports.income') }}',
            data: function (d) {
                d.date_from = $('input[name="date_from"]').val();
                d.date_to = $('input[name="date_to"]').val();
                d.income_type = $('select[name="income_type"]').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'user_name', name: 'user_name' },
            { data: 'from_user_name', name: 'from_user_name' },
            { data: 'income_type', name: 'income_type' },
            { data: 'cc_amount', name: 'cc_amount' },
            { data: 'currency_amount', name: 'currency_amount' },
            { data: 'reference', name: 'reference' },
            { data: 'date', name: 'created_at' },
        ],
        drawCallback: function () {
            var api = this.api();
            var json = api.ajax.json();
            if (json) {
                var footer = $(api.table().footer());
                if (!footer.length) {
                    $('#incomeTable').append('<tfoot><tr><th colspan="8" class="text-center"></th></tr></tfoot>');
                    footer = $(api.table().footer());
                }
                footer.find('th').html(
                    '<strong>Total CC: ' + json.totalCC + ' | Total ₹: ' + json.totalCurrency + '</strong>'
                );
            }
        }
    });

    $('form').on('submit', function (e) {
        e.preventDefault();
        table.ajax.reload();
    });
});
</script>
@endpush
