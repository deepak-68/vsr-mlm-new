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
    <div class="modal-dialog">
        <div class="modal-content">
            <input type="hidden" id="callbackId" value="">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tasks me-2 text-primary"></i>Manage Callback Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-control" id="callbackStatus">
                        <option value="PENDING">Pending</option>
                        <option value="SCHEDULED">Scheduled</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin Notes</label>
                    <textarea class="form-control" id="callbackNotes" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCallbackBtn">Update</button>
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
            { data: 'preferred_date', name: 'preferred_date' },
            { data: 'preferred_time', name: 'preferred_time' },
            { data: 'issue_summary', name: 'issue_summary' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ]
    });

    $('#callbackTable').on('click', '.manage-callback-button', function () {
        const btn = $(this);
        $('#callbackId').val(btn.data('id'));
        $('#callbackStatus').val(btn.data('status'));
        $('#callbackNotes').val(btn.data('notes'));
        $('#callbackModal').modal('show');
    });

    $('#saveCallbackBtn').on('click', function () {
        const id = $('#callbackId').val();
        const status = $('#callbackStatus').val();
        const notes = $('#callbackNotes').val();

        const promises = [];

        promises.push($.ajax({
            url: window.callbackIndexRoute + '/update-status/' + id,
            method: 'POST',
            data: {
                _token: window.csrfToken,
                status: status,
            }
        }));

        promises.push($.ajax({
            url: window.callbackIndexRoute + '/update-notes/' + id,
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
