<span class="text-dark fw-bold text-hover-primary mb-1 fs-6">
    ${{ number_format(rand(10, 1000), 2) }}
</span>
<div class="text-muted fs-7">Auth: {{ $transaction->auth_code ?? 'N/A' }}</div> 