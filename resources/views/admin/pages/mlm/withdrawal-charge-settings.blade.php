@extends("admin.layout.admin-master")
@section("title", "Withdrawal Charge Settings | Continuity Care")

@section("content")
    <div class="content-body">
        <div class="container-fluid">
            <div class="page-titles">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="javascript:void(0)">Settings</a></li>
                    <li class="breadcrumb-item active"><a href="javascript:void(0)">Withdrawal Charge Settings</a></li>
                </ol>
            </div>

            @if(session('success'))
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '{{ session('success') }}',
                        timer: 4000,
                        timerProgressBar: true,
                    });
                </script>
            @endif

            <div class="row">
                @include("admin.components.setting-sidebar")
                <div class="col-lg-9 ps-0">
                    <div class="card">
                        <div class="card-header bg-theme-light">
                            <h4 class="card-title">Withdrawal Charge Configuration</h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('withdrawal-charge-settings.update', $withdrawalCharge->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-lg-6 mb-4">
                                        <label for="w_charge" class="form-label fw-bold">Charge Percentage (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" name="value" id="w_charge"
                                                   class="form-control @error('value') is-invalid @enderror"
                                                   value="{{ old('value', $withdrawalCharge->value) }}" required min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        @error('value')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                        <small class="text-muted mt-2 d-block">
                                            Example: 2% charge on ₹1000 withdrawal = ₹20 deducted
                                        </small>
                                    </div>

                                    <div class="col-lg-6 mb-4">
                                        <label for="w_is_active" class="form-label fw-bold">Status</label>
                                        <select name="is_active" id="w_is_active" class="form-control @error('is_active') is-invalid @enderror">
                                            <option value="1" {{ old('is_active', $withdrawalCharge->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                            <option value="0" {{ old('is_active', $withdrawalCharge->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                        @error('is_active')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                        <small class="text-muted mt-2 d-block">Enable/Disable withdrawal charge deduction</small>
                                    </div>
                                </div>

                                <div class="alert alert-warning mb-4">
                                    <i class="fa fa-info-circle me-1"></i> This percentage will be deducted from all withdrawal approvals. Set to 0% for no charges.
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                        <i class="fa fa-save me-1"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
