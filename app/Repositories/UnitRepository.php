<?php

namespace App\Repositories;

use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use App\Services\UnitService;
use App\Traits\MessageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class UnitRepository implements UnitService
{
    use MessageManager;

    public function data(Request $request): JsonResponse
    {
        $query = Unit::where('shop_id' ,auth()->user()->shop_id);

        return DataTables::of($query)
            ->addColumn('record_select', function ($unit) {
                return view('units.data_table.record_select', compact('unit'))->render();
            })
            ->addColumn('name', function ($unit) {
                return $unit->getTranslation('name', app()->getLocale());
            })
            ->addColumn('base_unit', function ($unit) {
                return $unit->baseUnit ? $unit->baseUnit->getTranslation('name', app()->getLocale()) : '-';
            })
            ->addColumn('status', function ($unit) {
                return $unit->getStatusWithSpan();
            })
            ->addColumn('actions', function ($unit) {
                return view('units.data_table.actions', compact('unit'))->render();
            })
            ->rawColumns(['record_select', 'status', 'actions'])
            ->make(true);
    }

    public function createUnit(UnitRequest $request): Unit
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        return Unit::create($data);
    }

    public function updateUnit(UnitRequest $request, Unit $unit): bool
    {
        return $unit->update($request->validated());
    }

    public function deleteUnit(Unit $unit): bool
    {
        return $unit->delete();
    }

    public function changeStatus(Unit $unit): RedirectResponse
    {
        try {
            $unit->ChangeStatus();
            $this->SuccessMessage(__("translation.unit_status_has_been_changed"));
            return redirect()->back();
        } catch (\Exception $exception) {
            $this->ErrorMessage($exception->getMessage());
            return redirect()->back();
        }
    }
} 