@extends("admin.layout.admin-master")

@section("title", "Terms & Conditions | VSR")

@section("content")
<div class="content-body">
    <div class="container-fluid">
        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Terms & Conditions</li>
            </ol>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-file-contract me-2"></i>Terms & Conditions
                </h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#termsModal">
                    <i class="fas fa-plus me-1"></i>Add New
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="termsTable">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Sub Title</th>
                                <th>Main Title</th>
                                <th>Description</th>
                                <th>Status</th>
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

<div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="termsForm">
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" id="termsId" name="id" value="">
                <div class="modal-header">
                    <h5 class="modal-title"><span id="modalTitle">Add Terms & Conditions</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Sub Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="sub_title" id="subTitle" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Main Title <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="main_title" id="mainTitle" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control summernote" name="description" id="description"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" checked>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.csrfToken = '{{ csrf_token() }}';
window.indexRoute = '{{ route("admin-terms-condition.index") }}';
window.storeRoute = '{{ route("admin-terms-condition.store") }}';
window.updateRoute = '{{ route("admin-terms-condition.update") }}';

$(document).ready(function () {

    const table = $('#termsTable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: window.indexRoute,
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
            { data: 'sub_title', name: 'sub_title' },
            { data: 'main_title', name: 'main_title' },
            { data: 'description', name: 'description' },
            { data: 'is_active', name: 'is_active' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ]
    });

    function resetForm() {
        $('#termsForm')[0].reset();
        $('#termsForm input[name="_method"]').val('POST');
        $('#termsId').val('');
        $('#modalTitle').text('Add Terms & Conditions');
        $('#saveBtn').text('Save');
        $('#isActive').prop('checked', true);
        if ($('#description').summernote) {
            $('#description').summernote('code', '');
        }
    }

    $('#termsModal').on('hidden.bs.modal', resetForm);

    $('#termsTable').on('click', '.edit-btn', function () {
        const btn = $(this);
        $('#termsId').val(btn.data('id'));
        $('#subTitle').val(btn.data('sub_title'));
        $('#mainTitle').val(btn.data('main_title'));
        $('#isActive').prop('checked', btn.data('is_active') == 1 || btn.data('is_active') === true);
        if ($('#description').summernote) {
            $('#description').summernote('code', btn.data('description'));
        } else {
            $('#description').val(btn.data('description'));
        }
        $('#termsForm input[name="_method"]').val('PUT');
        $('#modalTitle').text('Edit Terms & Conditions');
        $('#saveBtn').text('Update');
        $('#termsModal').modal('show');
    });

    $('#termsForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#termsId').val();
        const method = id ? 'PUT' : 'POST';
        const url = id ? window.updateRoute : window.storeRoute;

        const data = {
            _token: window.csrfToken,
            id: id,
            sub_title: $('#subTitle').val(),
            main_title: $('#mainTitle').val(),
            description: $('#description').summernote ? $('#description').summernote('code') : $('#description').val(),
            is_active: $('#isActive').is(':checked') ? 1 : 0,
        };

        if (!id) { delete data.id; }

        $.ajax({
            url: url,
            method: method,
            data: data,
            success: function (response) {
                if (response.success) {
                    $('#termsModal').modal('hide');
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

    $('#termsTable').on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.indexRoute + '/' + id,
                    method: 'DELETE',
                    data: { _token: window.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            table.ajax.reload(null, false);
                            Swal.fire({ icon: 'success', title: 'Deleted!', text: response.message, timer: 2000 });
                        }
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Delete failed.', timer: 3000 });
                    }
                });
            }
        });
    });

    $('.summernote').summernote({ height: 200 });
});
</script>
@endpush
