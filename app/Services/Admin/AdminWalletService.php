<?php

namespace App\Services\Admin;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminWalletService
{
    /**
     * Counterparty pairing window in seconds for transfer rows that lack explicit from/to wallet columns.
     */
    private const COUNTERPARTY_PAIR_WINDOW_SECONDS = 10;

    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->walletListQuery($filters)
            ->with(['customer.merchant'])
            ->orderByDesc('created_at');

        return $query->paginate($perPage);
    }

    public function show(string $id): Wallet
    {
        $wallet = Wallet::query()
            ->with(['customer.merchant'])
            ->findOrFail($id);

        $summary = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->selectRaw("
                COUNT(*) as transaction_count,
                COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE 0 END), 0) as total_credits,
                COALESCE(SUM(CASE WHEN direction = 'debit' THEN amount ELSE 0 END), 0) as total_debits
            ")
            ->first();

        $wallet->setAttribute('summary', [
            'transaction_count' => (int) ($summary->transaction_count ?? 0),
            'total_credits' => (float) ($summary->total_credits ?? 0),
            'total_debits' => (float) ($summary->total_debits ?? 0),
        ]);

        return $wallet;
    }

    public function setStatus(string $id, string $status): Wallet
    {
        if (! in_array($status, Wallet::STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid wallet status: {$status}");
        }

        $wallet = Wallet::query()->findOrFail($id);

        if ($wallet->isMaster() && $status !== Wallet::STATUSES[0]) {
            throw new \InvalidArgumentException('Master wallet holds system equity and cannot be suspended or closed.');
        }

        $wallet->update(['status' => $status]);

        return $wallet->fresh(['customer.merchant']);
    }

    public function walletTransactions(string $walletId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        Wallet::query()->findOrFail($walletId);

        $query = $this->transactionQuery($filters)
            ->where('wallet_id', $walletId)
            ->with(['wallet.customer.merchant'])
            ->orderByDesc('created_at');

        $paginated = $query->paginate($perPage);
        $this->attachCounterparties($paginated->getCollection());

        return $paginated;
    }

    public function allTransactions(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->transactionQuery($filters)
            ->with(['wallet.customer.merchant'])
            ->orderByDesc('created_at');

        $paginated = $query->paginate($perPage);
        $this->attachCounterparties($paginated->getCollection());

        return $paginated;
    }

    public function exportWallets(array $filters): BinaryFileResponse
    {
        $wallets = $this->walletListQuery($filters)
            ->with(['customer.merchant'])
            ->orderByDesc('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Wallets');

        $headers = [
            'Wallet ID',
            'User Number',
            'Type',
            'Status',
            'Balance',
            'Available Balance',
            'Currency',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Merchant',
            'Created At',
        ];
        $sheet->fromArray([$headers], null, 'A1');
        $this->styleHeaderRow($sheet, 'A1:L1');

        $row = 2;
        foreach ($wallets as $wallet) {
            $customer = $wallet->customer;
            $merchant = $customer?->merchant;

            $sheet->fromArray([
                $wallet->wallet_id,
                $wallet->user_number,
                $wallet->resolveType(),
                $wallet->status,
                (float) $wallet->balance,
                (float) $wallet->available_balance,
                $wallet->currency_code,
                $customer?->name,
                $customer?->email,
                $customer?->phone,
                $merchant?->business_name ?? $merchant?->name,
                $wallet->created_at?->format('Y-m-d H:i:s'),
            ], null, "A{$row}");
            $row++;
        }

        return $this->downloadSpreadsheet($spreadsheet, 'wallets_export_'.date('Y-m-d_H-i-s').'.xlsx');
    }

    public function exportTransactions(array $filters): BinaryFileResponse
    {
        $transactions = $this->transactionQuery($filters)
            ->with(['wallet.customer.merchant'])
            ->orderByDesc('created_at')
            ->get();

        $this->attachCounterparties($transactions);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Wallet Transactions');

        $headers = [
            'Date',
            'Wallet ID',
            'Customer Name',
            'Customer Phone',
            'Merchant',
            'Type',
            'Direction',
            'Amount',
            'Signed Amount',
            'Balance After',
            'Counterparty Wallet',
            'Counterparty User',
            'Reference',
            'Description',
        ];
        $sheet->fromArray([$headers], null, 'A1');
        $this->styleHeaderRow($sheet, 'A1:N1');

        $row = 2;
        foreach ($transactions as $transaction) {
            $wallet = $transaction->wallet;
            $customer = $wallet?->customer;
            $merchant = $customer?->merchant;
            $counterparty = $transaction->counterparty ?? null;
            $signedAmount = $this->signedAmount($transaction);

            $sheet->fromArray([
                $transaction->created_at?->format('Y-m-d H:i:s'),
                $wallet?->wallet_id,
                $customer?->name,
                $customer?->phone,
                $merchant?->business_name ?? $merchant?->name,
                $transaction->type,
                $transaction->direction,
                (float) $transaction->amount,
                $signedAmount,
                (float) $transaction->balance_after,
                $counterparty['wallet_id'] ?? null,
                $counterparty['owner_name'] ?? null,
                $transaction->reference,
                $transaction->description,
            ], null, "A{$row}");
            $row++;
        }

        return $this->downloadSpreadsheet($spreadsheet, 'wallet_transactions_export_'.date('Y-m-d_H-i-s').'.xlsx');
    }

    private function walletListQuery(array $filters): Builder
    {
        $query = Wallet::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } elseif (empty($filters['include_closed'])) {
            $query->where('status', '!=', 'closed');
        }

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('wallet_id', 'like', "%{$search}%")
                    ->orWhere('user_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        if (! empty($filters['wallet_type'])) {
            if ($filters['wallet_type'] === Wallet::TYPE_MASTER) {
                $query->whereNull('customer_id');
            } elseif ($filters['wallet_type'] === Wallet::TYPE_USER) {
                $query->whereNotNull('customer_id');
            }
        }

        if (! empty($filters['currency_code'])) {
            $query->where('currency_code', $filters['currency_code']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['min_balance']) && $filters['min_balance'] !== '') {
            $query->where('balance', '>=', (float) $filters['min_balance']);
        }

        if (isset($filters['max_balance']) && $filters['max_balance'] !== '') {
            $query->where('balance', '<=', (float) $filters['max_balance']);
        }

        return $query;
    }

    private function transactionQuery(array $filters): Builder
    {
        $query = WalletTransaction::query();

        if (! empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['wallet_id'])) {
            $query->whereHas('wallet', function (Builder $walletQuery) use ($filters) {
                $walletQuery->where('wallet_id', $filters['wallet_id'])
                    ->orWhere('id', $filters['wallet_id']);
            });
        }

        if (! empty($filters['merchant_id'])) {
            $query->whereHas('wallet', function (Builder $walletQuery) use ($filters) {
                $walletQuery->where('merchant_id', $filters['merchant_id']);
            });
        }

        if (! empty($filters['wallet_type'])) {
            $query->whereHas('wallet', function (Builder $walletQuery) use ($filters) {
                if ($filters['wallet_type'] === Wallet::TYPE_MASTER) {
                    $walletQuery->whereNull('customer_id');
                } elseif ($filters['wallet_type'] === Wallet::TYPE_USER) {
                    $walletQuery->whereNotNull('customer_id');
                }
            });
        }

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('wallet', function (Builder $walletQuery) use ($search) {
                        $walletQuery->where('wallet_id', 'like', "%{$search}%")
                            ->orWhere('user_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                                $customerQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
            $query->where('amount', '>=', (float) $filters['min_amount']);
        }

        if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
            $query->where('amount', '<=', (float) $filters['max_amount']);
        }

        return $query;
    }

    /**
     * Best-effort counterparty resolution for transfer rows.
     * Transfers store two rows (debit/credit) without explicit from_wallet/to_wallet columns.
     */
    private function attachCounterparties(Collection $transactions): void
    {
        $transferTransactions = $transactions->filter(
            fn (WalletTransaction $tx) => $tx->type === 'transfer'
        );

        $counterpartyMap = [];

        if ($transferTransactions->isNotEmpty()) {
            foreach ($transferTransactions as $transaction) {
                $createdAt = $transaction->created_at;
                if (! $createdAt) {
                    continue;
                }

                $oppositeDirection = $transaction->direction === 'debit' ? 'credit' : 'debit';

                $counterpartyTx = WalletTransaction::query()
                    ->where('id', '!=', $transaction->id)
                    ->where('type', 'transfer')
                    ->where('reference', $transaction->reference)
                    ->where('reference_id', $transaction->reference_id)
                    ->where('amount', $transaction->amount)
                    ->where('direction', $oppositeDirection)
                    ->where('wallet_id', '!=', $transaction->wallet_id)
                    ->whereBetween('created_at', [
                        $createdAt->copy()->subSeconds(self::COUNTERPARTY_PAIR_WINDOW_SECONDS),
                        $createdAt->copy()->addSeconds(self::COUNTERPARTY_PAIR_WINDOW_SECONDS),
                    ])
                    ->with(['wallet.customer.merchant'])
                    ->orderBy('created_at')
                    ->first();

                if ($counterpartyTx) {
                    $counterpartyMap[$transaction->id] = $this->formatCounterparty($counterpartyTx);
                }
            }
        }

        $transactions->each(function (WalletTransaction $transaction) use ($counterpartyMap) {
            $transaction->setAttribute('counterparty', $counterpartyMap[$transaction->id] ?? null);
            $transaction->setAttribute('signed_amount', $this->signedAmount($transaction));
        });
    }

    private function formatCounterparty(WalletTransaction $counterpartyTx): array
    {
        $wallet = $counterpartyTx->wallet;
        $customer = $wallet?->customer;
        $merchant = $customer?->merchant;

        return [
            'wallet_id' => $wallet?->wallet_id,
            'wallet_uuid' => $wallet?->id,
            'owner_name' => $customer?->name,
            'owner_phone' => $customer?->phone,
            'owner_email' => $customer?->email,
            'merchant_id' => $wallet?->merchant_id,
            'merchant_name' => $merchant?->business_name ?? $merchant?->name,
            'direction' => $counterpartyTx->direction,
        ];
    }

    private function signedAmount(WalletTransaction $transaction): float
    {
        $amount = (float) $transaction->amount;

        return $transaction->direction === 'debit' ? -abs($amount) : abs($amount);
    }

    private function styleHeaderRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle($range)->getFont()->getColor()->setRGB('FFFFFF');
    }

    private function downloadSpreadsheet(Spreadsheet $spreadsheet, string $filename): BinaryFileResponse
    {
        $tempPath = storage_path('app/temp/'.Str::uuid().'.xlsx');
        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }
}
