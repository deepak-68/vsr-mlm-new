@extends('admin.layout.admin-master')
@section('title', 'Payout Dashboard')

@section('content')
<div class="content-body">
    <div class="container-fluid">
        <div class="page-titles">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Payout Dashboard</li>
            </ol>
        </div>

        {{-- Stats Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card bg-warning text-white shadow-sm border-0 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-md bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="fas fa-clock fs-3 text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-white-50 mb-0 small text-uppercase fw-semibold">Pending</p>
                                <h3 class="mb-0 text-white fw-bold">{{ $pendingCount }}</h3>
                                <small class="text-white-50">₹{{ number_format($totalPendingAmount, 2) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card bg-success text-white shadow-sm border-0 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-md bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="fas fa-check-circle fs-3 text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-white-50 mb-0 small text-uppercase fw-semibold">Approved</p>
                                <h3 class="mb-0 text-white fw-bold">{{ $approvedCount }}</h3>
                                <small class="text-white-50">₹{{ number_format($totalApprovedAmount, 2) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card bg-danger text-white shadow-sm border-0 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-md bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="fas fa-times-circle fs-3 text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-white-50 mb-0 small text-uppercase fw-semibold">Rejected</p>
                                <h3 class="mb-0 text-white fw-bold">{{ $rejectedCount }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card bg-info text-white shadow-sm border-0 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-md bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="fas fa-money-bill-wave fs-3 text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-white-50 mb-0 small text-uppercase fw-semibold">Total Paid</p>
                                <h5 class="mb-0 text-white fw-bold">₹{{ number_format($totalPaidViaTransfer, 2) }}</h5>
                                <small class="text-white-50">Via admin transfers</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-8 col-md-12">
                <div class="card bg-primary text-white shadow-sm border-0 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-md bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="fas fa-coins fs-3 text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-white-50 mb-0 small text-uppercase fw-semibold">1 CC Value</p>
                                <h5 class="mb-0 text-white fw-bold">₹{{ number_format($ccRate, 2) }}</h5>
                                <small class="text-white-50">Current active CC rate</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Pending Requests --}}
        <div class="row g-3 mb-4">
            <div class="col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="card-title mb-0 fs-6"><i class="fas fa-clock me-2 text-warning"></i>Recent Pending Requests</h5>
                        <a href="{{ route('mlm-users.payout-request') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Amount</th>
                                        <th>Mode</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingRequests as $req)
                                    <tr>
                                        <td>
                                            <strong>{{ $req->user?->first_name }} {{ $req->user?->last_name }}</strong><br>
                                            <small class="text-muted">{{ $req->user?->user_name }}</small>
                                        </td>
                                        <td><span class="badge bg-warning text-dark fs-6">₹{{ number_format($req->amount, 2) }}</span></td>
                                        <td>{{ $req->mode_of_payment ?? 'N/A' }}</td>
                                        <td><small>{{ $req->created_at->format('d M Y, h:i A') }}</small></td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No pending requests</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="card-title mb-0 fs-6"><i class="fas fa-users me-2 text-primary"></i>Users with Payout Activity</h5>
                        <span class="badge bg-primary">{{ $usersWithPayouts->total() }} Users</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Sponsor</th>
                                        <th>CC</th>
                                        <th>Available</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($usersWithPayouts as $user)
                                    @php
                                        $balance = $user->payoutBalance;
                                        $ccBalance = $balance?->cc_balance ?? 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $user->first_name ?? '' }} {{ $user->last_name ?? '' }}</strong><br>
                                            <small class="text-muted">{{ $user->user_name ?? 'N/A' }}</small>
                                        </td>
                                        <td>{{ $user->sponsor?->user_name ?? 'ROOT' }}</td>
                                        <td>
                                            {{ number_format($ccBalance) }} CC<br>
                                            <small>≈ ₹{{ number_format($ccBalance * $ccRate, 2) }}</small>
                                        </td>
                                        <td class="text-success fw-bold">₹{{ number_format($balance?->available_balance ?? 0, 2) }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary view-payout" data-id="{{ $user->id }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($usersWithPayouts->hasPages())
                    <div class="card-footer bg-white">{{ $usersWithPayouts->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Details Modal --}}
<div class="modal fade" id="payoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Payout Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="payoutBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success d-none" id="withdrawBtn" data-bs-toggle="modal" data-bs-target="#withdrawModal">Withdraw</button>
            </div>
        </div>
    </div>
</div>

{{-- Withdraw Modal --}}
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('mlm-users.withdraw') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" id="wUserId">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Process Withdrawal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">Available: ₹<span id="wAvailable">0.00</span></div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Method</label>
                        <select name="method" class="form-select" required>
                            <option value="bank">Bank Transfer</option>
                            <option value="upi">UPI</option>
                            <option value="wallet">Wallet</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Submit Request</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ccRate = {{ json_encode($ccRate) }};
    const payoutModalEl = document.getElementById('payoutModal');
    const payoutModal = payoutModalEl ? (bootstrap.Modal?.getOrCreateInstance?.(payoutModalEl) || new bootstrap.Modal(payoutModalEl)) : null;

    const fmt = (n) => {
        const num = parseFloat(n) || 0;
        return num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    document.querySelectorAll('.view-payout').forEach(btn => {
        btn.onclick = async (e) => {
            e.preventDefault();
            const id = btn.dataset.id;
            const body = document.getElementById('payoutBody');
            const wBtn = document.getElementById('withdrawBtn');

            body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading...</p></div>';
            if (wBtn) wBtn.classList.add('d-none');

            try {
                const res = await fetch(`/mlm-users/payout/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error(`Server error: ${res.status}`);
                const data = await res.json();
                if (!data?.summary) throw new Error('No payout data found');

                const s = data.summary;
                body.innerHTML = `
                    <div class="row g-3">
                        <div class="col-4 text-center">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body">
                                    <i class="fas fa-coins fa-2x mb-2"></i>
                                    <h6 class="mb-1">CC Balance</h6>
                                    <h4 class="mb-0">${s.personal_cc || 0} CC</h4>
                                    <small>≈ ₹${fmt((s.personal_cc || 0) * ccRate)}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body">
                                    <i class="fas fa-wallet fa-2x mb-2"></i>
                                    <h6 class="mb-1">Available</h6>
                                    <h4 class="mb-0">₹${fmt(s.available_balance)}</h4>
                                    <small>Ready to withdraw</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body">
                                    <i class="fas fa-trophy fa-2x mb-2"></i>
                                    <h6 class="mb-1">Total Earned</h6>
                                    <h4 class="mb-0">₹${fmt((s.available_balance || 0) + (s.locked_balance || 0))}</h4>
                                    <small>Lifetime earnings</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="fw-bold">Eligibility Progress</label>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar ${s.is_eligible ? 'bg-success' : 'bg-warning'}"
                                 style="width: ${Math.min(100, Math.max(0, s.progress_percent || 0))}%"></div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            ${s.products_needed || 0} more products to unlock payouts
                            ${s.is_eligible ? '<span class="text-success fw-bold ms-1">✓ ELIGIBLE</span>' : ''}
                        </small>
                    </div>

                    <h6 class="mt-4 mb-2"><i class="fas fa-history me-1"></i>Recent Transactions</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="bg-light">
                                <tr><th>Date</th><th>Type</th><th>CC</th><th>Amount</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                ${(data.transactions || []).length > 0
                                    ? data.transactions.map(t => `
                                        <tr>
                                            <td><small>${t.created_at ? new Date(t.created_at).toLocaleDateString() : 'N/A'}</small></td>
                                            <td><span class="badge bg-${t.type === 'direct_income' ? 'success' : 'info'}">${(t.type || '').replace('_', ' ')}</span></td>
                                            <td>${t.cc_amount || 0}</td>
                                            <td>₹${fmt(t.currency_amount)}</td>
                                            <td><span class="badge bg-${t.status === 'credited' ? 'success' : 'secondary'}">${t.status || 'pending'}</span></td>
                                        </tr>
                                    `).join('')
                                    : '<tr><td colspan="5" class="text-center text-muted py-3">No transactions yet</td></tr>'
                                }
                            </tbody>
                        </table>
                    </div>
                `;

                if (wBtn && s.is_eligible && (s.available_balance || 0) > 0) {
                    wBtn.classList.remove('d-none');
                    const wUserId = document.getElementById('wUserId');
                    const wAvailable = document.getElementById('wAvailable');
                    if (wUserId && data?.user?.id) wUserId.value = data.user.id;
                    if (wAvailable) wAvailable.textContent = fmt(s.available_balance);
                }

                if (payoutModal) payoutModal.show();
            } catch (e) {
                console.error('Modal error:', e);
                body.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><strong>Error:</strong> ${e.message || 'Something went wrong'}</div>`;
                if (payoutModal) payoutModal.show();
            }
        };
    });

    if (payoutModalEl) {
        payoutModalEl.addEventListener('hidden.bs.modal', () => {
            const body = document.getElementById('payoutBody');
            const wBtn = document.getElementById('withdrawBtn');
            if (body) body.innerHTML = '';
            if (wBtn) wBtn.classList.add('d-none');
        });
    }
});
</script>
@endpush
