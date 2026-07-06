@extends('admin.layout.admin-master')
@section('title', 'Ranks')

@section('content')
<div class="content-body">
    <div class="container-fluid">

        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">Ranks</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-layer-group me-2"></i>Rank Management
                </h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rankModal">
                    <i class="fas fa-plus me-1"></i>Add Rank
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="ranksTable">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Required Self CC</th>
                                <th>Sort Order</th>
                                <th>Reward Description</th>
                                <th>Active</th>
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

<div class="modal fade" id="rankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rankForm" method="POST">
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" id="rankId" name="id" value="">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-layer-group me-2 text-primary"></i><span id="modalTitle">Add Rank</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="rankName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="slug" id="rankSlug" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Required Self CC <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="required_self_cc" id="rankRequiredCC" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort Order <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="sort_order" id="rankSortOrder" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reward Description</label>
                        <textarea class="form-control" name="reward_description" id="rankRewardDesc" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="is_active" id="rankActive" value="1" checked>
                            <label class="form-check-label" for="rankActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="rankSaveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.csrfToken = '{{ csrf_token() }}';
window.ranksIndexRoute = '{{ route("ranks.index") }}';

$(document).ready(function () {

    const table = $('#ranksTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: window.ranksIndexRoute,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'name', name: 'name' },
            { data: 'slug', name: 'slug' },
            { data: 'required_self_cc', name: 'required_self_cc' },
            { data: 'sort_order', name: 'sort_order' },
            { data: 'reward_description', name: 'reward_description' },
            { data: 'is_active', name: 'is_active' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ]
    });

    function resetForm() {
        $('#rankForm')[0].reset();
        $('#rankForm input[name="_method"]').val('POST');
        $('#rankId').val('');
        $('#modalTitle').text('Add Rank');
        $('#rankSaveBtn').text('Save');
        $('#rankActive').prop('checked', true);
    }

    $('#rankModal').on('hidden.bs.modal', resetForm);

    $('#ranksTable').on('click', '.edit-rank-button', function () {
        const btn = $(this);
        $('#rankId').val(btn.data('id'));
        $('#rankName').val(btn.data('name'));
        $('#rankSlug').val(btn.data('slug'));
        $('#rankRequiredCC').val(btn.data('required_self_cc'));
        $('#rankSortOrder').val(btn.data('sort_order'));
        $('#rankRewardDesc').val(btn.data('reward_description'));
        $('#rankActive').prop('checked', btn.data('is_active') === 1 || btn.data('is_active') === true);
        $('#rankForm input[name="_method"]').val('PUT');
        $('#modalTitle').text('Edit Rank');
        $('#rankSaveBtn').text('Update');
        $('#rankModal').modal('show');
    });

    $('#rankForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#rankId').val();
        const method = id ? 'PUT' : 'POST';
        const url = id ? window.ranksIndexRoute + '/' + id : window.ranksIndexRoute;

        const data = {
            _token: window.csrfToken,
            name: $('#rankName').val(),
            slug: $('#rankSlug').val(),
            required_self_cc: $('#rankRequiredCC').val(),
            sort_order: $('#rankSortOrder').val(),
            reward_description: $('#rankRewardDesc').val(),
            is_active: $('#rankActive').is(':checked') ? 1 : 0,
        };

        $.ajax({
            url: url,
            method: method,
            data: data,
            success: function (response) {
                if (response.success) {
                    $('#rankModal').modal('hide');
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

    $('#ranksTable').on('click', '.toggle-active-button', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: 'Toggle the active status of this rank.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, toggle it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.ranksIndexRoute + '/toggle-active/' + id,
                    method: 'POST',
                    data: { _token: window.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            table.ajax.reload(null, false);
                            Swal.fire({ icon: 'success', title: 'Toggled!', text: response.message, timer: 2000 });
                        }
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to toggle status.', timer: 3000 });
                    }
                });
            }
        });
    });
});
</script>
@endpush
