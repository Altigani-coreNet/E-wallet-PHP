<?php

namespace App\Services;

use App\Http\Requests\PlanStoreRequest;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;

interface PlanService
{
    public function index();
    public function data();
    public function create();
    public function store(PlanStoreRequest $request);
    public function show(Plan $plan);
    public function edit(Plan $plan);
    public function update(PlanStoreRequest $request, Plan $plan);
    public function destroy(Plan $plan);
    public function getPlans();
    public function changeStatus(Plan $plan);
    public function getAllPlans();
    public function getPlanName(): Collection|array;
    public function getPaginated(array $filters = [], int $perPage = 15);
    public function find($id);
    public function findOrFail($id);
}
