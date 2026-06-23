<?php

namespace App\Repositories;

use App\Models\Terminal;
use Illuminate\Http\Request;

class TerminalRepository
{
    protected $model;

    public function __construct(Terminal $model)
    {
        $this->model = $model;
    }

    /**
     * Get all terminals
     */
    public function all()
    {
        return $this->model->with('merchant')->get();
    }

    /**
     * Get terminals by merchant ID
     */
    public function getByMerchantId($merchantId)
    {
        return $this->model->where('merchant_id', $merchantId)->get();
    }

    /**
     * Get active terminals by merchant ID
     */
    public function getActiveByMerchantId($merchantId)
    {
        return $this->model->where('merchant_id', $merchantId)
                          ->where('is_active', true)
                          ->get();
    }

    /**
     * Get terminals by branch ID
     */
    public function getByBranchId($branchId)
    {
        return $this->model->where('branch_id', $branchId)->get();
    }

    /**
     * Get active terminals by branch ID
     */
    public function getActiveByBranchId($branchId)
    {
        return $this->model->where('branch_id', $branchId)
                          ->where('is_active', true)
                          ->get();
    }

    /**
     * Create a new terminal
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Update a terminal
     */
    public function update(Terminal $terminal, array $data)
    {
        $terminal->update($data);
        return $terminal;
    }

    /**
     * Delete a terminal
     */
    public function delete(Terminal $terminal)
    {
        return $terminal->delete();
    }

    /**
     * Find terminal by ID
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    /**
     * Find terminal by ID or fail
     */
    public function findOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Get terminals for select dropdown with merchant and branch filtering
     */
    public function getSelectData(Request $request)
    {
        $query = $this->model->query();
        
        // Filter by merchant if provided
        if ($request->has('merchant_id') && $request->merchant_id) {
            $query->where('merchant_id', $request->merchant_id);
        }
        
        // Filter by branch if provided
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Filter by active status if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('terminal_id', 'like', "%{$search}%");
            });
        }
        
        return $query->select('id', 'name', 'terminal_id')
                    ->orderBy('name')
                    ->limit(10)
                    ->get()
                    ->map(function ($terminal) {
                        return [
                            'id' => $terminal->id,
                            'text' => $terminal->name . ' (' . $terminal->terminal_id . ')'
                        ];
                    });
    }

    /**
     * Find terminal by terminal ID
     */
    public function findByTerminalId($terminalId)
    {
        return $this->model->where('terminal_id', $terminalId)->first();
    }

    /**
     * Check if terminal ID exists
     */
    public function terminalIdExists($terminalId, $excludeId = null)
    {
        $query = $this->model->where('terminal_id', $terminalId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}

