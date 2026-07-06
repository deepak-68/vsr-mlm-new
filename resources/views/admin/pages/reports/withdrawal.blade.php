@extends('admin.layout.admin-master')
@section('title', 'Withdrawal Report')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                <li class="breadcrumb-item active">Withdrawal Report</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-money-bill-wave me-2"></i>Withdrawal Report
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reports.withdrawal') }}" class="mb-4">
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
                            <a href="{{ route('reports.withdrawal') }}" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover w-100" id="withdrawalTable">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Charges</th>
                                <th>Payable</th>
                                <th>Status</th>
                                <th>Transaction No</th>
                                <th>Payment Date</th>
                                <th>Request Date</th>
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
    var table = $('#withdrawalTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: '{{ route('reports.withdrawal') }}',
            data: function (d) {
                d.date_from = $('input[name="date_from"]').val();
                d.date_to = $('input[name="date_to"]').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'user_name', name: 'user_name' },
            { data: 'amount', name: 'amount' },
            { data: 'charges', name: 'charges', searchable: false },
            { data: 'payable', name: 'payable', searchable: false },
            { data: 'status', name: 'status' },
            { data: 'transaction_no', name: 'transaction_no' },
            { data: 'payment_date', name: 'payment_date' },
            { data: 'request_date', name: 'created_at' },
        ],
        drawCallback: function () {
            var api = this.api();
            var json = api.ajax.json();
            if (json) {
                var footer = $(api.table().footer());
                if (!footer.length) {
                    $('#withdrawalTable').append('<tfoot><tr><th colspan="9" class="text-center"></th></tr></tfoot>');
                    footer = $(api.table().footer());
                }
                footer.find('th').html(
                    '<strong>Total Requests: ' + json.totalRequests + ' | Total Amount: ' + json.totalAmount + ' | Total Payable: ' + json.totalPayable + '</strong>'
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
