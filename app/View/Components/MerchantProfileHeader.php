<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\Merchant;
use App\Repositories\MerchantRepository;

class MerchantProfileHeader extends Component
{
    public $merchant;
    public $activeTab;
    public $profileCompletion;
    protected $merchantRepository;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(Merchant $merchant, $activeTab = 'overview')
    {
        $this->merchant = $merchant;
        $this->activeTab = $activeTab;
        $this->merchantRepository = new MerchantRepository($merchant);
        $this->profileCompletion = Merchant::calculateProfileCompletion($merchant);
    }

    /**
     * Calculate profile completion
     */
    protected function calculateProfileCompletion()
    {
        // Get documents
        $documents = $this->merchant->attachments();
        $hasLogo = $documents->where('type', 'company_logo')->first();
        $hasUserId = $documents->where('type', 'user_id')->first();
        $hasTaxCertified = $documents->where('type', 'tax_certified')->first();
        $hasTradeLicense = $documents->where('type', 'trade_license')->first();

        // Check basic profile
        $hasProfile = $this->merchant->name && 
                     $this->merchant->owner_name && 
                     $this->merchant->email && 
                     $this->merchant->phone && 
                     $this->merchant->address;

        // Check users and terminals
        $hasUsers = $this->merchant->users()->count() > 0;
        $hasTerminal = $this->merchant->terminals()->count() > 0;

        return $this->merchantRepository->calculateProfileCompletion(
            $this->merchant,
            $hasLogo,
            null, // banner image not required
            $hasTradeLicense,
            true, // links not required
            true  // project not required
        );
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.merchant-profile-header', [
            'merchant' => $this->merchant,
            'activeTab' => $this->activeTab,
            'profileCompletion' => $this->profileCompletion
        ]);
    }
}
