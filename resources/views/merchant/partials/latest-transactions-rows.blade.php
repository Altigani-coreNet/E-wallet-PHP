@forelse($latestTransactions as $transaction)
<tr>
    <td>
        <div class="d-flex align-items-center">
            <div class="symbol symbol-40px me-3">
                @if($transaction->user && $transaction->user->profile_image)
                    <img src="{{ asset('storage/' . $transaction->user->profile_image) }}" class="rounded-circle" alt="Profile">
                @else
                    <div class="symbol-label bg-light-primary text-primary fs-6 fw-bold">
                        {{ substr($transaction->user->name ?? 'U', 0, 1) }}
                    </div>
                @endif
            </div>
            <div class="d-flex justify-content-start flex-column">
                <span class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">{{ $transaction->terminal_id ?? 'N/A' }}</span>
                <span class="text-muted fw-semibold d-block fs-7">Terminal: {{ $transaction->terminal_id ?? 'N/A' }}</span>
            </div>
        </div>
    </td>
    <td>
        <span class="text-gray-800 fw-bold d-block mb-1 fs-6">${{ number_format($transaction->amount, 2) }}</span>
    </td>
    <td>
        <span class="badge fs-7 fw-bold {{
            $transaction->status === 'approved' ? 'badge-light-success' : (
            $transaction->status === 'pending' ? 'badge-light-warning' : (
            $transaction->status === 'failed' ? 'badge-light-danger' : 'badge-light-info'))
        }}">
            {{ ucfirst($transaction->status) }}
        </span>
    </td>
    <td>
        <span class="text-muted fw-semibold d-block fs-7">{{ $transaction->created_at?->format('M d, Y H:i') }}</span>
    </td>
    <td class="text-end">
        <a href="#" class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary w-30px h-30px" data-bs-toggle="tooltip" title="View Details">
            <i class="ki-duotone ki-eye fs-2 text-gray-500"></i>
        </a>
    </td>
</tr>
@empty
<tr>
    <td colspan="5" class="text-center text-muted py-4">
        <span class="fs-6">No transactions found</span>
    </td>
</tr>
@endforelse

