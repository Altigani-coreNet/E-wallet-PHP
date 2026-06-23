<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use App\Services\UnitService;
use App\Traits\MessageManager;
use App\Traits\Select2Trait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    use MessageManager, Select2Trait;

    public function __construct(public UnitService $unitService)
    {
    }

    public function index(): View
    {
        return view('units.index');
    }

    public function data(Request $request): JsonResponse
    {
        return $this->unitService->data($request);
    }

    public function create(): View
    {
        $units = Unit::where('base_unit_id', null)->get();
        return view('units.create', compact('units'));
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        $this->unitService->createUnit($request);
        session()->flash('success', __('translation.added_successfully'));
        return redirect()->route('units.index');
    }

    public function show(Unit $unit): RedirectResponse|View
    {
        if (request()->status) {
            return $this->unitService->changeStatus($unit);
        }
        return view('units.show', compact('unit'));
    }

    public function edit(Unit $unit): View
    {
        $units = Unit::where('base_unit_id', null)
            ->where('id', '!=', $unit->id)
            ->get();
        return view('units.edit', compact('unit', 'units'));
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->unitService->updateUnit($request, $unit);
        session()->flash('success', __('site.updated_successfully'));
        return redirect()->route('units.index');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->unitService->deleteUnit($unit);
        session()->flash('success', __('site.deleted_successfully'));
        return redirect()->route('units.index');
    }
} 