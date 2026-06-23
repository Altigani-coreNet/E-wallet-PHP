<?php

namespace App\Services;

use App\Models\UserGroup;
use App\Models\Merchant;
use App\Models\User;
use App\Models\TerminalGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class UserGroupService
{
    public function __construct()
    {
        //
    }

    /**
     * Import user groups from file
     */
    public function import($file)
    {
        try {
            $data = $this->readFile($file);
            $importedCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                try {
                    $this->importUserGroup($row);
                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Successfully imported {$importedCount} user groups.",
                'imported_count' => $importedCount,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Export template for user groups import
     */
    public function exportTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="user_groups_import_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, [
                'name',
                'merchant_id',
                'description',
                'is_single_terminal',
                'terminal_id',
                'terminal_group_ids',
                'user_ids',
                'is_active'
            ]);
            
            // Add sample data
            fputcsv($file, [
                'Sample User Group',
                '1',
                'Sample user group description',
                '1',
                '1',
                '1,2,3',
                '1,2,3',
                '1'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Read file (CSV or Excel)
     */
    private function readFile($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        if ($extension === 'csv') {
            return $this->readCsvFile($file);
        } else {
            return $this->readExcelFile($file);
        }
    }

    /**
     * Read CSV file
     */
    private function readCsvFile($file)
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        
        // Skip header row
        fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 8) {
                $data[] = [
                    'name' => trim($row[0]),
                    'merchant_id' => trim($row[1]),
                    'description' => trim($row[2]),
                    'is_single_terminal' => trim($row[3]),
                    'terminal_id' => trim($row[4]),
                    'terminal_group_ids' => trim($row[5]),
                    'user_ids' => trim($row[6]),
                    'is_active' => trim($row[7])
                ];
            }
        }
        
        fclose($handle);
        return $data;
    }

    /**
     * Read Excel file using simple approach
     */
    private function readExcelFile($file)
    {
        $data = [];
        
        try {
            // For Excel files, we'll use a simple CSV approach
            $handle = fopen($file->getPathname(), 'r');
            
            // Skip header row
            fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 8) {
                    $data[] = [
                        'name' => trim($row[0] ?? ''),
                        'merchant_id' => trim($row[1] ?? ''),
                        'description' => trim($row[2] ?? ''),
                        'is_single_terminal' => trim($row[3] ?? '1'),
                        'terminal_id' => trim($row[4] ?? ''),
                        'terminal_group_ids' => trim($row[5] ?? ''),
                        'user_ids' => trim($row[6] ?? ''),
                        'is_active' => trim($row[7] ?? '1')
                    ];
                }
            }
            
            fclose($handle);
        } catch (\Exception $e) {
            throw new \Exception("Error reading Excel file: " . $e->getMessage());
        }
        
        return $data;
    }

    /**
     * Import single user group
     */
    private function importUserGroup($data)
    {
        // Validate required fields
        if (empty($data['name']) || empty($data['merchant_id'])) {
            throw new \Exception("Name and merchant_id are required");
        }

        // Check if merchant exists
        $merchant = Merchant::find($data['merchant_id']);
        if (!$merchant) {
            throw new \Exception("Merchant with ID {$data['merchant_id']} not found");
        }

        // Validate is_single_terminal
        $isSingleTerminal = filter_var($data['is_single_terminal'] ?? '1', FILTER_VALIDATE_BOOLEAN);

        // Validate terminal_id if single terminal mode
        if ($isSingleTerminal && !empty($data['terminal_id'])) {
            // Add terminal validation here if needed
        }

        // Validate user_ids
        if (empty($data['user_ids'])) {
            throw new \Exception("At least one user must be assigned");
        }

        $userIds = array_map('trim', explode(',', $data['user_ids']));
        $userIds = array_filter($userIds); // Remove empty values

        if (empty($userIds)) {
            throw new \Exception("At least one valid user ID must be provided");
        }

        // Validate that all users exist and belong to the merchant
        $users = User::whereIn('id', $userIds)->where('merchant_id', $data['merchant_id'])->get();
        if ($users->count() !== count($userIds)) {
            throw new \Exception("Some user IDs are invalid or don't belong to the specified merchant");
        }

        // Prepare user group data
        $userGroupData = [
            'name' => $data['name'],
            'group_id' => UserGroup::generateGroupId(),
            'merchant_id' => $data['merchant_id'],
            'description' => $data['description'] ?? null,
            'is_active' => filter_var($data['is_active'] ?? '1', FILTER_VALIDATE_BOOLEAN),
            'is_single_terminal' => $isSingleTerminal,
            'terminal_id' => $isSingleTerminal ? ($data['terminal_id'] ?? null) : null,
        ];

        // Create user group
        $userGroup = UserGroup::create($userGroupData);

        // Attach users
        $userGroup->users()->attach($userIds);

        // Attach terminal groups if not single terminal mode
        if (!$isSingleTerminal && !empty($data['terminal_group_ids'])) {
            $terminalGroupIds = array_map('trim', explode(',', $data['terminal_group_ids']));
            $terminalGroupIds = array_filter($terminalGroupIds);
            
            if (!empty($terminalGroupIds)) {
                // Validate terminal groups exist and belong to the merchant
                $terminalGroups = TerminalGroup::whereIn('id', $terminalGroupIds)
                    ->where('merchant_id', $data['merchant_id'])
                    ->get();
                
                if ($terminalGroups->count() === count($terminalGroupIds)) {
                    $userGroup->terminalGroups()->attach($terminalGroupIds);
                }
            }
        }
    }
} 