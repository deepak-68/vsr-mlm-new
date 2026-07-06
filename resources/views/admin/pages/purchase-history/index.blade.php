@extends('admin.layout.admin-master')
@section('title', 'Purchase History')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">Purchase History</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>Purchase History
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="purchaseTable">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Order No</th>
                                <th>User Name</th>
                                <th>Total Amount</th>
                                <th>CC Amount</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-receipt me-2 text-primary"></i>Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading order details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.csrfToken = '{{ csrf_token() }}';
window.purchaseIndexRoute = '{{ route("purchase-history.index") }}';

$(document).ready(function () {

    const table = $('#purchaseTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: window.purchaseIndexRoute,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'order_no', name: 'orders.id' },
            { data: 'user_name', name: 'mlm_users.first_name' },
            { data: 'total_amount', name: 'total_amount' },
            { data: 'cc_amount', name: 'total_cc_points' },
            { data: 'items_count', name: 'items_count', searchable: false },
            { data: 'status', name: 'status' },
            { data: 'date', name: 'order_date' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ]
    });

    $('#purchaseTable').on('click', '.view-order-button', function () {
        const id = $(this).data('id');
        const body = $('#orderDetailBody');
        body.html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading order details...</p>
            </div>
        `);
        $('#orderDetailModal').modal('show');

        $.ajax({
            url: window.purchaseIndexRoute + '/' + id,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    const order = response.order;
                    let itemsHtml = '';
                    if (order.items && order.items.length > 0) {
                        itemsHtml = '<table class="table table-sm table-bordered mt-3"><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>CC</th></tr></thead><tbody>';
                        order.items.forEach(function (item) {
                            const productName = item.product?.name ?? 'N/A';
                            itemsHtml += `<tr><td>${productName}</td><td>${item.quantity}</td><td>₹${parseFloat(item.price).toFixed(2)}</td><td>${item.cc_points ?? 0}</td></tr>`;
                        });
                        itemsHtml += '</tbody></table>';
                    }

                    body.html(`
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Order #${order.id}</strong><br>
                                <span class="text-muted">Date: ${order.order_date ? new Date(order.order_date).toLocaleDateString('en-IN') : '-'}</span>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <strong>Status:</strong> ${order.status}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>User:</strong> ${order.user ? order.user.first_name + ' ' + order.user.last_name : 'N/A'}
                            </div>
                            <div class="col-md-6 text-md-end">
                                <strong>Total:</strong> ₹${parseFloat(order.total_amount).toFixed(2)}
                            </div>
                        </div>
                        <hr>
                        <h6>Order Items</h6>
                        ${itemsHtml}
                    `);
                }
            },
            error: function () {
                body.html('<div class="alert alert-danger">Failed to load order details.</div>');
            }
        });
    });
});
</script>
@endpush
