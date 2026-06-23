<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Merchant;
use App\Repositories\CustomerRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function __construct(private readonly CustomerRepository $customerRepository)
    {
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
        
        return $this->customerRepository->create($data);
    }

    /**
     * Create customer (used by admin - merchant_id comes from data)
     */
    public function create(array $data): Customer
    {
        $merchantId = $data['merchant_id'];
        
        if ($this->customerRepository->emailExistsForMerchant($data['email'], $merchantId)) {
            throw ValidationException::withMessages(['email' => 'Customer with this email already exists for this merchant']);
        }

        $data['merchant_country_id'] = $this->getMerchantCountryId($merchantId);
        
        return $this->customerRepository->create($data);
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
     * Update customer (used by admin - merchant_id comes from data)
     */
    public function update(Customer $customer, array $data): Customer
    {
        $merchantId = $data['merchant_id'];
        
        if ($this->customerRepository->emailExistsForMerchant($data['email'], $merchantId, $customer->id)) {
            throw ValidationException::withMessages(['email' => 'Customer with this email already exists for this merchant']);
        }

        $data['merchant_country_id'] = $this->getMerchantCountryId($merchantId);
        
        return $this->customerRepository->update($customer, $data);
    }

    /**
     * Delete customer for merchant (used by merchant controllers)
     */
    public function deleteForMerchant(int $merchantId, Customer $customer): void
    {
        if ($customer->merchant_id !== $merchantId) {
            throw ValidationException::withMessages(['customer' => 'Customer not found']);
        }

        $this->customerRepository->delete($customer);
    }

    /**
     * Delete customer (used by admin)
     */
    public function delete(Customer $customer): void
    {
        $this->customerRepository->delete($customer);
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
                'Name',
                'Email',
                'Phone',
                'Address',
                'Country',
                'State',
                'Zip'
            ]);

            // Add sample data rows
            fputcsv($file, [
                'John Doe',
                'john.doe@example.com',
                '+1234567890',
                '123 Main Street, City',
                'United States',
                'California',
                '90001'
            ]);

            fputcsv($file, [
                'Jane Smith',
                'jane.smith@example.com',
                '+1234567891',
                '456 Oak Avenue, Town',
                'Canada',
                'Ontario',
                'M5H 2N2'
            ]);

            fputcsv($file, [
                'Bob Johnson',
                'bob.johnson@example.com',
                '+1234567892',
                '789 Pine Road, Village',
                'United Kingdom',
                'London',
                'SW1A 1AA'
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
                $customerData[strtolower(trim($header))] = trim($row[$index]);
            }
        }

        return $customerData;
    }
}


