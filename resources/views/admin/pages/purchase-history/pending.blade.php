@extends('admin.layout.admin-master')
@section('title', 'Pending Orders')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">Pending Orders</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>Pending Orders
                </h5>
                <span class="badge bg-warning text-dark fs-6" id="pendingCount">0</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="pendingTable">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Order No</th>
                                <th>User Name</th>
                                <th>Total Amount</th>
                                <th>CC Amount</th>
                                <th>Items</th>
                                <th>Payment Mode</th>
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
            <div class="modal-footer" id="orderDetailFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.csrfToken = '{{ csrf_token() }}';
window.pendingRoute = '{{ route("pending-orders.index") }}';
window.purchaseShowRoute = '{{ url("purchase-history") }}';
window.confirmRoute = '{{ url("purchase-history") }}';

$(document).ready(function () {

    const table = $('#pendingTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: window.pendingRoute,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'order_no', name: 'orders.id' },
            { data: 'user_name', name: 'mlm_users.first_name' },
            { data: 'total_amount', name: 'total_amount' },
            { data: 'cc_amount', name: 'total_cc_points' },
            { data: 'items_count', name: 'items_count', searchable: false },
            { data: 'payment_mode', name: 'payment_mode', searchable: false },
            { data: 'status', name: 'status' },
            { data: 'date', name: 'order_date' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
        drawCallback: function (settings) {
            const info = this.api().page.info();
            $('#pendingCount').text(info.recordsTotal);
        }
    });

    let currentOrderId = null;

    $('#pendingTable').on('click', '.view-order-button', function () {
        const id = $(this).data('id');
        currentOrderId = id;
        const body = $('#orderDetailBody');
        const footer = $('#orderDetailFooter');
        body.html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading order details...</p>
            </div>
        `);
        footer.html(`<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`);
        $('#orderDetailModal').modal('show');

        $.ajax({
            url: window.purchaseShowRoute + '/' + id,
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

                    let paymentSection = '';
                    if (order.payment_mode === 'MANUAL' && order.transaction_number) {
                        paymentSection = `
                            <hr>
                            <h6>Manual Payment Details</h6>
                            <p><strong>Transaction No:</strong> ${order.transaction_number}</p>
                            ${order.payment_proof ? `<p><strong>Payment Proof:</strong> <a href="${response.payment_proof_url}" target="_blank" class="btn btn-sm btn-outline-primary">View Proof</a></p>` : ''}
                        `;
                    }

                    let recipientHtml = '';
                    if (order.purchased_for_user && order.purchased_for_user.id !== order.user?.id) {
                        recipientHtml = `<div class="mb-3">
                            <strong>Purchased For:</strong> ${order.purchased_for_user.first_name + ' ' + order.purchased_for_user.last_name + ' (' + order.purchased_for_user.track_id + ')'}
                        </div>`;
                    }

                    body.html(`
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Order #${order.id}</strong><br>
                                <span class="text-muted">Date: ${order.order_date ? new Date(order.order_date).toLocaleDateString('en-IN') : '-'}</span>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <strong>Status:</strong> ${order.status}<br>
                                <strong>Payment:</strong> ${order.payment_mode}
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Purchased By:</strong> ${order.user ? order.user.first_name + ' ' + order.user.last_name + ' (' + order.user.track_id + ')' : 'N/A'}
                            </div>
                            <div class="col-md-6 text-md-end">
                                <strong>Total:</strong> ₹${parseFloat(order.total_amount).toFixed(2)}
                            </div>
                        </div>
                        ${recipientHtml}
                        ${paymentSection}
                        <hr>
                        <h6>Order Items</h6>
                        ${itemsHtml}
                    `);

                    footer.html(`
                        <button type="button" class="btn btn-success" onclick="confirmOrder(${order.id})">
                            <i class="fas fa-check me-1"></i> Confirm Order
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    `);
                }
            },
            error: function () {
                body.html('<div class="alert alert-danger">Failed to load order details.</div>');
            }
        });
    });
});

function confirmOrder(id) {
    Swal.fire({
        title: 'Confirm Order?',
        text: 'This will confirm the order and process payments.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: 'Yes, Confirm',
        cancelButtonText: 'Cancel',
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: window.confirmRoute + '/' + id + '/confirm',
                method: 'POST',
                data: { _token: window.csrfToken },
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Confirmed!', response.message, 'success');
                        $('#orderDetailModal').modal('hide');
                        $('#pendingTable').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Failed to confirm order.', 'error');
                }
            });
        }
    });
}
</script>
@endpush
