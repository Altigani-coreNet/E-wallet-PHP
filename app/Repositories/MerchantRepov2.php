<?php

namespace App\Repositories;

use App\Http\Controllers\Api\Admin\MerchantController;
use App\Http\Resources\TransactionResource;
use App\Models\Log;
use App\Models\Merchant;
use App\Models\Attachments;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Traits\HandlesMerchantFiles;
use App\Traits\HasFiles;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Request;

class MerchantRepov2
{
    use HandlesMerchantFiles, HasFiles;

    protected $model;

    public function __construct(Merchant $model)
    {
        $this->model = $model;
    }

    /**
     * Update merchant profile details
     */
    public function updateProfile(Merchant $merchant, array $data): Merchant
    {
        DB::beginTransaction();
        try {
            // Update merchant details - this will trigger the observer and create a log
            $merchant->update([
                'name' => $data['name'] ?? $merchant->name,
                'owner_name' => $data['owner_name'] ?? $merchant->owner_name,
                'email' => $data['email'] ?? $merchant->email,
                'phone' => $data['phone'] ?? $merchant->phone,
                'business_type' => $data['business_type'] ?? $merchant->business_type   ,
                'address' => $data['address'] ?? $merchant->address,
                'trade_license_number' => $data['trade_license_number'] ?? $merchant->trade_license_number,
                'tax_number' => $data['tax_certified_number'] ?? $merchant->tax_number,
                'country_id' => $data['country'] ?? $merchant->country_id,
                'city_id' => $data['city'] ?? $merchant->city_id,
            ]);

            // Get the log that was just created by the observer and add metadata
            $lastLog = Log::where('loggable_type', Merchant::class)
                ->where('loggable_id', $merchant->id)
                ->where('action', 'updated')
                ->latest()
                ->first();

            if ($lastLog) {
                $existingProperties = json_decode($lastLog->properties, true) ?? [];
                $existingProperties['metadata'] = [
                    'updated_via' => 'profile_form',
                    'updated_at' => now()->toDateTimeString(),
                    'user_id' => Auth::user()->id, 
                    'message' => 'Merchant profile updated'
                ];

                $lastLog->update([
                    'properties' => json_encode($existingProperties)
                ]);
            }

            $merchant->update([
                'status' => 'pending',
            ]);
            
            DB::commit();
            return $merchant;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Create a change request for merchant profile updates and log it.
     */
    public function createProfileChangeRequest(Merchant $merchant, array $payload): \App\Models\ChangeRequest
    {
        DB::beginTransaction();
        try {
            $previousStatus = $merchant->status;
            $payloadWithMeta = $payload;
            $payloadWithMeta['__meta'] = [
                'previous_status' => $previousStatus,
            ];

            $cr = \App\Models\ChangeRequest::create([
                'changeable_type' => Merchant::class,
                'changeable_id' => $merchant->id,
                'requester_type' => Auth::user() ? get_class(Auth::user()) : null,
                'requester_id' => Auth::user()->id ?? null,
                'payload' => $payloadWithMeta,
                'reason' => null,
                'status' => 'pending',
                'has_file' => false,
            ]);

            // Log change requested on merchant
            Log::create([
                'loggable_type' => Merchant::class,
                'loggable_id' => $merchant->id,
                'user_id' => Auth::user()->id ?? null,
                'user_type' => Auth::user() ? get_class(Auth::user()) : null,
                'action' => 'change_requested',
                'description' => 'Merchant profile change requested',
                'metadata' => json_encode([
                    'requested_at' => now()->toDateTimeString(),
                    'fields' => array_keys($payload),
                ]),
            ]);

            $merchant->update([
                'status' => 'requesting_updated',
            ]);
            
            DB::commit();
            return $cr;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a change request for attachments: upload files, store key=>path pairs.
     */
    public function createAttachmentChangeRequest(Merchant $merchant, \Illuminate\Http\Request $request): \App\Models\ChangeRequest
    {
        DB::beginTransaction();
        try {
            $files = $request->allFiles();
            $payload = [];

            foreach ($files as $key => $file) {
                if (!$file) { continue; }
                $path = $file->store('merchant_documents/' . $merchant->id, 'public');
                $payload[$key] = $path;
            }

            $previousStatus = $merchant->status;
            $payloadWithMeta = $payload;
            $payloadWithMeta['__meta'] = [
                'previous_status' => $previousStatus,
            ];

            $merchant->update([
                'status' => 'requesting_updated',
            ]);

            $cr = \App\Models\ChangeRequest::create([
                'changeable_type' => Merchant::class,
                'changeable_id' => $merchant->id,
                'requester_type' => $merchant->user() ? get_class(Auth::user()) : null,
                'requester_id' => Auth::user()->id ?? null,
                'payload' => $payloadWithMeta,
                'reason' => null,
                'status' => 'pending',
                'has_file' => true,
            ]);

            Log::create([
                'loggable_type' => Merchant::class,
                'loggable_id' => $merchant->id,
                'user_id' => Auth::user()->id ?? null,
                'user_type' => Auth::user() ? get_class(Auth::user()) : null,
                'action' => 'change_requested',
                'description' => 'User requested to update ' . implode(', ', array_keys($payload)) . ' attachments',
                'metadata' => json_encode([
                    'requested_at' => now()->toDateTimeString(),
                    'files' => array_keys($payload),
                ]),
            ]);

            DB::commit();
            return $cr;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update merchant attachments
     */
    public function updateAttachments(HttpRequest $request ,Merchant $merchant): void
    {
        DB::beginTransaction();
        try {
            // dd($request->all());
            // Get all files from request
            $files = $request->allFiles();
            
            // Format the file names for the log message
            $updatedFileTypes = array_keys($files);
            $formattedFileTypes = array_map(function($type) {
                return ucwords(str_replace('_', ' ', $type));
            }, $updatedFileTypes);

            // Create the message
            $message = 'Updated documents: ' . implode(', ', $formattedFileTypes);


            // Upload the images
            $this->uploadImages($request, $merchant);


            // Create new log entry
            Log::create([
                'loggable_type' => Merchant::class,
                'loggable_id' => $merchant->id,
                'user_id' => Auth::user()->id,
                'user_type' => User::class,
                'action' => 'attachments_updated',
                'description' => 'Merchant documents updated',
                'metadata' => json_encode([
                        'updated_via' => 'attachments_form',
                        'updated_at' => now()->toDateTimeString(),
                        'user_id' => Auth::user()->id,
                        'message' => $message,
                        'updated_files' => $updatedFileTypes
                ]),
            ]);
            // dd('logos account');

            $merchant->update([
                'status' => 'pending',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Create a new merchant
     */
    /**
     * Register a new merchant with user account
     * 
     * @param array $data
     * @return array
     */
    public function registerMerchant(HttpRequest $request): array
    {
        DB::beginTransaction();
        try {
            // Create user account
            $user = \App\Models\User::create([
                'name' => $request->owner_name,
                'email' => $request->email,
                'password' => $request->password ? bcrypt($request->password) : bcrypt('12345678'),
                'phone' => $request->phone,
                'role' => 'merchant',
            ]);

            // Create merchant profile
            $merchant = $this->model->create([
                'name' => $request->business_name,
                'owner_name' => $request->owner_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'user_id' => $user->id,
                'business_type' => $request->business_type,
                'status' => 'pending',
                'merchant_code' => $this->model->generateMerchantCode(),
                'is_active' => false,
                'address' => $request->business_address,
                'latitude' => $request->lat,
                'longitude' => $request->long,
                'add_type' => 'api',
            ]);

            // Update user's merchant_id
            $user->update([
                'merchant_id' => $merchant->id
            ]);

            // Create merchant-specific role with web permissions
          
            $this->uploadImages($request, $merchant);

            // Send registration confirmation email
            Mail::to($user->email)->send(new \App\Mail\MerchantRegistrationConfirmationMail($user, $merchant));

            DB::commit();

            return [
                'user' => $user,
                'merchant' => $merchant,
                'status' => 'success',
                'message' => 'Merchant registered successfully'
            ];
        } catch (\Exception $e) {
            DB::rollback();
            
            throw new \Exception('Failed to register merchant: ' . $e->getMessage());
        }
    }

    /**
     * Handle document uploads for merchant
     */
    protected function handleDocumentUploads(array $documents, Merchant $merchant): void
    {
        foreach ($documents as $type => $file) {
            $path = $file->store('merchant_documents/' . $merchant->id, 'public');
            $merchant->attachments()->create([
                'type' => $type,
                'path' => $path
            ]);
        }
    }

    /**
     * Create a new merchant
     */
    public function create(array $data): Merchant
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing merchant
     */
    public function update(Merchant $merchant, array $data): Merchant
    {
        $merchant->update($data);

        return $merchant->fresh();
    }

    /**
     * Delete a merchant
     */
    public function delete(Merchant $merchant): bool
    {
        return $merchant->delete();
    }

    /**
     * Find merchant by ID
     */
    public function findById(int $id): ?Merchant
    {
        return $this->model->find($id);
    }

    /**
     * Get all merchants
     */
    public function getAll()
    {
        return $this->model->with('user')->get();
    }

    /**
     * Get active merchants
     */
    public function getActive()
    {
        return $this->model->where('is_active', true)->with('user')->get();
    }

    /**
     * Find merchant by email
     */
    public function findByEmail(string $email): ?Merchant
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find merchant by merchant code
     */
    public function findByMerchantCode(string $merchantCode): ?Merchant
    {
        return $this->model->where('merchant_code', $merchantCode)->first();
    }

    /**
     * Get daily transaction statistics for merchant
     */
    public function getDailyTransactionStats(string $merchantId, int $days = 30, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        $query = Transaction::where('merchant_id', $merchantId);
        
        // Apply status filter if provided, otherwise default to approved
        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'approved');
        }
        
        // Apply datetime filters if provided
        if ($datetimeFrom && $datetimeTo) {
            $query->whereBetween('created_at', [$datetimeFrom, $datetimeTo]);
            // Calculate actual days between datetimes for proper range
            $days = Carbon::parse($datetimeFrom)->diffInDays(Carbon::parse($datetimeTo)) + 1;
        } else {
            $startDate = Carbon::now()->subDays($days);
            $query->where('created_at', '>=', $startDate);
        }
        
        $transactions = $query
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = [];
        $counts = [];
        $amounts = [];

        // Fill in missing dates with zero values
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dates[] = Carbon::parse($date)->format('M d');
            
            $transaction = $transactions->where('date', $date)->first();
            $counts[] = $transaction ? $transaction->count : 0;
            $amounts[] = $transaction ? (float) $transaction->total_amount : 0;
        }

        return [
            'labels' => $dates,
            'counts' => $counts,
            'amounts' => $amounts,
            'total_transactions' => array_sum($counts),
            'total_amount' => array_sum($amounts),
            'average_per_day' => array_sum($counts) / $days
        ];
    }

    /**
     * Get weekly transaction statistics for merchant
     */
    public function getWeeklyTransactionStats(string $merchantId, int $weeks = 12, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        $query = Transaction::where('merchant_id', $merchantId);
        
        // Apply status filter if provided, otherwise default to approved
        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'approved');
        }
        
        // Apply datetime filters if provided
        if ($datetimeFrom && $datetimeTo) {
            $query->whereBetween('created_at', [$datetimeFrom, $datetimeTo]);
            // Calculate actual weeks between datetimes for proper range
            $weeks = Carbon::parse($datetimeFrom)->diffInWeeks(Carbon::parse($datetimeTo)) + 1;
        } else {
            $startDate = Carbon::now()->subWeeks($weeks);
            $query->where('created_at', '>=', $startDate);
        }
        
        $transactions = $query
            ->select(
                DB::raw('YEARWEEK(created_at) as week'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        $labels = [];
        $counts = [];
        $amounts = [];

        // Fill in missing weeks with zero values
        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            $weekKey = $weekStart->format('Y-W');
            
            $labels[] = $weekStart->format('M d') . ' - ' . $weekEnd->format('M d');
            
            $transaction = $transactions->where('week', $weekKey)->first();
            $counts[] = $transaction ? $transaction->count : 0;
            $amounts[] = $transaction ? (float) $transaction->total_amount : 0;
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'amounts' => $amounts,
            'total_transactions' => array_sum($counts),
            'total_amount' => array_sum($amounts),
            'average_per_week' => array_sum($counts) / $weeks
        ];
    }

    /**
     * Get monthly transaction statistics for merchant
     */
    public function getMonthlyTransactionStats(string $merchantId, int $months = 12, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        $query = Transaction::where('merchant_id', $merchantId);
        
        // Apply status filter if provided, otherwise default to approved
        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'approved');
        }
        
        // Apply datetime filters if provided
        if ($datetimeFrom && $datetimeTo) {
            $query->whereBetween('created_at', [$datetimeFrom, $datetimeTo]);
            // Calculate actual months between datetimes for proper range
            $months = Carbon::parse($datetimeFrom)->diffInMonths(Carbon::parse($datetimeTo)) + 1;
        } else {
            $startDate = Carbon::now()->subMonths($months);
            $query->where('created_at', '>=', $startDate);
        }
        
        $transactions = $query
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $labels = [];
        $counts = [];
        $amounts = [];

        // Fill in missing months with zero values
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthKey = $month->format('Y-m');
            
            $labels[] = $month->format('M Y');
            
            $transaction = $transactions->where('year', $month->year)->where('month', $month->month)->first();
            $counts[] = $transaction ? $transaction->count : 0;
            $amounts[] = $transaction ? (float) $transaction->total_amount : 0;
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'amounts' => $amounts,
            'total_transactions' => array_sum($counts),
            'total_amount' => array_sum($amounts),
            'average_per_month' => array_sum($counts) / $months
        ];
    }

    /**
     * Get overall transaction summary for merchant
     */
    public function getTransactionSummary(string $merchantId, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisYear = Carbon::now()->startOfYear();

        // If datetime filters are provided, use them instead of default periods
        if ($datetimeFrom && $datetimeTo) {
            $todayQuery = Transaction::where('merchant_id', $merchantId)
                ->whereBetween('created_at', [$datetimeFrom, $datetimeTo]);
            if ($status) {
                $todayQuery->where('status', $status);
            }
            
            $summary = [
                'today' => [
                    'count' => $todayQuery->count(),
                    'amount' => Transaction::where('merchant_id', $merchantId)
                        ->whereBetween('created_at', [$datetimeFrom, $datetimeTo])
                        ->when($status, function($query) use ($status) {
                            return $query->where('status', $status);
                        })
                        ->sum('amount')
                ],
                'this_week' => [
                    'count' => Transaction::where('merchant_id', $merchantId)
                        ->whereBetween('created_at', [$datetimeFrom, $datetimeTo])
                        ->when($status, function($query) use ($status) {
                            return $query->where('status', $status);
                        })
                        ->count(),
                    'amount' => Transaction::where('merchant_id', $merchantId)
                        ->whereBetween('created_at', [$datetimeFrom, $datetimeTo])
                        ->when($status, function($query) use ($status) {
                            return $query->where('status', $status);
                        })
                        ->sum('amount')
                ],
                'this_month' => [
                    'count' => Transaction::where('merchant_id', $merchantId)
                        ->whereBetween('created_at', [$datetimeFrom, $datetimeTo])
                        ->when($status, function($query) use ($status) {
                            return $query->where('status', $status);
                        })
                        ->count(),
                    'amount' => Transaction::where('merchant_id', $merchantId)
                        ->whereBetween('created_at', [$datetimeFrom, $datetimeTo])
                        ->when($status, function($query) use ($status) {
                            return $query->where('status', $status);
                        })
                        ->sum('amount')
                ],
                'this_year' => [
                    'count' => Transaction::where('merchant_id', $merchantId)
                        ->whereBetween('created_at', [$datetimeFrom, $datetimeTo])
                        ->when($status, function($query) use ($status) {
                            return $query->where('status', $status);
                        })
                        ->count(),
                    'amount' => Transaction::where('merchant_id', $merchantId)
                        ->whereBetween('created_at', [$datetimeFrom, $datetimeTo])
                        ->when($status, function($query) use ($status) {
                            return $query->where('status', $status);
                        })
                        ->sum('amount')
                ]
            ];
        } else {
            $summary = [
                'today' => [
                    'count' => Transaction::where('merchant_id', $merchantId)
                        // ->where('status', 'approved')
                        ->whereDate('created_at', $today)
                        ->count(),
                    'amount' => Transaction::where('merchant_id', $merchantId)
                        // ->where('status', 'approved')
                        ->whereDate('created_at', $today)
                        ->sum('amount')
                ],
                'this_week' => [
                    'count' => Transaction::where('merchant_id', $merchantId)
                        // ->where('status', 'approved')
                        ->where('created_at', '>=', $thisWeek)
                        ->count(),
                    'amount' => Transaction::where('merchant_id', $merchantId)
                        // ->where('status', 'approved')
                        ->where('created_at', '>=', $thisWeek)
                        ->sum('amount')
                ],
                'this_month' => [
                    'count' => Transaction::where('merchant_id', $merchantId)
                        // ->where('status', 'approved')
                        ->where('created_at', '>=', $thisMonth)
                        ->count(),
                    'amount' => Transaction::where('merchant_id', $merchantId)
                        // ->where('status', 'approved')
                        ->where('created_at', '>=', $thisMonth)
                        ->sum('amount')
                ],
                'this_year' => [
                    'count' => Transaction::where('merchant_id', $merchantId)
                        // ->where('status', 'approved')
                        ->where('created_at', '>=', $thisYear)
                        ->count(),
                    'amount' => Transaction::where('merchant_id', $merchantId)
                        // ->where('status', 'approved')
                        ->where('created_at', '>=', $thisYear)
                        ->sum('amount')
                ]
            ];
        }

        return $summary;
    }

    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStatistics($merchant = null): array
    {
        $query = \App\Models\Terminal::query();
        $userQuery = \App\Models\User::query();
        $branchQuery = \App\Models\Branch::query();
        
        // If merchant is specified, filter by merchant
        if ($merchant) {
            $userQuery->where('merchant_id', $merchant);
            $branchQuery->where('merchant_id', $merchant);
        }
        
        // Get total counts
        $totalTerminals = $query->count();
        $activeTerminals = (clone $query)->where('is_active', true)->count();
        $totalUsers = $userQuery->count();
        $totalBranches = $branchQuery->count();
        
        // Get transaction statistics by status
        // $transactionStats = $this->getTransactionStatisticsByStatus($merchant);
        
        // Get latest transactions by status
        // $latestTransactions = $this->getLatestTransactionsByStatus($merchant);
        
        return array_merge([
            'totalTerminals' => $totalTerminals,
            'activeTerminals' => $activeTerminals,
            'totalUsers' => $totalUsers,
            'totalBranches' => $totalBranches,
            'merchant' => $merchant,
        ]);
    }

    /**
     * Get transaction statistics by status
     */
    public function getTransactionStatisticsByStatus(?string $merchantId = null, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        $query = Transaction::query();
        
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }
        
        // Apply datetime filters if provided
        if ($datetimeFrom && $datetimeTo) {
            $query->whereBetween('created_at', [$datetimeFrom, $datetimeTo]);
        }
        
        // Apply status filter if provided
        if ($status) {
            $query->where('status', $status);
        }
        
        // Total transactions
        $totalTransactions = $query->count();
        
        // Sale transactions (approved, pending, capture)
        $saleTransactions = (clone $query)
            ->whereIn('status', ['approved', 'pending', 'captured'])
            ->count();
        
        $saleTransactionsAmount = (clone $query)
            ->whereIn('status', ['approved', 'pending', 'captured'])
            ->sum('amount');
        
        // Refund transactions
        $refundTransactions = (clone $query)
            ->where('status', 'refunded')
            ->count();
        
        $refundTransactionsAmount = (clone $query)
            ->where('status', 'refunded')
            ->sum('amount');
        
        // Void transactions
        $voidTransactions = (clone $query)
            ->where('status', 'voided')
            ->count();
        
        $voidTransactionsAmount = (clone $query)
            ->where('status', 'voided')
            ->sum('amount');
        
        return [
            'totalTransactions' => $totalTransactions,
            'saleTransactions' => $saleTransactions,
            'saleTransactionsAmount' => $saleTransactionsAmount,
            'refundTransactions' => $refundTransactions,
            'refundTransactionsAmount' => $refundTransactionsAmount,
            'voidTransactions' => $voidTransactions,
            'voidTransactionsAmount' => $voidTransactionsAmount,
        ];
    }

    /**
     * Get latest transactions by status
     */
    public function getLatestTransactionsByStatus(?string $merchantId = null): array
    {
        $query = Transaction::query()
            ->with(['terminal', 'user'])
            ->orderBy('created_at', 'desc');
        
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }
        
        // Latest 5 sales (approved, pending, capture)
        $latestSales = (clone $query)
            ->whereIn('status', ['approved', 'pending', 'captured'])
            ->limit(5)
            ->get();
        
        // Latest 5 refunds
        $latestRefunds = (clone $query)
            ->where('status', 'refunded')
            ->limit(5)
            ->get();
        
        // Latest 5 voids
        $latestVoids = (clone $query)
            ->where('status', 'voided')
            ->limit(5)
            ->get();
        
        return [
            'latestSales' =>  TransactionResource::collection($latestSales),
            'latestRefunds' =>  TransactionResource::collection($latestRefunds),
            'latestVoids' =>  TransactionResource::collection($latestVoids),
        ];
    }

    /**
     * Get terminal data by status for the dashboard
     */
    public function getTerminalDataByStatus(?string $merchantId = null): array
    {
        $query = \App\Models\Terminal::query();
        
        // Get online terminals (terminals that have users with current_terminal_id)
        $onlineTerminals = (clone $query)
            ->limit(10)
            ->get();
            
        // Get offline terminals (terminals that don't have users with current_terminal_id)
        $offlineTerminals = (clone $query)
            ->limit(10)
            ->get();
            
        // Get testing terminals (terminals with testing status)
        $testingTerminals = (clone $query)
            ->testing()
            ->limit(10)
            ->get();
        
        // Get terminal counts by status
        $onlineCount = (clone $query)->online()->count();
        $offlineCount = (clone $query)->offline()->count();
        $testingCount = (clone $query)->testing()->count();
        
        return [
            'onlineTerminals' => $onlineTerminals,
            'offlineTerminals' => $offlineTerminals,
            'testingTerminals' => $testingTerminals,
            'onlineCount' => $onlineCount,
            'offlineCount' => $offlineCount,
            'testingCount' => $testingCount,
        ];
    }

    /**
     * Get comprehensive dashboard data for API
     */
    public function getDashboardDataForApi(string $merchantId): array
    {
        // dd($merchantId);
        $basicStats = $this->getDashboardStatistics($merchantId); // We'll filter by merchant in the method
        // $terminalData = $this->getTerminalDataByStatus($merchantId);
        $transactionStats = $this->getTransactionStatisticsByStatus($merchantId);
        $latestTransactions = $this->getLatestTransactionsByStatus($merchantId);
        
        return array_merge($basicStats, $transactionStats, $latestTransactions);
    }

    /**
     * Generate daily transaction chart data for the last 30 days
     */
    public function generateDailyTransactionChartData(string $merchantId, int $days = 30, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        $dailyData = [];
        $dailyLabels = [];
        
        // If datetime filters are provided, use them instead of default days
        if ($datetimeFrom && $datetimeTo) {
            $startDate = Carbon::parse($datetimeFrom);
            $endDate = Carbon::parse($datetimeTo);
            $days = $startDate->diffInDays($endDate) + 1;
            
            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);
                $dailyLabels[] = $date->format('M d');
                
                $dailyAmountQuery = Transaction::where('merchant_id', $merchantId)
                    ->whereDate('created_at', $date);
                
                // Apply status filter
                if ($status) {
                    $dailyAmountQuery->where('status', $status);
                } else {
                    $dailyAmountQuery->whereIn('status', ['approved', 'pending', 'captured']);
                }
                
                $dailyAmount = $dailyAmountQuery->sum('amount');
                $dailyData[] = (float) $dailyAmount;
            }
            
            $totalTransactionsQuery = Transaction::where('merchant_id', $merchantId)
                ->whereBetween('created_at', [$datetimeFrom, $datetimeTo]);
            
            // Apply status filter
            if ($status) {
                $totalTransactionsQuery->where('status', $status);
            } else {
                $totalTransactionsQuery->where('status', 'approved');
            }
            
            $totalTransactions = $totalTransactionsQuery->count();
        } else {
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dailyLabels[] = $date->format('M d');
                
                $dailyAmountQuery = Transaction::where('merchant_id', $merchantId)
                    ->whereDate('created_at', $date);
                
                // Apply status filter
                if ($status) {
                    $dailyAmountQuery->where('status', $status);
                } else {
                    $dailyAmountQuery->whereIn('status', ['approved', 'pending', 'captured']);
                }
                
                $dailyAmount = $dailyAmountQuery->sum('amount');
                $dailyData[] = (float) $dailyAmount;
            }
            
            $totalTransactionsQuery = Transaction::where('merchant_id', $merchantId)
                ->where('created_at', '>=', Carbon::now()->subDays($days));
            
            // Apply status filter
            if ($status) {
                $totalTransactionsQuery->where('status', $status);
            } else {
                $totalTransactionsQuery->where('status', 'approved');
            }
            
            $totalTransactions = $totalTransactionsQuery->count();
        }
        
        return [
            'labels' => $dailyLabels,
            'amounts' => $dailyData,
            'total_amount' => array_sum($dailyData),
            'average_per_day' => array_sum($dailyData) / $days,
            'total_transactions' => $totalTransactions
        ];
    }

    // ... existing code ...

public function generateHourlyTransactionChartWithStatus(string $merchantId, ?string $status = null): array
{
    $hourlyData = [];
    $hourlyLabels = [];
    
    // Define status filter
    $statusFilter = $status ? [$status] : Transaction::TRANSACTION_STATUS;
    
    // Generate data for last 24 hours
    for ($i = 23; $i >= 0; $i--) {
        $hour = Carbon::now()->subHours($i);
        $hourlyLabels[] = $hour->format('H:i'); // Format as "14:00" (24-hour format)
        
        $hourlyAmount = Transaction::where('merchant_id', $merchantId)
            ->whereIn('status', $statusFilter)    
            ->whereRaw('HOUR(created_at) = ?', [$hour->hour])
            ->whereDate('created_at', $hour->toDateString());
            if ($status === 'voided') {
                $hourlyAmount = $hourlyAmount->sum('original_amount');
            } else {
                $hourlyAmount = $hourlyAmount->sum('amount');
            }
        
        $hourlyData[] = (float) $hourlyAmount;
    }
    
    return [
        'labels' => $hourlyLabels,
        'amounts' => $hourlyData,
        'total_amount' => array_sum($hourlyData),
        'average_per_hour' => array_sum($hourlyData) / 24,
        'total_transactions' => Transaction::where('merchant_id', $merchantId)
            ->whereIn('status', $statusFilter)
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->count()
    ];
}

// ... existing code ...
    

    /**
     * Generate weekly transaction chart data for the last 12 weeks
     */
    public function generateWeeklyTransactionChartData(string $merchantId, int $weeks = 12, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        $weeklyData = [];
        $weeklyLabels = [];
        
        // If datetime filters are provided, use them instead of default weeks
        if ($datetimeFrom && $datetimeTo) {
            $startDate = Carbon::parse($datetimeFrom)->startOfWeek();
            $endDate = Carbon::parse($datetimeTo)->endOfWeek();
            $weeks = $startDate->diffInWeeks($endDate) + 1;
            
            for ($i = 0; $i < $weeks; $i++) {
                $weekStart = $startDate->copy()->addWeeks($i);
                $weekEnd = $weekStart->copy()->endOfWeek();
                $weeklyLabels[] = $weekStart->format('M d') . ' - ' . $weekEnd->format('M d');
                
                $weeklyAmountQuery = Transaction::where('merchant_id', $merchantId)
                    ->whereBetween('created_at', [$weekStart, $weekEnd]);
                
                // Apply status filter
                if ($status) {
                    $weeklyAmountQuery->where('status', $status);
                } else {
                    $weeklyAmountQuery->whereIn('status', ['approved', 'pending', 'captured']);
                }
                
                $weeklyAmount = $weeklyAmountQuery->sum('amount');
                $weeklyData[] = (float) $weeklyAmount;
            }
            
            $totalTransactionsQuery = Transaction::where('merchant_id', $merchantId)
                ->whereBetween('created_at', [$datetimeFrom, $datetimeTo]);
            
            // Apply status filter
            if ($status) {
                $totalTransactionsQuery->where('status', $status);
            } else {
                $totalTransactionsQuery->whereIn('status', ['approved', 'pending', 'captured']);
            }
            
            $totalTransactions = $totalTransactionsQuery->count();
        } else {
            for ($i = $weeks - 1; $i >= 0; $i--) {
                $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
                $weekEnd = $weekStart->copy()->endOfWeek();
                $weeklyLabels[] = $weekStart->format('M d') . ' - ' . $weekEnd->format('M d');
                
                $weeklyAmountQuery = Transaction::where('merchant_id', $merchantId)
                    ->whereBetween('created_at', [$weekStart, $weekEnd]);
                
                // Apply status filter
                if ($status) {
                    $weeklyAmountQuery->where('status', $status);
                } else {
                    $weeklyAmountQuery->whereIn('status', ['approved', 'pending', 'captured']);
                }
                
                $weeklyAmount = $weeklyAmountQuery->sum('amount');
                $weeklyData[] = (float) $weeklyAmount;
            }
            
            $totalTransactionsQuery = Transaction::where('merchant_id', $merchantId)
                ->where('created_at', '>=', Carbon::now()->subWeeks($weeks));
            
            // Apply status filter
            if ($status) {
                $totalTransactionsQuery->where('status', $status);
            } else {
                $totalTransactionsQuery->whereIn('status', ['approved', 'pending', 'captured']);
            }
            
            $totalTransactions = $totalTransactionsQuery->count();
        }
        
        return [
            'labels' => $weeklyLabels,
            'amounts' => $weeklyData,
            'total_amount' => array_sum($weeklyData),
            'average_per_week' => array_sum($weeklyData) / $weeks,
            'total_transactions' => $totalTransactions
        ];
    }

    public function generateWeeklyTransactionChartWithStatus(string $merchantId, int $weeks = 12, ?string $status = null): array
{
    $dailyData = [];
    $dailyLabels = [];
    
    // Define status filter
    $statusFilter = $status ? [$status] : Transaction::TRANSACTION_STATUS;
    
    // Generate data for last 7 days (including today)
    for ($i = 6; $i >= 0; $i--) {
        $date = Carbon::now()->subDays($i);
        $dailyLabels[] = $date->format('M d'); // Format as "Dec 15"
        
        $dailyAmount = Transaction::where('merchant_id', $merchantId)
            ->whereIn('status', $statusFilter)    
            ->whereDate('created_at', $date);
            if ($status === 'voided') {
                $dailyAmount = $dailyAmount->sum('original_amount');
            } else {
                $dailyAmount = $dailyAmount->sum('amount');
            }
        
        $dailyData[] = (float) $dailyAmount;
    }
    
    return [
        'labels' => $dailyLabels,
        'amounts' => $dailyData,
        'total_amount' => array_sum($dailyData),
        'average_per_day' => array_sum($dailyData) / 7,
        'total_transactions' => Transaction::where('merchant_id', $merchantId)
            ->whereIn('status', $statusFilter)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count()
    ];
}

    /**
     * Generate monthly transaction chart data for the last 12 months
     */
    public function generateMonthlyTransactionChartData(string $merchantId, int $months = 12, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        $monthlyData = [];
        $monthlyLabels = [];
        
        // If datetime filters are provided, use them instead of default months
        if ($datetimeFrom && $datetimeTo) {
            $startDate = Carbon::parse($datetimeFrom)->startOfMonth();
            $endDate = Carbon::parse($datetimeTo)->endOfMonth();
            $months = $startDate->diffInMonths($endDate) + 1;
            
            for ($i = 0; $i < $months; $i++) {
                $month = $startDate->copy()->addMonths($i);
                $monthlyLabels[] = $month->format('M Y');
                
                $monthlyAmountQuery = Transaction::where('merchant_id', $merchantId)
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month);
                
                // Apply status filter
                if ($status) {
                    $monthlyAmountQuery->where('status', $status);
                } else {
                    $monthlyAmountQuery->whereIn('status', ['approved', 'pending', 'captured']);
                }
                
                $monthlyAmount = $monthlyAmountQuery->sum('amount');
                $monthlyData[] = (float) $monthlyAmount;
            }
            
            $totalTransactionsQuery = Transaction::where('merchant_id', $merchantId)
                ->whereBetween('created_at', [$datetimeFrom, $datetimeTo]);
            
            // Apply status filter
            if ($status) {
                $totalTransactionsQuery->where('status', $status);
            } else {
                $totalTransactionsQuery->where('status', 'approved');
            }
            
            $totalTransactions = $totalTransactionsQuery->count();
        } else {
            for ($i = $months - 1; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $monthlyLabels[] = $month->format('M Y');
                
                $monthlyAmountQuery = Transaction::where('merchant_id', $merchantId)
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month);
                
                // Apply status filter
                if ($status) {
                    $monthlyAmountQuery->where('status', $status);
                } else {
                    $monthlyAmountQuery->whereIn('status', ['approved', 'pending', 'captured']);
                }
                
                $monthlyAmount = $monthlyAmountQuery->sum('amount');
                $monthlyData[] = (float) $monthlyAmount;
            }
            
            $totalTransactionsQuery = Transaction::where('merchant_id', $merchantId)
                ->where('created_at', '>=', Carbon::now()->subMonths($months));
            
            // Apply status filter
            if ($status) {
                $totalTransactionsQuery->where('status', $status);
            } else {
                $totalTransactionsQuery->where('status', 'approved');
            }
            
            $totalTransactions = $totalTransactionsQuery->count();
        }
        
        return [
            'labels' => $monthlyLabels,
            'amounts' => $monthlyData,
            'total_amount' => array_sum($monthlyData),
            'average_per_month' => array_sum($monthlyData) / $months,
            'total_transactions' => $totalTransactions
        ];
    }


    // ... existing code ...

public function generateMonthlyTransactionChartWithStatus(string $merchantId, ?string $status = null): array
{
    $monthlyData = [];
    $monthlyLabels = [];
    
    // Define status filter
    $statusFilter = $status ? [$status] : ['approved', 'pending', 'captured'];
    
    // Generate data for last 30 days
    for ($i = 29; $i >= 0; $i--) {
        $date = Carbon::now()->subDays($i);
        $monthlyLabels[] = $date->format('M d'); // Format as "Dec 15"
        
        $monthlyAmount = Transaction::where('merchant_id', $merchantId)
            ->whereIn('status', $statusFilter)
            ->whereDate('created_at', $date);
            if ($status === 'voided') {
                $monthlyAmount = $monthlyAmount->sum('original_amount');
            } else {
                $monthlyAmount = $monthlyAmount->sum('amount');
            }
        
        $monthlyData[] = (float) $monthlyAmount;
    }
    
    return [
        'labels' => $monthlyLabels,
        'amounts' => $monthlyData,
        'total_amount' => array_sum($monthlyData),
        'average_per_day' => array_sum($monthlyData) / 30,
        'total_transactions' => Transaction::where('merchant_id', $merchantId)
            ->whereIn('status', $statusFilter)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count()
    ];
}

// ... existing code ...

    /**
     * Generate hourly transaction chart data
     */
    public function generateHourlyTransactionChartData(string $merchantId, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        $hourlyData = [];
        $hourlyLabels = [];
        
        // Define status filter
        $statusFilter = $status ? [$status] : ['approved', 'pending', 'captured'];
        
        // If datetime filters are provided, use them; otherwise use last 24 hours
        if ($datetimeFrom && $datetimeTo) {
            $startDate = Carbon::parse($datetimeFrom);
            $endDate = Carbon::parse($datetimeTo);
            
            // Generate hourly data for the date range
            $current = $startDate->copy()->startOfHour();
            while ($current <= $endDate) {
                $hourlyLabels[] = $current->format('H:i');
                
                $hourlyAmount = Transaction::where('merchant_id', $merchantId)
                    ->whereIn('status', $statusFilter)
                    ->whereRaw('HOUR(created_at) = ?', [$current->hour])
                    ->whereDate('created_at', $current->toDateString());
                
                $hourlyAmount = $hourlyAmount->sum('amount');
                $hourlyData[] = (float) $hourlyAmount;
                
                $current->addHour();
            }
        } else {
            // Default: last 24 hours
            for ($i = 23; $i >= 0; $i--) {
                $hour = Carbon::now()->subHours($i);
                $hourlyLabels[] = $hour->format('H:i');
                
                $hourlyAmount = Transaction::where('merchant_id', $merchantId)
                    ->whereIn('status', $statusFilter)
                    ->whereRaw('HOUR(created_at) = ?', [$hour->hour])
                    ->whereDate('created_at', $hour->toDateString())
                    ->sum('amount');
                
                $hourlyData[] = (float) $hourlyAmount;
            }
        }
        
        return [
            'labels' => $hourlyLabels,
            'amounts' => $hourlyData,
            'total_amount' => array_sum($hourlyData),
            'average_per_hour' => count($hourlyData) > 0 ? array_sum($hourlyData) / count($hourlyData) : 0,
            'total_transactions' => Transaction::where('merchant_id', $merchantId)
                ->whereIn('status', $statusFilter)
                ->where('created_at', '>=', $datetimeFrom ? Carbon::parse($datetimeFrom) : Carbon::now()->subHours(24))
                ->where('created_at', '<=', $datetimeTo ? Carbon::parse($datetimeTo) : Carbon::now())
                ->count()
        ];
    }

    /**
     * Generate comprehensive transaction chart data
     */
    public function generateTransactionChartData(string $merchantId, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        $hourlyData = $this->generateHourlyTransactionChartData($merchantId, $datetimeFrom, $datetimeTo, $status);
        $dailyData = $this->generateDailyTransactionChartData($merchantId, 30, $datetimeFrom, $datetimeTo, $status);
        $weeklyData = $this->generateWeeklyTransactionChartData($merchantId, 12, $datetimeFrom, $datetimeTo, $status);
        $monthlyData = $this->generateMonthlyTransactionChartData($merchantId, 12, $datetimeFrom, $datetimeTo, $status);
        
        return [
            'hourly' => $this->formatChartDataForApex($hourlyData),
            'daily' => $this->formatChartDataForApex($dailyData),
            'weekly' => $this->formatChartDataForApex($weeklyData),
            'monthly' => $this->formatChartDataForApex($monthlyData)
        ];
    }
    
    /**
     * Format chart data for ApexCharts series structure
     */
    private function formatChartDataForApex(array $data): array
    {
        // If no labels or amounts, return empty structure
        if (empty($data['labels']) || empty($data['amounts'])) {
            return [
                'labels' => ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
                'series' => [
                    ['name' => 'Approved', 'data' => [0, 0, 0, 0, 0, 0, 0]],
                    ['name' => 'Voided', 'data' => [0, 0, 0, 0, 0, 0, 0]],
                    ['name' => 'Refunded', 'data' => [0, 0, 0, 0, 0, 0, 0]]
                ]
            ];
        }
        
        return [
            'labels' => $data['labels'],
            'series' => [
                ['name' => 'Approved', 'data' => $data['amounts']],
                ['name' => 'Voided', 'data' => array_fill(0, count($data['amounts']), 0)],
                ['name' => 'Refunded', 'data' => array_fill(0, count($data['amounts']), 0)]
            ]
        ];
    }

    // ==================== ADMIN DASHBOARD METHODS ====================

    /**
     * Get admin dashboard statistics (across all merchants)
     */
    public function getAdminDashboardStatistics(): array
    {
        $query = \App\Models\Terminal::withCountry();
        $userQuery = \App\Models\User::withCountry();
        $branchQuery = \App\Models\Branch::withCountry();
        $merchantQuery = \App\Models\Merchant::withCountry();
        
        // Get total counts across all merchants
        $totalTerminals = $query->count();
        $activeTerminals = (clone $query)->where('is_active', true)->count();
        $totalUsers = $userQuery->count();
        $totalBranches = $branchQuery->count();
        $totalMerchants = $merchantQuery->count();
        
        return [
            'totalTerminals' => $totalTerminals,
            'activeTerminals' => $activeTerminals,
            'totalUsers' => $totalUsers,
            'totalBranches' => $totalBranches,
            'totalMerchants' => $totalMerchants,
        ];
    }

    /**
     * Get admin transaction statistics by status (across all merchants)
     */
    public function getAdminTransactionStatistics(): array
    {
        $query = \App\Models\Transaction::withCountry();
        
        // Total transactions
        $totalTransactions = $query->count();
        
        // // Sale transactions (approved, pending, capture)
        // $saleTransactions = (clone $query)
        //     ->whereIn('status', ['approved', 'pending', 'capture'])
        //     ->count();
        
        // $saleTransactionsAmount = (clone $query)
        //     ->whereIn('status', ['approved', 'pending', 'capture'])
        //     ->sum('amount');
        
        // // Refund transactions
        // $refundTransactions = (clone $query)
        //     ->where('status', 'refunded')
        //     ->count();
        
        // $refundTransactionsAmount = (clone $query)
        //     ->where('status', 'refunded')
        //     ->sum('amount');
        
        // // Void transactions
        // $voidTransactions = (clone $query)
        //     ->where('status', 'voided')
        //     ->count();
        
        // $voidTransactionsAmount = (clone $query)
        //     ->where('status', 'voided')
        //     ->sum('amount');
        
       
            return [
            'totalTransactions' => $totalTransactions,
            // 'saleTransactions' => $saleTransactions,
            // 'saleTransactionsAmount' => $saleTransactionsAmount,
            // 'refundTransactions' => $refundTransactions,
            // 'refundTransactionsAmount' => $refundTransactionsAmount,
            // 'voidTransactions' => $voidTransactions,
            // 'voidTransactionsAmount' => $voidTransactionsAmount,
        ];
    }

    /**
     * Get admin latest transactions by status (across all merchants)
     */
    public function getAdminLatestTransactionsByStatus(): array
    {
        $query = \App\Models\Transaction::query()
            ->with(['terminal', 'user', 'merchant'])
            ->orderBy('created_at', 'desc');
        
        // Latest 5 sales (approved, pending, capture)
        $latestSales = (clone $query)
            ->whereIn('status', ['approved', 'pending', 'captured'])
            ->limit(5)
            ->get();
        
        // Latest 5 refunds
        $latestRefunds = (clone $query)
            ->where('status', 'refunded')
            ->limit(5)
            ->get();
        
        // Latest 5 voids
        $latestVoids = (clone $query)
            ->where('status', 'voided')
            ->limit(5)
            ->get();
        
        return [
            'latestSales' => $latestSales,
            'latestRefunds' => $latestRefunds,
            'latestVoids' => $latestVoids,
        ];
    }

    /**
     * Get admin transaction summary for different time periods (across all merchants)
     */
    public function getAdminTransactionSummary(): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();
        $thisYear = now()->startOfYear();
        
        // Today's transactions
        $todayStats = \App\Models\Transaction::where('created_at', '>=', $today)
            ->selectRaw('COUNT(*) as count, SUM(amount) as amount')
            ->first();
        
        // This week's transactions
        $thisWeekStats = \App\Models\Transaction::where('created_at', '>=', $thisWeek)
            ->selectRaw('COUNT(*) as count, SUM(amount) as amount')
            ->first();
        
        // This month's transactions
        $thisMonthStats = \App\Models\Transaction::where('created_at', '>=', $thisMonth)
            ->selectRaw('COUNT(*) as count, SUM(amount) as amount')
            ->first();
        
        // This year's transactions
        $thisYearStats = \App\Models\Transaction::where('created_at', '>=', $thisYear)
            ->selectRaw('COUNT(*) as count, SUM(amount) as amount')
            ->first();
        
        return [
            'today' => [
                'count' => $todayStats->count ?? 0,
                'amount' => $todayStats->amount ?? 0
            ],
            'this_week' => [
                'count' => $thisWeekStats->count ?? 0,
                'amount' => $thisWeekStats->amount ?? 0
            ],
            'this_month' => [
                'count' => $thisMonthStats->count ?? 0,
                'amount' => $thisMonthStats->amount ?? 0
            ],
            'this_year' => [
                'count' => $thisYearStats->count ?? 0,
                'amount' => $thisYearStats->amount ?? 0
            ]
        ];
    }

    /**
     * Get admin daily transaction statistics for the last 30 days (across all merchants)
     */
    public function getAdminDailyTransactionStats($days = 30): array
    {
        $stats = \App\Models\Transaction::selectRaw('
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->where('created_at', '>=', now()->subDays($days))
            ->withCountry()
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        $labels = [];
        $counts = [];
        $amounts = [];
        
        foreach ($stats as $stat) {
            $labels[] = $stat->date;
            $counts[] = $stat->count;
            $amounts[] = $stat->amount;
        }
        
        return [
            'labels' => $labels,
            'counts' => $counts,
            'amounts' => $amounts
        ];
    }

    /**
     * Get admin weekly transaction statistics for the last 12 weeks (across all merchants)
     */
    public function getAdminWeeklyTransactionStats($weeks = 12): array
    {
        $stats = \App\Models\Transaction::selectRaw('
                YEARWEEK(created_at) as week,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->where('created_at', '>=', now()->subWeeks($weeks))
            ->withCountry()
            ->groupBy('week')
            ->orderBy('week')
            ->get();
        
        $labels = [];
        $counts = [];
        $amounts = [];
        
        foreach ($stats as $stat) {
            $labels[] = 'Week ' . $stat->week;
            $counts[] = $stat->count;
            $amounts[] = $stat->amount;
        }
        
        return [
            'labels' => $labels,
            'counts' => $counts,
            'amounts' => $amounts
        ];
    }

    /**
     * Get admin monthly transaction statistics for the last 12 months (across all merchants)
     */
    public function getAdminMonthlyTransactionStats($months = 12): array
    {
        $stats = \App\Models\Transaction::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        $labels = [];
        $counts = [];
        $amounts = [];
        
        foreach ($stats as $stat) {
            $labels[] = date('M Y', strtotime($stat->month . '-01'));
            $counts[] = $stat->count;
            $amounts[] = $stat->amount;
        }
        
        return [
            'labels' => $labels,
            'counts' => $counts,
            'amounts' => $amounts
        ];
    }

    /**
     * Get admin transaction chart data for the dashboard (across all merchants)
     */
    public function getAdminTransactionChartData(): array
    {
        return [
            'daily' => $this->getAdminDailyTransactionStats(30),
            'weekly' => $this->getAdminWeeklyTransactionStats(12),
            'monthly' => $this->getAdminMonthlyTransactionStats(12)
        ];
    }

    // ... existing code ...

public function generateCustomTransactionChartWithStatus(string $merchantId, string $startDateTime, string $endDateTime, ?string $status = null): array
{
    $chartData = [];
    $chartLabels = [];
    
    // Define status filter
    $statusFilter = $status ? [$status] : ['approved', 'pending', 'captured'];
    
    // Parse the date range
    $startDate = Carbon::parse($startDateTime);
    $endDate = Carbon::parse($endDateTime);
    
    // Calculate the difference in days
    $totalDays = $startDate->diffInDays($endDate) + 1;
    
    // Generate data for each day in the range
    for ($i = 0; $i < $totalDays; $i++) {
        $currentDate = $startDate->copy()->addDays($i);
        $chartLabels[] = $currentDate->format('M d'); // Format as "Dec 15"
        
        $dailyAmount = Transaction::where('merchant_id', $merchantId)
            ->whereIn('status', $statusFilter)
            ->whereDate('created_at', $currentDate)
            ->sum('amount');
        
        $chartData[] = (float) $dailyAmount;
    }
    
    return [
        'labels' => $chartLabels,
        'amounts' => $chartData,
        'total_amount' => array_sum($chartData),
        'average_per_day' => $totalDays > 0 ? array_sum($chartData) / $totalDays : 0,
        'total_transactions' => Transaction::where('merchant_id', $merchantId)
            ->whereIn('status', $statusFilter)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count(),
        'date_range' => [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_days' => $totalDays
        ]
    ];
}

    /**
     * Calculate merchant profile completion percentage and missing requirements
     * 
     * @param Merchant $merchant
     * @return array
     */
    public function calculateProfileCompletion(Merchant $merchant): array
    {
        $completion = 10; // Default minimum
        $missingFields = []; // To store missing field messages
        $pointsPerItem = 18; // 90% divided by 5 main criteria

        // 1. Check if merchant has basic profile info
        $hasProfile = $merchant->name && $merchant->owner_name && $merchant->email && $merchant->phone && $merchant->address;
        if ($hasProfile) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Complete your business profile information.';
        }

        // 2. Check required documents
        $requiredDocuments = ['company_logo', 'user_id', 'tax_certified', 'trade_license'];
        $documentCount = $merchant->attachments()
            ->whereIn('type', $requiredDocuments)
            // ->where('path', '!=', '')
            ->count();
        
        if ($documentCount === count($requiredDocuments)) {
            $completion += $pointsPerItem;
        } else {
            $missingDocs = array_diff($requiredDocuments, 
                $merchant->attachments()->whereIn('type', $requiredDocuments)->pluck('type')->toArray());
            foreach ($missingDocs as $doc) {
                $missingFields[] = ucwords(str_replace('_', ' ', $doc)) . ' is required.';
            }
        }

        // 3. Check account approval status
        if ($merchant->status === 'approved') {
            $completion += $pointsPerItem;
        } else if ($merchant->status === 'rejected') {
            $missingFields[] = 'Account approval was rejected. Reason: ' . ($merchant->rejection_reason ?? 'Not specified');
        } else {
            $missingFields[] = 'Account is pending approval.';
        }

        // 4. Check if merchant has at least one user
        $hasUsers = $merchant->users()->count() > 0;
        if ($hasUsers) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Add at least one user to your account.';
        }

        // 5. Check if merchant has at least one terminal
        $hasTerminal = $merchant->terminals()->count() > 0;
        if ($hasTerminal) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Add at least one terminal to your account.';
        }

        return [
            'completion' => min(round($completion), 100), // Ensure max is 100%
            'missing' => $missingFields, // Return list of missing fields
            'status' => $merchant->status, // Include account status
            'documents' => [
                'total_required' => count($requiredDocuments),
                'uploaded' => $documentCount
            ],
            'users_count' => $merchant->users()->count(),
            'terminals_count' => $merchant->terminals()->count()
        ];
    }

    // ... existing code ...

    /**
     * Get admin terminal data by status for the dashboard (across all merchants)
     */
    public function getAdminTerminalDataByStatus(): array
    {
        $query = \App\Models\Terminal::query();
        
        // Get online terminals (terminals with online status)
        $onlineTerminals = (clone $query)->online()
            ->limit(10)
            ->get();
            
        // Get offline terminals (terminals with offline status)
        $offlineTerminals = (clone $query)->offline()
            ->limit(10)
            ->get();
            
        // Get testing terminals (terminals with testing status)
        $testingTerminals = (clone $query)->testing()
            ->limit(10)
            ->get();
        
        // Get terminal counts by status
        $onlineCount = (clone $query)->online()->count();
        $offlineCount = (clone $query)->offline()->count();
        $testingCount = (clone $query)->testing()->count();
        
        return [
            'onlineTerminals' => $onlineTerminals,
            'offlineTerminals' => $offlineTerminals,
            'testingTerminals' => $testingTerminals,
            'onlineCount' => $onlineCount,
            'offlineCount' => $offlineCount,
            'testingCount' => $testingCount,
        ];
    }

    /**
     * Get admin period statistics (daily, weekly, monthly)
     */
    public function getAdminPeriodStatistics(): array
    {
        return [
            'dailyStats' => $this->getAdminDailyTransactionStats(),
            'weeklyStats' => $this->getAdminWeeklyTransactionStats(),
            'monthlyStats' => $this->getAdminMonthlyTransactionStats(),
        ];
    }

    /**
     * Get top selling merchants based on transaction count and amount
     * 
     * @param int $limit Number of merchants to return
     * @param int $days Number of days to look back
     * @return array
     */
    public function getTopSellingMerchants($limit = 10, $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $topMerchants = Transaction::select(
                'merchant_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('merchant_id')
            ->orderBy('transaction_count', 'desc')
            ->limit($limit)
            ->with(['merchant' => function($query) {
                $query->select('id', 'name', 'business_type');
            }])
            ->get();

        $labels = [];
        $counts = [];
        $amounts = [];
        $merchants = [];

        foreach ($topMerchants as $transaction) {
            if ($transaction->merchant) {
                $merchant = $transaction->merchant;
                $labels[] = $merchant->name;
                $counts[] = $transaction->transaction_count;
                $amounts[] = (float) $transaction->total_amount;
                
                // Add merchant info
                $merchants[] = [
                    'id' => $merchant->id,
                    'name' => $merchant->name,
                    'business_type' => $merchant->business_type,
                    'stats' => [
                        'total_transactions' => $transaction->transaction_count,
                        'total_amount' => (float) $transaction->total_amount
                    ]
                ];
            }
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'amounts' => $amounts,
            'merchants' => $merchants,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => Carbon::now()->format('Y-m-d'),
                'days' => $days
            ]
        ];
    }

    /**
     * Get daily statistics for API with comprehensive transaction data
     */
    public function getDailyStatistics(string $merchantId, string $date): array
    {
        $startDate = Carbon::parse($date)->startOfDay();
        $endDate = Carbon::parse($date)->endOfDay();

        $transactions = Transaction::where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                status,
                COUNT(*) as count,
                COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_count,
                SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_count,
                SUM(CASE WHEN status = "cancelled" THEN amount ELSE 0 END) as cancelled_amount,
                COUNT(CASE WHEN status = "refunded" THEN 1 END) as refunded_count,
                SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as refunded_amount,
                COUNT(CASE WHEN status IN ("pending", "captured") THEN 1 END) as pending_count,
                SUM(CASE WHEN status IN ("pending", "captured") THEN amount ELSE 0 END) as pending_amount,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_count,
                SUM(CASE WHEN status = "failed" THEN amount ELSE 0 END) as failed_amount,
                COUNT(CASE WHEN status = "voided" THEN 1 END) as voided_count,
                SUM(CASE WHEN status = "voided" THEN voided_amount ELSE 0 END) as voided_amount
            ')
            ->groupBy('status')
            ->get();

        $summary = [
            'date' => $date,
            'total_transactions' => 0,
            'total_amount' => 0,
            'approved_count' => 0,
            'approved_amount' => 0,
            'cancelled_count' => 0,
            'cancelled_amount' => 0,
            'refunded_count' => 0,
            'refunded_amount' => 0,
            'pending_count' => 0,
            'pending_amount' => 0,
            'failed_count' => 0,
            'failed_amount' => 0,
            'voided_count' => 0,
            'voided_amount' => 0,
            'status_breakdown' => []
        ];

        foreach ($transactions as $transaction) {
            $summary['total_transactions'] += $transaction->count;
            $summary['approved_count'] += $transaction->approved_count;
            $summary['approved_amount'] += $transaction->approved_amount;
            $summary['cancelled_count'] += $transaction->cancelled_count;
            $summary['cancelled_amount'] += $transaction->cancelled_amount;
            $summary['refunded_count'] += $transaction->refunded_count;
            $summary['refunded_amount'] += $transaction->refunded_amount;
            $summary['pending_count'] += $transaction->pending_count;
            $summary['pending_amount'] += $transaction->pending_amount;
            $summary['failed_count'] += $transaction->failed_count;
            $summary['failed_amount'] += $transaction->failed_amount;
            $summary['voided_count'] += $transaction->voided_count;
            $summary['voided_amount'] += $transaction->voided_amount;

            // Combine captured status with pending in the breakdown
            if ($transaction->status === 'captured') {
                // Find existing pending entry or create new one
                $pendingIndex = array_search('pending', array_column($summary['status_breakdown'], 'status'));
                if ($pendingIndex !== false) {
                    $summary['status_breakdown'][$pendingIndex]['count'] += $transaction->count;
                    $summary['status_breakdown'][$pendingIndex]['amount'] += $this->getTransactionStatusGroupAmount($transaction);
                } else {
                    $summary['status_breakdown'][] = [
                        'status' => 'pending',
                        'count' => $transaction->count,
                        'amount' => $this->getTransactionStatusGroupAmount($transaction)
                    ];
                }
            } else {
                $summary['status_breakdown'][] = [
                    'status' => $transaction->status,
                    'count' => $transaction->count,
                    'amount' => $this->getTransactionStatusGroupAmount($transaction)
                ];
            }
        }

        $summary['total_amount'] = $this->calculateNetSalesTotal($summary);

        // Add chart data for the day (hourly breakdown)
        $hourlyData = Transaction::where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                HOUR(created_at) as hour,
                COUNT(*) as count,
                (SUM(CASE WHEN status IN ("pending", "captured") THEN amount ELSE 0 END) + 
                 SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END)) - 
                SUM(CASE WHEN status = "voided" THEN voided_amount ELSE 0 END) as total_amount
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hourlyChart = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourData = $hourlyData->where('hour', $hour)->first();
            $hourlyChart[] = [
                'hour' => sprintf('%02d:00', $hour),
                'count' => $hourData ? $hourData->count : 0,
                'amount' => $hourData ? (float) $hourData->total_amount : 0
            ];
        }

        $summary['hourly_chart'] = $hourlyChart;

        return $summary;
    }

    /**
     * Get weekly statistics for API with comprehensive transaction data
     */
    public function getWeeklyStatistics(string $merchantId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $transactions = Transaction::where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                status,
                COUNT(*) as count,
                COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_count,
                SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_count,
                SUM(CASE WHEN status = "cancelled" THEN amount ELSE 0 END) as cancelled_amount,
                COUNT(CASE WHEN status = "refunded" THEN 1 END) as refunded_count,
                SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as refunded_amount,
                COUNT(CASE WHEN status IN ("pending", "captured") THEN 1 END) as pending_count,
                SUM(CASE WHEN status IN ("pending", "captured") THEN amount ELSE 0 END) as pending_amount,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_count,
                SUM(CASE WHEN status = "failed" THEN amount ELSE 0 END) as failed_amount,
                COUNT(CASE WHEN status = "voided" THEN 1 END) as voided_count,
                SUM(CASE WHEN status = "voided" THEN voided_amount ELSE 0 END) as voided_amount
            ')
            ->groupBy('status')
            ->get();

            // dd($transactions);
        $summary = [
            'period' => $startDate . ' to ' . $endDate,
            'total_transactions' => 0,
            'total_amount' => 0,
            'approved_count' => 0,
            'approved_amount' => 0,
            'cancelled_count' => 0,
            'cancelled_amount' => 0,
            'refunded_count' => 0,
            'refunded_amount' => 0,
            'pending_count' => 0,
            'pending_amount' => 0,
            'failed_count' => 0,
            'failed_amount' => 0,
            'voided_count' => 0,
            'voided_amount' => 0,
            'status_breakdown' => [],
            'daily_breakdown' => []
        ];

        foreach ($transactions as $transaction) {
            $summary['total_transactions'] += $transaction->count;
            $summary['approved_count'] += $transaction->approved_count;
            $summary['approved_amount'] += $transaction->approved_amount;
            $summary['cancelled_count'] += $transaction->cancelled_count;
            $summary['cancelled_amount'] += $transaction->cancelled_amount;
            $summary['refunded_count'] += $transaction->refunded_count;
            $summary['refunded_amount'] += $transaction->refunded_amount;
            $summary['pending_count'] += $transaction->pending_count;
            $summary['pending_amount'] += $transaction->pending_amount;
            $summary['failed_count'] += $transaction->failed_count;
            $summary['failed_amount'] += $transaction->failed_amount;
            $summary['voided_count'] += $transaction->voided_count;
            $summary['voided_amount'] += $transaction->voided_amount;

            // Combine captured status with pending in the breakdown
            if ($transaction->status === 'captured') {
                // Find existing pending entry or create new one
                $pendingIndex = array_search('pending', array_column($summary['status_breakdown'], 'status'));
                if ($pendingIndex !== false) {
                    $summary['status_breakdown'][$pendingIndex]['count'] += $transaction->count;
                    $summary['status_breakdown'][$pendingIndex]['amount'] += $this->getTransactionStatusGroupAmount($transaction);
                } else {
                    $summary['status_breakdown'][] = [
                        'status' => 'pending',
                        'count' => $transaction->count,
                        'amount' => $this->getTransactionStatusGroupAmount($transaction)
                    ];
                }
            } else {
                $summary['status_breakdown'][] = [
                    'status' => $transaction->status,
                    'count' => $transaction->count,
                    'amount' => $this->getTransactionStatusGroupAmount($transaction)
                ];
            }
        }

        $summary['total_amount'] = $this->calculateNetSalesTotal($summary);

        // Add daily breakdown for the week
        $dailyData = Transaction::where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $currentDate = $start->copy();
        while ($currentDate <= $end) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayData = $dailyData->where('date', $dateStr)->first();
            
            $summary['daily_breakdown'][] = [
                'date' => $dateStr,
                'day_name' => $currentDate->format('D'),
                'count' => $dayData ? $dayData->count : 0,
                'amount' => $dayData ? (float) $dayData->amount : 0
            ];
            
            $currentDate->addDay();
        }

        return $summary;
    }

    /**
     * Get monthly statistics for API with comprehensive transaction data
     */
    public function getMonthlyStatistics(string $merchantId, string $month): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $transactions = Transaction::where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                status,
                COUNT(*) as count,
                COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_count,
                SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_count,
                SUM(CASE WHEN status = "cancelled" THEN amount ELSE 0 END) as cancelled_amount,
                COUNT(CASE WHEN status = "refunded" THEN 1 END) as refunded_count,
                SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as refunded_amount,
                COUNT(CASE WHEN status IN ("pending", "captured") THEN 1 END) as pending_count,
                SUM(CASE WHEN status IN ("pending", "captured") THEN amount ELSE 0 END) as pending_amount,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_count,
                SUM(CASE WHEN status = "failed" THEN amount ELSE 0 END) as failed_amount,
                COUNT(CASE WHEN status = "voided" THEN 1 END) as voided_count,
                SUM(CASE WHEN status = "voided" THEN voided_amount ELSE 0 END) as voided_amount
            ')
            ->groupBy('status')
            ->get();

        $summary = [
            'month' => $month,
            'month_name' => $start->format('F Y'),
            'total_transactions' => 0,
            'total_amount' => 0,
            'approved_count' => 0,
            'approved_amount' => 0,
            'cancelled_count' => 0,
            'cancelled_amount' => 0,
            'refunded_count' => 0,
            'refunded_amount' => 0,
            'pending_count' => 0,
            'pending_amount' => 0,
            'failed_count' => 0,
            'failed_amount' => 0,
            'voided_count' => 0,
            'voided_amount' => 0,
            'status_breakdown' => [],
            'weekly_breakdown' => []
        ];

        foreach ($transactions as $transaction) {
            $summary['total_transactions'] += $transaction->count;
            $summary['approved_count'] += $transaction->approved_count;
            $summary['approved_amount'] += $transaction->approved_amount;
            $summary['cancelled_count'] += $transaction->cancelled_count;
            $summary['cancelled_amount'] += $transaction->cancelled_amount;
            $summary['refunded_count'] += $transaction->refunded_count;
            $summary['refunded_amount'] += $transaction->refunded_amount;
            $summary['pending_count'] += $transaction->pending_count;
            $summary['pending_amount'] += $transaction->pending_amount;
            $summary['failed_count'] += $transaction->failed_count;
            $summary['failed_amount'] += $transaction->failed_amount;
            $summary['voided_count'] += $transaction->voided_count;
            $summary['voided_amount'] += $transaction->voided_amount;

            // Combine captured status with pending in the breakdown
            if ($transaction->status === 'captured') {
                // Find existing pending entry or create new one
                $pendingIndex = array_search('pending', array_column($summary['status_breakdown'], 'status'));
                if ($pendingIndex !== false) {
                    $summary['status_breakdown'][$pendingIndex]['count'] += $transaction->count;
                    $summary['status_breakdown'][$pendingIndex]['amount'] += $this->getTransactionStatusGroupAmount($transaction);
                } else {
                    $summary['status_breakdown'][] = [
                        'status' => 'pending',
                        'count' => $transaction->count,
                        'amount' => $this->getTransactionStatusGroupAmount($transaction)
                    ];
                }
            } else {
                $summary['status_breakdown'][] = [
                    'status' => $transaction->status,
                    'count' => $transaction->count,
                    'amount' => $this->getTransactionStatusGroupAmount($transaction)
                ];
            }
        }

        $summary['total_amount'] = $this->calculateNetSalesTotal($summary);

        // Add weekly breakdown for the month
        $weeklyData = Transaction::where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                YEARWEEK(created_at) as week,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        $currentWeek = $start->copy()->startOfWeek();
        while ($currentWeek <= $end) {
            $weekKey = $currentWeek->format('Y-W');
            $weekData = $weeklyData->where('week', $weekKey)->first();
            
            $summary['weekly_breakdown'][] = [
                'week' => $weekKey,
                'week_start' => $currentWeek->format('Y-m-d'),
                'week_end' => $currentWeek->copy()->endOfWeek()->format('Y-m-d'),
                'count' => $weekData ? $weekData->count : 0,
                'amount' => $weekData ? (float) $weekData->amount : 0
            ];
            
            $currentWeek->addWeek();
        }

        return $summary;
    }

    /**
     * Get comprehensive transaction summary for API with detailed breakdowns
     */
    public function getComprehensiveTransactionSummary(string $merchantId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $transactions = Transaction::where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_count,
                SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_count,
                SUM(CASE WHEN status = "cancelled" THEN amount ELSE 0 END) as cancelled_amount,
                COUNT(CASE WHEN status = "refunded" THEN 1 END) as refunded_count,
                SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as refunded_amount,
                COUNT(CASE WHEN status IN ("pending", "captured") THEN 1 END) as pending_count,
                SUM(CASE WHEN status IN ("pending", "captured") THEN amount ELSE 0 END) as pending_amount,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_count,
                SUM(CASE WHEN status = "failed" THEN amount ELSE 0 END) as failed_amount
            ')
            ->groupBy('status')
            ->get();

        $summary = [
            'period' => $startDate . ' to ' . $endDate,
            'total_transactions' => 0,
            'total_amount' => 0,
            'approved_count' => 0,
            'approved_amount' => 0,
            'cancelled_count' => 0,
            'cancelled_amount' => 0,
            'refunded_count' => 0,
            'refunded_amount' => 0,
            'pending_count' => 0,
            'pending_amount' => 0,
            'failed_count' => 0,
            'failed_amount' => 0,
            'status_breakdown' => [],
            'daily_breakdown' => [],
            'status_chart' => [],
            'amount_chart' => []
        ];

        foreach ($transactions as $transaction) {
            $summary['total_transactions'] += $transaction->count;
            $summary['approved_count'] += $transaction->approved_count;
            $summary['approved_amount'] += $transaction->approved_amount;
            $summary['cancelled_count'] += $transaction->cancelled_count;
            $summary['cancelled_amount'] += $transaction->cancelled_amount;
            $summary['refunded_count'] += $transaction->refunded_count;
            $summary['refunded_amount'] += $transaction->refunded_amount;
            $summary['pending_count'] += $transaction->pending_count;
            $summary['pending_amount'] += $transaction->pending_amount;
            $summary['failed_count'] += $transaction->failed_count;
            $summary['failed_amount'] += $transaction->failed_amount;

            // Combine captured status with pending in the breakdown
            if ($transaction->status === 'captured') {
                // Find existing pending entry or create new one
                $pendingIndex = array_search('pending', array_column($summary['status_breakdown'], 'status'));
                if ($pendingIndex !== false) {
                    $summary['status_breakdown'][$pendingIndex]['count'] += $transaction->count;
                    $summary['status_breakdown'][$pendingIndex]['amount'] += $this->getTransactionStatusGroupAmount($transaction);
                } else {
                    $summary['status_breakdown'][] = [
                        'status' => 'pending',
                        'count' => $transaction->count,
                        'amount' => $this->getTransactionStatusGroupAmount($transaction)
                    ];
                }
            } else {
                $summary['status_breakdown'][] = [
                    'status' => $transaction->status,
                    'count' => $transaction->count,
                    'amount' => $this->getTransactionStatusGroupAmount($transaction)
                ];
            }
        }

        $summary['total_amount'] = $this->calculateNetSalesTotal($summary);

        // Add daily breakdown
        $dailyData = Transaction::where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $currentDate = $start->copy();
        while ($currentDate <= $end) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayData = $dailyData->where('date', $dateStr)->first();
            
            $summary['daily_breakdown'][] = [
                'date' => $dateStr,
                'day_name' => $currentDate->format('D'),
                'count' => $dayData ? $dayData->count : 0,
                'amount' => $dayData ? (float) $dayData->amount : 0
            ];
            
            $currentDate->addDay();
        }

        // Add chart data for status distribution
        $summary['status_chart'] = [
            'labels' => ['Approved', 'Cancelled', 'Refunded', 'Pending', 'Failed'],
            'counts' => [
                $summary['approved_count'],
                $summary['cancelled_count'],
                $summary['refunded_count'],
                $summary['pending_count'],
                $summary['failed_count']
            ],
            'amounts' => [
                $summary['approved_amount'],
                $summary['cancelled_amount'],
                $summary['refunded_amount'],
                $summary['pending_amount'],
                $summary['failed_amount']
            ]
        ];

        // Add chart data for amount trends
        $summary['amount_chart'] = [
            'labels' => array_column($summary['daily_breakdown'], 'day_name'),
            'amounts' => array_column($summary['daily_breakdown'], 'amount'),
            'counts' => array_column($summary['daily_breakdown'], 'count')
        ];

        return $summary;
    }

    /**
     * Get custom statistics for API with specific date and time range
     */
    public function getCustomStatistics(string $merchantId, Carbon $startDateTime, Carbon $endDateTime): array
    {
        $transactions = Transaction::where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->selectRaw('
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_count,
                SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_count,
                SUM(CASE WHEN status = "cancelled" THEN amount ELSE 0 END) as cancelled_amount,
                COUNT(CASE WHEN status = "refunded" THEN 1 END) as refunded_count,
                SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as refunded_amount,
                COUNT(CASE WHEN status IN ("pending", "captured") THEN 1 END) as pending_count,
                SUM(CASE WHEN status IN ("pending", "captured") THEN amount ELSE 0 END) as pending_amount,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_count,
                SUM(CASE WHEN status = "failed" THEN amount ELSE 0 END) as failed_amount
            ')
            ->groupBy('status')
            ->get();

        $summary = [
            'period' => $startDateTime->format('Y-m-d H:i') . ' to ' . $endDateTime->format('Y-m-d H:i'),
            'start_datetime' => $startDateTime->format('Y-m-d H:i:s'),
            'end_datetime' => $endDateTime->format('Y-m-d H:i:s'),
            'total_transactions' => 0,
            'total_amount' => 0,
            'approved_count' => 0,
            'approved_amount' => 0,
            'cancelled_count' => 0,
            'cancelled_amount' => 0,
            'refunded_count' => 0,
            'refunded_amount' => 0,
            'pending_count' => 0,
            'pending_amount' => 0,
            'failed_count' => 0,
            'failed_amount' => 0,
            'status_breakdown' => [],
            'hourly_breakdown' => [],
            'daily_breakdown' => [],
            'status_chart' => [],
            'amount_chart' => []
        ];

        foreach ($transactions as $transaction) {
            $summary['total_transactions'] += $transaction->count;
            $summary['approved_count'] += $transaction->approved_count;
            $summary['approved_amount'] += $transaction->approved_amount;
            $summary['cancelled_count'] += $transaction->cancelled_count;
            $summary['cancelled_amount'] += $transaction->cancelled_amount;
            $summary['refunded_count'] += $transaction->refunded_count;
            $summary['refunded_amount'] += $transaction->refunded_amount;
            $summary['pending_count'] += $transaction->pending_count;
            $summary['pending_amount'] += $transaction->pending_amount;
            $summary['failed_count'] += $transaction->failed_count;
            $summary['failed_amount'] += $transaction->failed_amount;

            // Combine captured status with pending in the breakdown
            if ($transaction->status === 'captured') {
                // Find existing pending entry or create new one
                $pendingIndex = array_search('pending', array_column($summary['status_breakdown'], 'status'));
                if ($pendingIndex !== false) {
                    $summary['status_breakdown'][$pendingIndex]['count'] += $transaction->count;
                    $summary['status_breakdown'][$pendingIndex]['amount'] += $this->getTransactionStatusGroupAmount($transaction);
                } else {
                    $summary['status_breakdown'][] = [
                        'status' => 'pending',
                        'count' => $transaction->count,
                        'amount' => $this->getTransactionStatusGroupAmount($transaction)
                    ];
                }
            } else {
                $summary['status_breakdown'][] = [
                    'status' => $transaction->status,
                    'count' => $transaction->count,
                    'amount' => $this->getTransactionStatusGroupAmount($transaction)
                ];
            }
        }

        $summary['total_amount'] = $this->calculateNetSalesTotal($summary);

        // Add hourly breakdown for the custom period
        $hourlyData = Transaction::where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->selectRaw('
                DATE(created_at) as date,
                HOUR(created_at) as hour,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->groupBy('date', 'hour')
            ->orderBy('date')
            ->orderBy('hour')
            ->get();

        $currentDateTime = $startDateTime->copy();
        while ($currentDateTime <= $endDateTime) {
            $dateStr = $currentDateTime->format('Y-m-d');
            $hour = $currentDateTime->format('H');
            $hourData = $hourlyData->where('date', $dateStr)->where('hour', $hour)->first();
            
            $summary['hourly_breakdown'][] = [
                'datetime' => $currentDateTime->format('Y-m-d H:i'),
                'date' => $dateStr,
                'hour' => $hour,
                'count' => $hourData ? $hourData->count : 0,
                'amount' => $hourData ? (float) $hourData->amount : 0
            ];
            
            $currentDateTime->addHour();
        }

        // Add daily breakdown
        $dailyData = Transaction::where('merchant_id', $merchantId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(amount) as amount
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $currentDate = $startDateTime->copy()->startOfDay();
        while ($currentDate <= $endDateTime) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayData = $dailyData->where('date', $dateStr)->first();
            
            $summary['daily_breakdown'][] = [
                'date' => $dateStr,
                'day_name' => $currentDate->format('D'),
                'count' => $dayData ? $dayData->count : 0,
                'amount' => $dayData ? (float) $dayData->amount : 0
            ];
            
            $currentDate->addDay();
        }

        // Add chart data for status distribution
        $summary['status_chart'] = [
            'labels' => ['Approved', 'Cancelled', 'Refunded', 'Pending', 'Failed'],
            'counts' => [
                $summary['approved_count'],
                $summary['cancelled_count'],
                $summary['refunded_count'],
                $summary['pending_count'],
                $summary['failed_count']
            ],
            'amounts' => [
                $summary['approved_amount'],
                $summary['cancelled_amount'],
                $summary['refunded_amount'],
                $summary['pending_amount'],
                $summary['failed_amount']
            ]
        ];

        // Add chart data for amount trends
        $summary['amount_chart'] = [
            'labels' => array_column($summary['daily_breakdown'], 'day_name'),
            'amounts' => array_column($summary['daily_breakdown'], 'amount'),
            'counts' => array_column($summary['daily_breakdown'], 'count')
        ];

        return $summary;
    }

    // ============================================
    // V2 METHODS - PAYMENT & TRANSACTION FOCUSED
    // No queries to Terminal, User, Branch models
    // ============================================

    /**
     * V2: Get dashboard statistics focusing ONLY on payments & transactions
     * Returns 0 for terminal, user, branch counts (moved to AuthService)
     */
    public function getDashboardStatisticsV2(?string $merchantId = null): array
    {
        return [
            'totalTerminals' => 0,
            'activeTerminals' => 0,
            'totalUsers' => 0,
            'totalBranches' => 0,
            'inactiveTerminals' => 0,
            'onlineTerminals' => 0,
            'offlineTerminals' => 0,
            'merchant' => $merchantId,
        ];
    }

    /**
     * V2: Get terminal data - Returns 0/empty arrays (terminals moved to AuthService)
     */
    public function getTerminalDataByStatusV2(?string $merchantId = null): array
    {
        return [
            'onlineTerminals' => [],
            'offlineTerminals' => [],
            'testingTerminals' => [],
            'totalTerminals' => 0,
            'onlineCount' => 0,
            'offlineCount' => 0,
            'testingCount' => 0,
            'activeTerminals' => 0,
            'inactiveTerminals' => 0,
        ];
    }

    /**
     * V2: Get comprehensive dashboard data for API
     * Focuses ONLY on transaction/payment data
     */
    public function getDashboardDataForApiV2(string $merchantId): array
    {
        $basicStats = $this->getDashboardStatisticsV2($merchantId);
        $transactionStats = $this->getTransactionStatisticsByStatus($merchantId);
        $latestTransactions = $this->getLatestTransactionsByStatus($merchantId);
        
        return array_merge($basicStats, $transactionStats, $latestTransactions);
    }

    /**
     * V2: Generate transaction chart data (same as V1, already focused on transactions)
     */
    public function generateTransactionChartDataV2(string $merchantId, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        return $this->generateTransactionChartData($merchantId, $datetimeFrom, $datetimeTo, $status);
    }

    /**
     * V2: Get transaction summary (same as V1, already focused on transactions)
     */
    public function getTransactionSummaryV2(string $merchantId, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        return $this->getTransactionSummary($merchantId, $datetimeFrom, $datetimeTo, $status);
    }

    /**
     * V2: Get daily transaction stats (same as V1, already focused on transactions)
     */
    public function getDailyTransactionStatsV2(string $merchantId, int $days = 30, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        return $this->getDailyTransactionStats($merchantId, $days, $datetimeFrom, $datetimeTo, $status);
    }

    /**
     * V2: Get weekly transaction stats (same as V1, already focused on transactions)
     */
    public function getWeeklyTransactionStatsV2(string $merchantId, int $weeks = 12, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        return $this->getWeeklyTransactionStats($merchantId, $weeks, $datetimeFrom, $datetimeTo, $status);
    }

    /**
     * V2: Get monthly transaction stats (same as V1, already focused on transactions)
     */
    public function getMonthlyTransactionStatsV2(string $merchantId, int $months = 12, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        return $this->getMonthlyTransactionStats($merchantId, $months, $datetimeFrom, $datetimeTo, $status);
    }

    /**
     * V2: Get transaction statistics by status (same as V1, already focused on transactions)
     */
    public function getTransactionStatisticsByStatusV2(?string $merchantId = null, ?string $datetimeFrom = null, ?string $datetimeTo = null, ?string $status = null): array
    {
        return $this->getTransactionStatisticsByStatus($merchantId, $datetimeFrom, $datetimeTo, $status);
    }

    /**
     * V2: Get daily statistics (same as V1, already focused on transactions)
     */
    public function getDailyStatisticsV2(string $merchantId, string $date): array
    {
        return $this->getDailyStatistics($merchantId, $date);
    }

    /**
     * V2: Get weekly statistics (same as V1, already focused on transactions)
     */
    public function getWeeklyStatisticsV2(string $merchantId, string $weekStart, string $weekEnd): array
    {
        return $this->getWeeklyStatistics($merchantId, $weekStart, $weekEnd);
    }

    /**
     * V2: Get monthly statistics (same as V1, already focused on transactions)
     */
    public function getMonthlyStatisticsV2(string $merchantId, string $month): array
    {
        return $this->getMonthlyStatistics($merchantId, $month);
    }

    /**
     * V2: Get comprehensive transaction summary (same as V1, already focused on transactions)
     */
    public function getComprehensiveTransactionSummaryV2(string $merchantId, string $startDate, string $endDate): array
    {
        return $this->getComprehensiveTransactionSummary($merchantId, $startDate, $endDate);
    }

    /**
     * V2: Get custom statistics (same as V1, already focused on transactions)
     */
    public function getCustomStatisticsV2(string $merchantId, Carbon $startDateTime, Carbon $endDateTime): array
    {
        return $this->getCustomStatistics($merchantId, $startDateTime, $endDateTime);
    }

    /**
     * V2: Generate daily transaction chart data (same as V1, already focused on transactions)
     */
    public function generateDailyTransactionChartDataV2(string $merchantId, int $days = 20): array
    {
        return $this->generateDailyTransactionChartData($merchantId, $days);
    }

    /**
     * V2: Generate weekly transaction chart data (same as V1, already focused on transactions)
     */
    public function generateWeeklyTransactionChartDataV2(string $merchantId, int $weeks = 12): array
    {
        return $this->generateWeeklyTransactionChartData($merchantId, $weeks);
    }

    /**
     * V2: Generate monthly transaction chart data (same as V1, already focused on transactions)
     */
    public function generateMonthlyTransactionChartDataV2(string $merchantId, int $months = 12): array
    {
        return $this->generateMonthlyTransactionChartData($merchantId, $months);
    }

    /**
     * V2: Generate hourly transaction chart with status (same as V1, already focused on transactions)
     */
    public function generateHourlyTransactionChartWithStatusV2(string $merchantId, ?string $status = null): array
    {
        return $this->generateHourlyTransactionChartWithStatus($merchantId, $status);
    }

    /**
     * V2: Generate weekly transaction chart with status (same as V1, already focused on transactions)
     */
    public function generateWeeklyTransactionChartWithStatusV2(string $merchantId, int $weeks = 12, ?string $status = null): array
    {
        return $this->generateWeeklyTransactionChartWithStatus($merchantId, $weeks, $status);
    }

    /**
     * V2: Generate monthly transaction chart with status (same as V1, already focused on transactions)
     */
    public function generateMonthlyTransactionChartWithStatusV2(string $merchantId, ?string $status = null): array
    {
        return $this->generateMonthlyTransactionChartWithStatus($merchantId, $status);
    }

    /**
     * V2: Generate custom transaction chart with status (same as V1, already focused on transactions)
     */
    public function generateCustomTransactionChartWithStatusV2(string $merchantId, Carbon $startDateTime, Carbon $endDateTime, ?string $status = null): array
    {
        return $this->generateCustomTransactionChartWithStatus($merchantId, $startDateTime, $endDateTime, $status);
    }

    /**
     * Net sales total: pending + approved - voided.
     * Refunds are already reflected in the reduced amount on the original sale row.
     */
    private function calculateNetSalesTotal(array $summary): float
    {
        return round(
            ($summary['pending_amount'] ?? 0)
            + ($summary['approved_amount'] ?? 0)
            - ($summary['voided_amount'] ?? 0),
            2
        );
    }

    /**
     * Amount for one grouped status row in statistics breakdowns.
     */
    private function getTransactionStatusGroupAmount(object $transaction): float
    {
        return (float) (
            ($transaction->pending_amount ?? 0)
            + ($transaction->approved_amount ?? 0)
            + ($transaction->voided_amount ?? 0)
            + ($transaction->refunded_amount ?? 0)
            + ($transaction->cancelled_amount ?? 0)
            + ($transaction->failed_amount ?? 0)
        );
    }
} 