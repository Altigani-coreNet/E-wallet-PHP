<?php

namespace App\Services;

use App\Mail\CustomerApprovalMail;
use App\Mail\CustomerRejectionMail;
use App\Models\Customer;
use App\Models\CustomerRejection;
use App\Models\Merchant;
use App\Support\CustomerEventMessageBuilder;
use App\Modules\AdminKyc\Notifications\AdminKycNotificationType;
use App\Modules\AdminKyc\Services\AdminKycNotificationService;
use App\Modules\AdminKyc\Services\AdminKycQueueService;
use App\Modules\CustomerAuth\Notifications\CustomerNotificationType;
use App\Modules\CustomerAuth\Services\CustomerAttachmentService;
use App\Modules\CustomerAuth\Services\CustomerSystemNotificationService;
use App\Repositories\CustomerRepository;
use App\Support\CsvExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly WalletService $walletService,
        private readonly CustomerSystemNotificationService $customerSystemNotificationService,
        private readonly AdminKycQueueService $adminKycQueueService,
    ) {
    }

    public function listForMerchant(int $merchantId, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->customerRepository->paginateByMerchant($merchantId, $perPage, $search);
    }

    public function showForMerchant(int $merchantId, int $customerId): array
    {
        $customer = $this->customerRepository->findByIdAndMerchant($customerId, $merchantId);
        if (!$customer) {
            throw ValidationException::withMessages(['customer' => 'Customer not found']);
        }

        $lastPaymentLink = $this->customerRepository->latestPaymentLinkForCustomer($merchantId, $customer->id);

        return [
            'customer' => $customer,
            'last_payment_link' => $lastPaymentLink,
        ];
    }

    /**
     * Create customer for merchant (used by merchant controllers)
     */
    public function createForMerchant(int $merchantId, array $data): Customer
    {
        if ($this->customerRepository->emailExistsForMerchant($data['email'], $merchantId)) {
            throw ValidationException::withMessages(['email' => 'Customer with this email already exists']);
        }

        $data['merchant_id'] = $merchantId;
        $data['merchant_country_id'] = $this->getMerchantCountryId($merchantId);

        return DB::transaction(function () use ($data) {
            $customer = $this->customerRepository->create($data);
            $this->walletService->createForCustomer($customer);

            return $customer;
        });
    }

    /**
     * Create customer (used by admin - merchant_id optional)
     */
    public function create(array $data): Customer
    {
        if ($this->customerRepository->emailExistsGlobally($data['email'])) {
            throw ValidationException::withMessages(['email' => 'Customer with this email already exists']);
        }

        $merchantId = $data['merchant_id'] ?? null;
        if ($merchantId) {
            $data['merchant_country_id'] = $this->getMerchantCountryId((int) $merchantId);
        } else {
            unset($data['merchant_id']);
            $data['merchant_country_id'] = $data['merchant_country_id'] ?? null;
        }

        $data['status'] = $data['status'] ?? Customer::STATUS_PENDING;

        return DB::transaction(function () use ($data) {
            $customer = $this->customerRepository->create($data);
            $this->walletService->createForCustomer($customer);

            return $customer;
        });
    }

    /**
     * Update customer for merchant (used by merchant controllers)
     */
    public function updateForMerchant(int $merchantId, Customer $customer, array $data): Customer
    {
        if ($customer->merchant_id !== $merchantId) {
            throw ValidationException::withMessages(['customer' => 'Customer not found']);
        }

        if ($this->customerRepository->emailExistsForMerchant($data['email'], $merchantId, $customer->id)) {
            throw ValidationException::withMessages(['email' => 'Customer with this email already exists']);
        }

        $data['merchant_country_id'] = $this->getMerchantCountryId($merchantId);
        
        return $this->customerRepository->update($customer, $data);
    }

    /**
     * Update customer (used by admin - merchant_id optional)
     */
    public function update(Customer $customer, array $data): Customer
    {
        if ($this->customerRepository->emailExistsGlobally($data['email'], $customer->id)) {
            throw ValidationException::withMessages(['email' => 'Customer with this email already exists']);
        }

        if (array_key_exists('email', $data) && $data['email'] !== $customer->email) {
            $data['email_verified_at'] = null;
        }

        $merchantId = $data['merchant_id'] ?? null;
        if ($merchantId) {
            $data['merchant_country_id'] = $this->getMerchantCountryId((int) $merchantId);
        } else {
            $data['merchant_id'] = null;
            if (! array_key_exists('merchant_country_id', $data)) {
                $data['merchant_country_id'] = null;
            }
        }

        return $this->customerRepository->update($customer, $data);
    }

    /**
     * Approve a pending or rejected customer application.
     */
    public function approve(Customer $customer): array
    {
        if (! in_array($customer->status, Customer::APPROVABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Only pending or rejected customers can be approved.',
            ]);
        }

        return DB::transaction(function () use ($customer) {
            $customer->status = Customer::STATUS_ACTIVE;
            $customer->save();

            $fresh = $customer->fresh(['merchant', 'country', 'city', 'wallet']);
            $admin = $this->resolveCustomerLogActor();

            $this->logCustomerAction($fresh, 'approved', null, $fresh->getAttributes(), [
                'type' => 'approval',
                'event' => 'Admin approved customer profile',
                'message' => CustomerEventMessageBuilder::approved($fresh, $admin?->name),
            ]);

            $this->customerSystemNotificationService->send(
                $fresh,
                CustomerNotificationType::ProfileApproved,
            );

            $this->sendApprovalEmail($fresh);

            $this->adminKycQueueService->broadcast();

            return [
                'message' => 'Customer approved successfully',
                'customer' => $fresh,
            ];
        });
    }

    /**
     * Reject a customer application with reason and flagged fields.
     *
     * @param  list<string>  $invalidFields
     * @param  list<string>  $missingAttachments
     */
    public function reject(
        Customer $customer,
        string $rejectionReason,
        array $invalidFields = [],
        array $missingAttachments = [],
    ): array {
        if ($customer->status === Customer::STATUS_REJECTED) {
            throw ValidationException::withMessages([
                'status' => 'Customer is already rejected.',
            ]);
        }

        if (! in_array($customer->status, Customer::REJECTABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Only pending or requesting-updated customers can be rejected.',
            ]);
        }

        foreach ($missingAttachments as $attachment) {
            if (! in_array($attachment, CustomerAttachmentService::ALLOWED_MISSING_ATTACHMENTS, true)) {
                throw ValidationException::withMessages([
                    'missing_attachments' => 'Invalid attachment key: '.$attachment,
                ]);
            }
        }

        return DB::transaction(function () use ($customer, $rejectionReason, $invalidFields, $missingAttachments) {
            $customer->status = Customer::STATUS_REJECTED;
            $customer->save();

            $rejectedById = $this->resolveRejectionActorId();

            CustomerRejection::create([
                'customer_id' => $customer->id,
                'rejection_reason' => $rejectionReason,
                'invalid_fields' => $invalidFields,
                'missing_attachments' => $missingAttachments ?: null,
                'rejected_by' => $rejectedById,
            ]);

            $fresh = $customer->fresh(['merchant', 'country', 'city', 'wallet']);
            $admin = $this->resolveCustomerLogActor();

            $this->logCustomerAction($fresh, 'rejected', null, $fresh->getAttributes(), [
                'type' => 'rejection',
                'reason' => $rejectionReason,
                'invalid_fields' => $invalidFields,
                'missing_attachments' => $missingAttachments,
                'event' => 'Admin rejected customer profile',
                'message' => CustomerEventMessageBuilder::rejected(
                    $rejectionReason,
                    $invalidFields,
                    $missingAttachments,
                    $admin?->name,
                ),
            ]);

            $this->customerSystemNotificationService->send(
                $fresh,
                CustomerNotificationType::ProfileRejected,
                ['reason' => $rejectionReason],
            );

            $this->sendRejectionEmail($fresh, $rejectionReason);

            $this->adminKycQueueService->broadcast();

            return [
                'message' => 'Customer rejected successfully',
                'customer' => $fresh,
            ];
        });
    }

    public function logCustomerEvent(Customer $customer, string $action, array $metadata = [], $oldValues = null, $newValues = null): void
    {
        $this->logCustomerAction($customer, $action, $oldValues, $newValues, $metadata);
    }

    protected function logCustomerAction(Customer $customer, string $action, $oldValues = null, $newValues = null, array $metadata = []): void
    {
        $actor = $this->resolveCustomerLogActor();

        $metadata = array_merge([
            'timestamp' => now(),
            'performed_by' => $actor?->name ?? 'System',
        ], $metadata);

        $customer->logs()->create([
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'user_id' => $actor?->id,
            'user_type' => $actor ? get_class($actor) : null,
        ]);
    }

    protected function resolveCustomerLogActor(): mixed
    {
        if (Auth::guard('customer')->check()) {
            return Auth::guard('customer')->user();
        }

        try {
            $adminApiUser = Auth::guard('admin-api')->user();
            if ($adminApiUser) {
                return $adminApiUser;
            }
        } catch (\Throwable) {
            // Passport can throw on multipart customer requests that carry a customer JWT.
        }

        return Auth::guard('admin')->user();
    }

    /**
     * @throws \RuntimeException
     */
    protected function resolveRejectionActorId(): string
    {
        $guards = ['admin-api', 'admin', null];

        foreach ($guards as $guard) {
            try {
                $authGuard = $guard ? Auth::guard($guard) : Auth::guard();
            } catch (\Throwable) {
                continue;
            }

            if ($authGuard->check()) {
                $id = $authGuard->id();
                if (! empty($id)) {
                    return (string) $id;
                }
            }
        }

        throw new \RuntimeException('Unable to determine the rejecting user.');
    }

    protected function sendApprovalEmail(Customer $customer): void
    {
        if (! $customer->email || ! class_exists(CustomerApprovalMail::class)) {
            return;
        }

        try {
            Mail::to($customer->email)->send(new CustomerApprovalMail($customer));
        } catch (\Throwable $e) {
            Log::warning('Failed to send customer approval email: '.$e->getMessage());
        }
    }

    protected function sendRejectionEmail(Customer $customer, string $reason): void
    {
        if (! $customer->email || ! class_exists(CustomerRejectionMail::class)) {
            return;
        }

        try {
            Mail::to($customer->email)->send(new CustomerRejectionMail($customer, $reason));
        } catch (\Throwable $e) {
            Log::warning('Failed to send customer rejection email: '.$e->getMessage());
        }
    }

    /**
     * Update customer account status (pending, active, suspended, inactive).
     */
    public function updateStatus(Customer $customer, string $status): Customer
    {
        if (! in_array($status, Customer::MANAGEABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid customer status.',
            ]);
        }

        $customer->status = $status;
        $customer->save();

        return $customer->fresh(['merchant', 'country', 'city']);
    }

    /**
     * @deprecated Use updateStatus() instead.
     */
    public function toggleStatus(Customer $customer): Customer
    {
        $nextStatus = $customer->isActive() ? Customer::STATUS_SUSPENDED : Customer::STATUS_ACTIVE;

        return $this->updateStatus($customer, $nextStatus);
    }

    /**
     * Delete customer for merchant (used by merchant controllers)
     */
    public function deleteForMerchant(int $merchantId, Customer $customer): void
    {
        if ($customer->merchant_id !== $merchantId) {
            throw ValidationException::withMessages(['customer' => 'Customer not found']);
        }

        $this->softDeleteWithCorruption($customer);
    }

    /**
     * Delete customer (used by admin)
     */
    public function delete(Customer $customer): void
    {
        $this->softDeleteWithCorruption($customer);
    }

    /**
     * Soft-delete customers by id with phone/email corruption to free unique constraints.
     */
    public function bulkDelete(array $ids): int
    {
        $deletedCount = 0;

        Customer::query()
            ->whereIn('id', $ids)
            ->get()
            ->each(function (Customer $customer) use (&$deletedCount) {
                $this->softDeleteWithCorruption($customer);
                $deletedCount++;
            });

        return $deletedCount;
    }

    /**
     * Corrupt unique identifiers, then soft-delete the customer inside a transaction.
     */
    public function softDeleteWithCorruption(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            $id = $customer->id;

            $customer->phone = $this->corruptUniqueValue($customer->phone, $id);

            if ($customer->email) {
                $customer->email = $this->corruptUniqueValue($customer->email, $id);
            }

            $customer->status = Customer::STATUS_DELETED;
            $customer->save();
            $customer->delete();
        });
    }

    private function corruptUniqueValue(string $value, int|string $id, int $maxLength = 255): string
    {
        $corrupted = "deleted_{$id}_{$value}";

        if (strlen($corrupted) > $maxLength) {
            return substr($corrupted, 0, $maxLength);
        }

        return $corrupted;
    }

    /**
     * Get merchant country ID
     */
    private function getMerchantCountryId(int $merchantId): ?int
    {
        $merchant = Merchant::select('country_id')->find($merchantId);
        return $merchant?->country_id;
    }

    /**
     * Export template for customers import
     */
    public function exportTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers_import_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'Name*',
                'Email*',
                'Phone',
                'Address',
                'State',
                'Zip',
            ]);

            fputcsv($file, [
                'John Doe',
                'john.doe@example.com',
                CsvExport::asText('+1234567890'),
                '123 Main Street',
                'California',
                '90001',
            ]);

            fputcsv($file, [
                'Jane Smith',
                'jane.smith@example.com',
                CsvExport::asText('+1234567891'),
                '456 Oak Avenue',
                'Ontario',
                'M5H 2N2',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Preview customers import data
     */
    public function importPreview($file, $merchantId)
    {
        $data = [];
        $errors = [];
        
        try {
            $extension = $file->getClientOriginalExtension();
            
            if ($extension === 'csv') {
                $rawData = $this->readCsvFile($file);
            } else {
                $rawData = $this->readExcelFile($file);
            }
            
            // Skip header row
            $headers = array_shift($rawData);
            
            foreach ($rawData as $index => $row) {
                $customerData = $this->mapCsvRowToCustomerData($row, $headers);
                $rowNum = $index + 2;
                
                // Validate the row
                $validation = $this->validateCustomerRow($customerData, $merchantId, $rowNum);
                
                $data[] = [
                    'name' => $customerData['name'] ?? '',
                    'email' => $customerData['email'] ?? '',
                    'phone' => $customerData['phone'] ?? '',
                    'address' => $customerData['address'] ?? '',
                    'country_name' => $customerData['country'] ?? '',
                    'state' => $customerData['state'] ?? '',
                    'zip' => $customerData['zip'] ?? '',
                    'is_valid' => $validation['is_valid'],
                    'errors' => $validation['errors']
                ];
                
                if (!$validation['is_valid']) {
                    $errors[] = "Row {$rowNum}: " . $validation['errors'];
                }
            }

            return [
                'data' => $data,
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to preview file: ' . $e->getMessage());
        }
    }

    /**
     * Import customers from file
     */
    public function import($file, $merchantId)
    {
        try {
            $extension = $file->getClientOriginalExtension();

            $importedCount = 0;
            $skippedCount = 0;
            $errors = [];

            if ($extension === 'csv') {
                $rawData = $this->readCsvFile($file);
            } else {
                $rawData = $this->readExcelFile($file);
            }

            // Skip header row
            $headers = array_shift($rawData);
            
            // Get merchant's country_id
            $merchant = \App\Models\Merchant::find($merchantId);
            if (!$merchant) {
                throw new \Exception('Merchant not found');
            }

            foreach ($rawData as $index => $row) {
                try {
                    $customerData = $this->mapCsvRowToCustomerData($row, $headers);
                    $rowNum = $index + 2;
                    
                    // Validate the row
                    $validation = $this->validateCustomerRow($customerData, $merchantId, $rowNum);
                    
                    if (!$validation['is_valid']) {
                        $skippedCount++;
                        $errors[] = "Row {$rowNum}: " . $validation['errors'];
                        continue;
                    }

                    if ($customerData) {
                        $customerData['merchant_id'] = $merchantId;
                        $customerData['merchant_country_id'] = $merchant->country_id;
                        
                        // Try to find country by name
                        if (!empty($customerData['country'])) {
                            $country = \App\Models\Country::where('name', 'like', '%' . $customerData['country'] . '%')->first();
                            if ($country) {
                                $customerData['country_id'] = $country->id;
                            }
                        }
                        
                        $this->customerRepository->create($customerData);
                        $importedCount++;
                    }
                } catch (\Exception $e) {
                    $skippedCount++;
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            $message = "Import completed. {$importedCount} customers imported successfully";
            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} skipped";
            }

            return [
                'success' => true,
                'message' => $message,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            throw new \Exception('Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate customer row data
     */
    private function validateCustomerRow($customerData, $merchantId, $rowNum)
    {
        $errors = [];
        
        // Check required fields
        if (empty($customerData['name'])) {
            $errors[] = 'Missing name';
        }
        
        if (empty($customerData['email'])) {
            $errors[] = 'Missing email';
        } elseif (!filter_var($customerData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif (Customer::where('email', $customerData['email'])->where('merchant_id', $merchantId)->exists()) {
            $errors[] = 'Duplicate email for this merchant';
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => implode(', ', $errors)
        ];
    }

    /**
     * Read CSV file
     */
    private function readCsvFile($file)
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }

        fclose($handle);
        return $data;
    }

    /**
     * Read Excel file
     */
    private function readExcelFile($file)
    {
        $data = [];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        
        foreach ($worksheet->toArray() as $row) {
            $data[] = $row;
        }
        
        return $data;
    }

    /**
     * Map CSV row to customer data
     */
    private function mapCsvRowToCustomerData($row, $headers)
    {
        $customerData = [];

        foreach ($headers as $index => $header) {
            if (isset($row[$index])) {
                $normalizedHeader = strtolower(trim(str_replace('*', '', (string) $header)));
                $customerData[$normalizedHeader] = trim($row[$index]);
            }
        }

        return $customerData;
    }
}


