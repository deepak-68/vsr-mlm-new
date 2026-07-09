@extends('admin.layout.admin-master')
@section('title', 'Callback Requests')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">Callback Requests</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-phone-alt me-2"></i>Callback Request Management
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="callbackTable">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Phone</th>
                                <th>Preferred Date</th>
                                <th>Preferred Time</th>
                                <th>Issue</th>
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

<div class="modal fade" id="callbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <input type="hidden" id="callbackId" value="">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-phone-alt me-2 text-primary"></i>Manage Callback Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="card bg-light mb-3">
                    <div class="card-body py-3">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Name</small>
                                <strong id="modalName" class="fs-15">—</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Username</small>
                                <strong id="modalUsername" class="fs-15">—</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Phone</small>
                                <strong id="modalPhone" class="fs-15">—</strong>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted d-block">Preferred</small>
                                <strong id="modalDateTime" class="fs-15">—</strong>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted d-block">Issue Summary</small>
                            <span id="modalIssue" class="text-dark">—</span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="callbackStatus">
                            <option value="PENDING">Pending</option>
                            <option value="SCHEDULED">Scheduled</option>
                            <option value="COMPLETED">Completed</option>
                            <option value="CANCELLED">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">
                            <i class="fas fa-check-circle text-success me-1"></i>Mark as Responded
                        </label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="markResponded" style="width:40px;height:20px;">
                            <label class="form-check-label text-muted" for="markResponded">Auto-set status to Completed</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">
                        <i class="fas fa-sticky-note text-info me-1"></i>Call Summary / Admin Notes
                    </label>
                    <textarea class="form-control" id="callbackNotes" rows="4" placeholder="Enter summary of the call, resolution, or any follow-up notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCallbackBtn">
                    <i class="fas fa-save me-1"></i>Update
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.csrfToken = '{{ csrf_token() }}';
window.callbackIndexRoute = '{{ route("callback-requests.index") }}';

$(document).ready(function () {

    const table = $('#callbackTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: window.callbackIndexRoute,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'name', name: 'mlm_users.first_name' },
            { data: 'username', name: 'mlm_users.user_name' },
            { data: 'phone', name: 'mlm_users.phone', orderable: false },
            { data: 'preferred_date', name: 'preferred_date' },
            { data: 'preferred_time', name: 'preferred_time' },
            { data: 'issue_summary', name: 'issue_summary' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });

    // Mark Responded checkbox → auto-set status to COMPLETED
    $('#markResponded').on('change', function () {
        if ($(this).is(':checked')) {
            $('#callbackStatus').val('COMPLETED');
        }
    });

    $('#callbackTable').on('click', '.manage-btn', function () {
        const btn = $(this);
        $('#callbackId').val(btn.data('id'));
        $('#callbackStatus').val(btn.data('status'));
        $('#callbackNotes').val(btn.data('notes'));
        $('#markResponded').prop('checked', btn.data('status') === 'COMPLETED');
        $('#modalName').text(btn.data('name'));
        $('#modalUsername').text(btn.data('username'));
        const phone = btn.data('phone');
        $('#modalPhone').html(phone ? '<a href="tel:' + phone + '">' + phone + '</a>' : '—');
        $('#modalDateTime').text((btn.data('date') || '') + ' ' + (btn.data('time') || ''));
        $('#modalIssue').text(btn.data('issue') || 'No details provided');
        $('#callbackModal').modal('show');
    });

    $('#saveCallbackBtn').on('click', function () {
        const id = $('#callbackId').val();
        const status = $('#callbackStatus').val();
        const notes = $('#callbackNotes').val();

        const promises = [];

        promises.push($.ajax({
            url: '{{ route("callback-requests.update-status", ["id" => "_id_"]) }}'.replace('_id_', id),
            method: 'POST',
            data: {
                _token: window.csrfToken,
                status: status,
            }
        }));

        promises.push($.ajax({
            url: '{{ route("callback-requests.update-notes", ["id" => "_id_"]) }}'.replace('_id_', id),
            method: 'POST',
            data: {
                _token: window.csrfToken,
                admin_notes: notes,
            }
        }));

        $.when.apply($, promises).then(function () {
            $('#callbackModal').modal('hide');
            table.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: 'Success', text: 'Callback request updated.', timer: 2000 });
        }, function (xhr) {
            const msg = xhr.responseJSON?.message ?? 'An error occurred.';
            Swal.fire({ icon: 'error', title: 'Error', text: msg, timer: 3000 });
        });
    });
});
</script>
@endpush
