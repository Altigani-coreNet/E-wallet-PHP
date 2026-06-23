<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use App\Models\Transaction;
use App\Models\Merchant;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Display a listing of transactions
     */
    public function index(Request $request)
    {
        $type = $request->get('type');
        
        if ($type) {
            // Get statistics for specific transaction type
            $statistics = $this->transactionService->getStatisticsByType($type);
        } else {
            // Get general statistics
            $statistics = $this->transactionService->getStatistics();
        }
        
        // Extract card-specific data for the view
        $saleTransactions = $statistics['saleTransactions'] ?? 0;
        $saleTransactionsAmount = $statistics['saleTransactionsAmount'] ?? 0;
        $refundTransactions = $statistics['refundTransactions'] ?? 0;
        $refundTransactionsAmount = $statistics['refundTransactionsAmount'] ?? 0;
        $voidTransactions = $statistics['voidTransactions'] ?? 0;
        $voidTransactionsAmount = $statistics['voidTransactionsAmount'] ?? 0;
        
        $merchants = Merchant::withCountry()->where('is_active', true)->get();
        $terminals = Terminal::withCountry()->where('is_active', true)->get();

        return view('admin.transactions.index', compact(
            'statistics', 
            'merchants', 
            'terminals', 
            'type',
            'saleTransactions',
            'saleTransactionsAmount',
            'refundTransactions',
            'refundTransactionsAmount',
            'voidTransactions',
            'voidTransactionsAmount'
        ));
    }

   


    /**
     * Display the specified transaction
     */
    public function show($id)
    {
        $transaction = Transaction::with('logs')->find($id);
        
        if (!$transaction) {
            return redirect()->route('admin.transactions.index')
                ->with('error', 'Transaction not found');
        }

        return view('admin.transactions.show', compact('transaction'));
    }

    

    /**
     * Remove the specified transaction
     */
    public function destroy($id)
    {
        try {
            $this->transactionService->deleteTransaction($id);
            
            return redirect()->route('admin.transactions.index')
                ->with('success', 'Transaction deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.transactions.index')
                ->with('error', 'Failed to delete transaction: ' . $e->getMessage());
        }
    }

    /**
     * Void a transaction
     */
    public function voidTransaction(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);
        $transaction = Transaction::findOrFail($id);
        try {
            $transaction->void($request->input('reason'));
            return redirect()->back()->with('success', __('translation.transaction_voided_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('translation.transaction_void_failed') . ': ' . $e->getMessage());
        }
    }

    public function refundTransaction(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $transaction = Transaction::findOrFail($id);
        try {
            DB::beginTransaction();
            $transaction->refund($request->input('amount'), $request->input('reason'));
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('translation.transaction_refunded_successfully')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('translation.transaction_refund_failed') . ': ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel a transaction
     */
    public function cancelTransaction(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);
        $transaction = Transaction::findOrFail($id);
        try {
            DB::beginTransaction();
            $transaction->cancel($request->input('reason'));
            DB::commit();

            return redirect()->back()->with('success', __('translation.transaction_cancelled_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('translation.transaction_cancel_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Get transaction statistics for dashboard
     */
    public function statistics()
    {
        $statistics = $this->transactionService->getStatistics();
       

        return response()->json([
            'statistics' => $statistics,
        ]);
    }

    /**
     * Export transactions
     */
    public function export(Request $request)
    {
        $filters = $request->only(['status', 'merchant_id', 'terminal_id', 'method', 'start_date', 'end_date', 'search', 'type']);
        
        $query = Transaction::with(['merchant']);
        
        // Apply type filter if present
        if (isset($filters['type']) && $filters['type']) {
            switch ($filters['type']) {
                case 'refunded':
                    $query->where('state', 'REFUNDED');
                    break;
                case 'voided':
                    $query->where('state', 'VOIDED');
                    break;
            }
        }
        
        // Apply filters
        if (isset($filters['status']) && $filters['status']) {
            $query = $query->where('state', $filters['status']);
        }

        if (isset($filters['merchant_id']) && $filters['merchant_id']) {
            $query = $query->where('merchant_id', $filters['merchant_id']);
        }

        if (isset($filters['terminal_id']) && $filters['terminal_id']) {
            $query = $query->where('terminal_id', $filters['terminal_id']);
        }

        if (isset($filters['method']) && $filters['method']) {
            $query = $query->where('method', $filters['method']);
        }

        if (isset($filters['start_date']) && $filters['start_date']) {
            $query = $query->where('transaction_datetime', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date']) && $filters['end_date']) {
            $query = $query->where('transaction_datetime', '<=', $filters['end_date']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query = $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'LIKE', "%{$search}%")
                  ->orWhere('rrn', 'LIKE', "%{$search}%")
                  ->orWhere('auth_code', 'LIKE', "%{$search}%")
                  ->orWhere('invoice_no', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('batch_no', 'LIKE', "%{$search}%")
                  ->orWhere('trace_no', 'LIKE', "%{$search}%");
            });
        }

        // Get all transactions (no pagination for export)
        $transactions = $query->latest('transaction_datetime')->get();

        // Check if there are transactions to export
        if ($transactions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No transactions found with the specified filters'
            ], 404);
        }

        // Log export activity for audit purposes
        Log::info('Transaction export requested', [
            'filters' => $filters,
            'count' => $transactions->count(),
            'user_id' => Auth::id() ?? 'guest'
        ]);

        // Generate CSV
        $filename = 'transactions_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, [
                'ID', 'Batch No', 'Trace No', 'RRN', 'Auth Code', 'MID', 'TID',
                'Transaction ID', 'State', 'Description', 'Transaction DateTime',
                'Invoice No', 'Card Number', 'Expiry', 'Method', 'Ref No',
                'ATC', 'TVR', 'App Name', 'TSI', 'Terminal', 'Merchant', 'Created At'
            ]);

            // Add data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->id,
                    $transaction->batch_no,
                    $transaction->trace_no,
                    $transaction->rrn,
                    $transaction->auth_code,
                    $transaction->mid,
                    $transaction->tid,
                    $transaction->transaction_id,
                    $transaction->state,
                    $transaction->description,
                    $transaction->transaction_datetime,
                    $transaction->invoice_no,
                    $transaction->card_number,
                    $transaction->expiry,
                    $transaction->method,
                    $transaction->ref_no,
                    $transaction->atc,
                    $transaction->tvr,
                    $transaction->app_name,
                    $transaction->tsi,
                    $transaction->terminal_id ?? '',
                    $transaction->merchant ? $transaction->merchant->name : '',
                    $transaction->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Search transactions by RRN
     */
    public function searchByRRN(Request $request)
    {
        $request->validate([
            'rrn' => 'required|string'
        ]);

        $transaction = $this->transactionService->getTransactionByRRN($request->rrn);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'transaction' => $transaction
        ]);
    }

    /**
     * Search transactions by trace number
     */
    public function searchByTraceNumber(Request $request)
    {
        $request->validate([
            'trace_no' => 'required|string'
        ]);

        $transaction = $this->transactionService->getTransactionByTraceNumber($request->trace_no);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'transaction' => $transaction
        ]);
    }

    /**
     * Get transactions data for DataTables
     */
    public function data(Request $request)
    {
        $query = Transaction::withCountry()->with(['merchant']);

        // Apply type filter if present
         if($request->has('type')){
            $query->where('status', $request->type);
         }

        // Apply filters
        if ($request->has('search') && !is_array($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%$search%")
                  ->orWhere('rrn', 'like', "%$search%")
                  ->orWhere('auth_code', 'like', "%$search%")
                  ->orWhere('invoice_no', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhere('batch_no', 'like', "%$search%")
                  ->orWhere('trace_no', 'like', "%$search%");
            });
        }

        if ($request->has('status')) {
            $query->where('state', $request->input('status'));
        }

        // if ($request->has('method')) {
        //     $query->where('method', $request->input('method'));
        // }

        if ($request->has('merchant_id')) {
            $query->where('merchant_id', $request->input('merchant_id'));
        }

        if ($request->has('terminal_id')) {
            $query->where('terminal_id', $request->input('terminal_id'));
        }

        if ($request->has('country_id')) {
            $query->where('country_id', $request->input('country_id'));
        }

        if ($request->has('start_date')) {
            $query->where('transaction_datetime', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('transaction_datetime', '<=', $request->input('end_date'));
        }

        return DataTables::of($query)
            ->addColumn('record_select', 'admin.transactions.data_table.record_select')
            ->addColumn('actions', 'admin.transactions.data_table.actions')
            ->editColumn('transaction_id', 'admin.transactions.data_table.transaction_id')
            ->editColumn('rrn', fn($item) => $item->rrn ?? 'N/A')
            ->editColumn('merchant', fn($item) => $item->merchant->name ?? 'N/A')
            ->editColumn('payment_method', 'admin.transactions.data_table.payment_method')
            ->editColumn('card_number', 'admin.transactions.data_table.card_number')
            ->editColumn('amount', 'admin.transactions.data_table.amount')
            ->editColumn('batch_no', fn($item) => $item->batch_no ?? 'N/A')
            ->editColumn('sdk', fn($item) => $item->sdk ?? 'N/A')
            ->editColumn('created_at', 'admin.transactions.data_table.created_at')
            ->editColumn('country', fn($item) => $item->country->name ?? 'N/A')
            ->editColumn('amount', fn($item) => '$ ' . number_format($item->amount, 2))
            ->editColumn('payment_type', fn($item) => view('admin.transactions.data_table.payment_type', compact('item'))->render())
            ->editColumn('status',  fn($item) => view('admin.transactions.data_table.status', compact('item'))->render())
            ->rawColumns(['record_select', 'actions', 'transaction_id', 'rrn', 'merchant', 'payment_method', 'card_number', 'amount', 'batch_no', 'sdk', 'created_at', 'country', 'payment_type', 'status'])
            ->toJson();
    }

    /**
     * Bulk delete transactions
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|string'
        ]);

        $ids = explode(',', $request->ids);
        $deletedCount = 0;

        foreach ($ids as $id) {
            try {
                $this->transactionService->deleteTransaction($id);
                $deletedCount++;
            } catch (\Exception $e) {
                // Log error but continue with other deletions
                Log::error('Failed to delete transaction ' . $id . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => $deletedCount . ' transactions deleted successfully'
        ]);
    }

    /**
     * Display transaction receipt
     */
    public function receipt($id)
    {
        $transaction = $this->transactionService->getTransaction($id);
        
        if (!$transaction) {
            abort(404, 'Transaction not found');
        }

        return view('admin.transactions.receipt', compact('transaction'));
    }
}
