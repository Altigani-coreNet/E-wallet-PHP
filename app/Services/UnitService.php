<?php

namespace App\Services;

use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

interface UnitService
{
    public function data(Request $request): JsonResponse;
    public function createUnit(UnitRequest $request): Unit;
    public function updateUnit(UnitRequest $request, Unit $unit): bool;
    public function deleteUnit(Unit $unit): bool;
    public function changeStatus(Unit $unit): RedirectResponse;
} 