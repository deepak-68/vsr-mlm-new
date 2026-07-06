@extends('admin.layout.admin-master')
@section('title', 'User Rewards')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">User Rewards</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-award me-2"></i>User Reward Management
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="userRewardsTable">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>User Name</th>
                                <th>Reward Name</th>
                                <th>Rank Name</th>
                                <th>Achieved At</th>
                                <th>Claimed At</th>
                                <th>Status</th>
                                <th>Notes</th>
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

<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <input type="hidden" id="userRewardId" value="">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2 text-primary"></i>Update Reward Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-control" id="rewardStatus">
                        <option value="pending">Pending</option>
                        <option value="claimed">Claimed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" id="rewardNotes" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveStatusBtn">Update</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.csrfToken = '{{ csrf_token() }}';
window.userRewardsIndexRoute = '{{ route("user-rewards.index") }}';

$(document).ready(function () {

    const table = $('#userRewardsTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: window.userRewardsIndexRoute,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'user_name', name: 'mlm_users.first_name' },
            { data: 'reward_name', name: 'rewards.name' },
            { data: 'rank_name', name: 'ranks.name' },
            { data: 'achieved_at', name: 'achieved_at' },
            { data: 'claimed_at', name: 'claimed_at' },
            { data: 'status', name: 'status' },
            { data: 'notes', name: 'notes' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ]
    });

    $('#userRewardsTable').on('click', '.update-status-button', function () {
        const btn = $(this);
        $('#userRewardId').val(btn.data('id'));
        $('#rewardStatus').val(btn.data('status'));
        $('#rewardNotes').val('');
        $('#statusModal').modal('show');
    });

    $('#saveStatusBtn').on('click', function () {
        const id = $('#userRewardId').val();
        const status = $('#rewardStatus').val();
        const notes = $('#rewardNotes').val();

        $.ajax({
            url: window.userRewardsIndexRoute + '/update-status/' + id,
            method: 'POST',
            data: {
                _token: window.csrfToken,
                status: status,
                notes: notes,
            },
            success: function (response) {
                if (response.success) {
                    $('#statusModal').modal('hide');
                    table.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Success', text: response.message, timer: 2000 });
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message ?? 'An error occurred.';
                Swal.fire({ icon: 'error', title: 'Error', text: msg, timer: 3000 });
            }
        });
    });
});
</script>
@endpush
