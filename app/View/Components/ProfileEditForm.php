<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ProfileEditForm extends Component
{
    public $user;
    public $type;
    public $categories;
    public $countries;
    public $doc;
    public $banner_image;
    public $company_proof;
    public $trade_proof;
    public $expert_id;
    public $expert_licences;

    public function __construct($user, $type)
    {
//        dd($type);
        $this->user = $user;
        $this->type = $type;

        $this->categories = $user->Profile->SubCategories->pluck('id')->toArray();
        $this->countries = $user->Profile->Countries->pluck('id')->toArray();

        $this->doc = [];
        $this->doc["banner_image"] = $user->Profile->LatestBannerImage->url ?? null;
        $this->banner_image = $this->doc["banner_image"];

        if ($type == "company") {
            $this->company_proof = $user->Profile->attachments->firstWhere('url_type', 'company_proof')->url ?? null;
            $this->trade_proof = $user->Profile->attachments->firstWhere('url_type', 'trade_proof')->url ?? null;
        } else {
            $this->expert_id = $user->Profile->attachments->firstWhere('url_type', 'expert_id')->url ?? null;
            $this->expert_licences = $user->Profile->attachments->firstWhere('url_type', 'expert_licences')->url ?? null;
        }
    }

    public function render()
    {
        return view('components.profile-edit-form');
    }
}
