<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TransactionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'action',
        'action_type',
        'performed_by',
        'performed_by_type',
        'amount_before',
        'amount_after',
        'amount_change',
        'status_before',
        'status_after',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'amount_before' => 'decimal:2',
        'amount_after' => 'decimal:2',
        'amount_change' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Constants for actions
    public const ACTIONS = [
        'created' => 'created',
        'updated' => 'updated',
        'cancelled' => 'cancelled',
        'voided' => 'voided',
        'refunded' => 'refunded',
        'partial_refunded' => 'partial_refunded',
        'status_changed' => 'status_changed',
        'batch_assigned' => 'batch_assigned',
        'batch_removed' => 'batch_removed',
        'processed' => 'processed',
        'failed' => 'failed',
        'declined' => 'declined',
        'approved' => 'approved',
    ];

    // Constants for action types
    public const ACTION_TYPES = [
        'system' => 'system',
        'user' => 'user',
        'admin' => 'admin',
        'api' => 'api',
        'terminal' => 'terminal',
        'batch' => 'batch',
    ];

    // Relationships
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function performer()
    {
        return $this->morphTo('performed_by', 'performed_by_type', 'performed_by');
    }

    // Scopes
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByActionType($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function scopeByTransaction($query, $transactionId)
    {
        return $query->where('transaction_id', $transactionId);
    }

    public function scopeByPerformer($query, $performerId, $performerType = null)
    {
        $query->where('performed_by', $performerId);
        
        if ($performerType) {
            $query->where('performed_by_type', $performerType);
        }
        
        return $query;
    }

    public function scopeAmountChanges($query)
    {
        return $query->whereNotNull('amount_change');
    }

    public function scopeStatusChanges($query)
    {
        return $query->whereNotNull('status_before')->whereNotNull('status_after');
    }

    // Helper methods
    public static function logTransactionAction(
        $transactionId,
        $action,
        $actionType = 'system',
        $description = null,
        $metadata = [],
        $amountBefore = null,
        $amountAfter = null,
        $statusBefore = null,
        $statusAfter = null
    ) {
        $performedBy = null;
        $performedByType = null;
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        // Try to get the current authenticated user/admin
        if (Auth::check()) {
            $user = Auth::user();
            $performedBy = $user->id;
            $performedByType = get_class($user);
        } elseif (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            $performedBy = $admin->id;
            $performedByType = get_class($admin);
        }

        // Calculate amount change
        $amountChange = null;
        if ($amountBefore !== null && $amountAfter !== null) {
            $amountChange = $amountAfter - $amountBefore;
        }

        return self::create([
            'transaction_id' => $transactionId,
            'action' => $action,
            'action_type' => $actionType,
            'performed_by' => $performedBy,
            'performed_by_type' => $performedByType,
            'amount_before' => $amountBefore,
            'amount_after' => $amountAfter,
            'amount_change' => $amountChange,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    // Static helper methods for common actions
    public static function logCreated($transactionId, $amount, $status = 'pending', $description = null, $metadata = [])
    {
        return self::logTransactionAction(
            $transactionId,
            self::ACTIONS['created'],
            'system',
            $description ?: 'Transaction created',
            $metadata,
            null,
            $amount,
            null,
            $status
        );
    }

    public static function logStatusChange($transactionId, $statusBefore, $statusAfter, $description = null, $metadata = [])
    {
        return self::logTransactionAction(
            $transactionId,
            self::ACTIONS['status_changed'],
            'system',
            $description ?: "Status changed from {$statusBefore} to {$statusAfter}",
            $metadata,
            null,
            null,
            $statusBefore,
            $statusAfter
        );
    }

    public static function logRefund($transactionId, $amountBefore, $refundAmount, $description = null, $metadata = [])
    {
        $amountAfter = $amountBefore - $refundAmount;
        
        return self::logTransactionAction(
            $transactionId,
            $refundAmount == $amountBefore ? self::ACTIONS['refunded'] : self::ACTIONS['partial_refunded'],
            'user',
            $description ?: "Refunded {$refundAmount}",
            $metadata,
            $amountBefore,
            $amountAfter,
            null,
            null
        );
    }

    public static function logVoid($transactionId, $amount, $description = null, $metadata = [])
    {
        return self::logTransactionAction(
            $transactionId,
            self::ACTIONS['voided'],
            'user',
            $description ?: 'Transaction voided',
            $metadata,
            $amount,
            0,
            null,
            null
        );
    }

    public static function logCancellation($transactionId, $amount, $description = null, $metadata = [])
    {
        return self::logTransactionAction(
            $transactionId,
            self::ACTIONS['cancelled'],
            'user',
            $description ?: 'Transaction cancelled',
            $metadata,
            $amount,
            0,
            null,
            null
        );
    }

    public static function logBatchAssignment($transactionId, $batchId, $description = null, $metadata = [])
    {
        $metadata['batch_id'] = $batchId;
        
        return self::logTransactionAction(
            $transactionId,
            self::ACTIONS['batch_assigned'],
            'system',
            $description ?: "Assigned to batch {$batchId}",
            $metadata
        );
    }

    public static function logBatchRemoval($transactionId, $batchId, $description = null, $metadata = [])
    {
        $metadata['batch_id'] = $batchId;
        
        return self::logTransactionAction(
            $transactionId,
            self::ACTIONS['batch_removed'],
            'system',
            $description ?: "Removed from batch {$batchId}",
            $metadata
        );
    }

    // Get transaction history
    public static function getTransactionHistory($transactionId, $limit = null)
    {
        $query = self::where('transaction_id', $transactionId)
                    ->orderBy('created_at', 'desc');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    // Get audit trail for a transaction
    public static function getAuditTrail($transactionId)
    {
        return self::where('transaction_id', $transactionId)
                    ->with('performer')
                    ->orderBy('created_at', 'asc')
                    ->get();
    }
}
