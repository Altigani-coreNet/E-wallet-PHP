<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\Partner as ContentProvider;
use App\Repositories\PartnerRepository as ContentProviderRepository;
use Illuminate\Support\Facades\DB;

class PartnerService extends ContentProviderService
{
    public function __construct(
        ContentProviderRepository $contentProviderRepository,
        private readonly PartnerPayableAccountService $partnerPayableAccountService,
    ) {
        parent::__construct($contentProviderRepository);
    }

    public function approve(string $id): array
    {
        return DB::transaction(function () use ($id) {
            $result = parent::approve($id);

            $this->allocatePayableAccount($id);

            return $result;
        });
    }

    protected function afterContentProviderCreated(ContentProvider $contentProvider): void
    {
        $this->allocatePayableAccount($contentProvider->id);
    }

    private function allocatePayableAccount(string $partnerId): void
    {
        $partner = Partner::withoutGlobalScopes()->findOrFail($partnerId);
        $this->partnerPayableAccountService->allocateForPartner($partner);
    }
}
