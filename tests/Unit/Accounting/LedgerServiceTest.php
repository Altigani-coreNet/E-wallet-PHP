<?php

namespace Tests\Unit\Accounting;

use App\Models\TransactionLine;
use App\Services\LedgerService;
use App\Support\AccountCode;
use Database\Seeders\ChartOfAccountSeeder;
use Tests\CustomerAuthTestCase;
use Tests\Support\AccountingTestHelpers;

class LedgerServiceTest extends CustomerAuthTestCase
{
    use AccountingTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
    }

    public function test_post_assigns_shared_operation_id_to_all_lines(): void
    {
        $ledgerService = app(LedgerService::class);
        $operationId = '11111111-1111-4111-8111-111111111111';

        $ledgerService->post(
            [
                ['account_code' => AccountCode::BANK, 'debit' => 50, 'sub_id' => 0],
                ['account_code' => AccountCode::CUSTOMER_LIABILITY, 'credit' => 50, 'sub_id' => 1],
            ],
            LedgerService::REF_WALLET_TOPUP,
            '99',
            createdBy: 0,
            operationId: $operationId
        );

        $lines = TransactionLine::query()
            ->where('operation_id', $operationId)
            ->orderBy('reference_sub_id')
            ->get();

        $this->assertCount(2, $lines);
        $this->assertSame($operationId, $lines[0]->operation_id);
        $this->assertSame($operationId, $lines[1]->operation_id);
    }

    public function test_post_generates_operation_id_when_not_provided(): void
    {
        $ledgerService = app(LedgerService::class);

        $ledgerService->post(
            [
                ['account_code' => AccountCode::BANK, 'debit' => 25, 'sub_id' => 0],
                ['account_code' => AccountCode::CUSTOMER_LIABILITY, 'credit' => 25, 'sub_id' => 1],
            ],
            LedgerService::REF_WALLET_TOPUP,
            '100'
        );

        $lines = TransactionLine::query()
            ->where('reference', LedgerService::REF_WALLET_TOPUP)
            ->where('reference_id', '100')
            ->get();

        $this->assertCount(2, $lines);
        $this->assertNotNull($lines[0]->operation_id);
        $this->assertSame($lines[0]->operation_id, $lines[1]->operation_id);
    }
}
