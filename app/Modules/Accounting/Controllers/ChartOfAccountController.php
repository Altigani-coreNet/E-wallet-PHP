<?php

namespace App\Modules\Accounting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Requests\ImportChartOfAccountRequest;
use App\Modules\Accounting\Requests\StoreChartOfAccountRequest;
use App\Modules\Accounting\Requests\UpdateChartOfAccountRequest;
use App\Modules\Accounting\Services\ChartOfAccountService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ChartOfAccountController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ChartOfAccountService $chartOfAccountService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->chartOfAccountService->index($request->all());

            return $this->SuccessMessage($data);
        } catch (\Throwable $e) {
            Log::error('ChartOfAccountController@index: '.$e->getMessage());

            return $this->ErrorMessage('Failed to fetch chart of accounts', null, 500);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $data = $this->chartOfAccountService->show(
                $id,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->SuccessMessage($data);
        } catch (\Throwable $e) {
            Log::error('ChartOfAccountController@show: '.$e->getMessage());

            return $this->ErrorMessage('Chart of account not found', null, 404);
        }
    }

    public function store(StoreChartOfAccountRequest $request): JsonResponse
    {
        try {
            $account = $this->chartOfAccountService->store($request->validated());

            return $this->SuccessMessage(
                $this->chartOfAccountService->show($account->id),
                201
            );
        } catch (\Throwable $e) {
            Log::error('ChartOfAccountController@store: '.$e->getMessage());

            return $this->ErrorMessage('Failed to create chart of account', null, 500);
        }
    }

    public function update(UpdateChartOfAccountRequest $request, int $id): JsonResponse
    {
        try {
            $this->chartOfAccountService->update($id, $request->validated());

            return $this->SuccessMessage($this->chartOfAccountService->show($id));
        } catch (\Throwable $e) {
            Log::error('ChartOfAccountController@update: '.$e->getMessage());

            return $this->ErrorMessage('Failed to update chart of account', null, 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->chartOfAccountService->destroy($id);

            return $this->SuccessMessage(['message' => 'Account successfully deleted.']);
        } catch (RuntimeException $e) {
            return $this->ErrorMessage($e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            Log::error('ChartOfAccountController@destroy: '.$e->getMessage());

            return $this->ErrorMessage('Failed to delete chart of account', null, 500);
        }
    }

    public function types(): JsonResponse
    {
        try {
            return $this->SuccessMessage($this->chartOfAccountService->types());
        } catch (\Throwable $e) {
            Log::error('ChartOfAccountController@types: '.$e->getMessage());

            return $this->ErrorMessage('Failed to fetch account types', null, 500);
        }
    }

    public function nextCode(Request $request): JsonResponse
    {
        try {
            $typeId = $request->input('type_id') ? (int) $request->input('type_id') : null;

            return $this->SuccessMessage([
                'code' => $this->chartOfAccountService->suggestNextCode($typeId),
            ]);
        } catch (\Throwable $e) {
            Log::error('ChartOfAccountController@nextCode: '.$e->getMessage());

            return $this->ErrorMessage('Failed to suggest account code', null, 500);
        }
    }

    public function export(Request $request)
    {
        try {
            return $this->chartOfAccountService->export(
                $request->input('start_date'),
                $request->input('end_date')
            );
        } catch (\Throwable $e) {
            Log::error('ChartOfAccountController@export: '.$e->getMessage());

            return $this->ErrorMessage('Failed to export chart of accounts', null, 500);
        }
    }

    public function sample()
    {
        try {
            return $this->chartOfAccountService->sample();
        } catch (\Throwable $e) {
            Log::error('ChartOfAccountController@sample: '.$e->getMessage());

            return $this->ErrorMessage('Failed to download sample file', null, 500);
        }
    }

    public function import(ImportChartOfAccountRequest $request): JsonResponse
    {
        try {
            $result = $this->chartOfAccountService->import($request->file('file'));

            return $this->SuccessMessage($result);
        } catch (\Throwable $e) {
            Log::error('ChartOfAccountController@import: '.$e->getMessage());

            return $this->ErrorMessage('Failed to import chart of accounts: '.$e->getMessage(), null, 500);
        }
    }
}
