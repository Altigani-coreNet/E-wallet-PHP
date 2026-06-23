<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'card_number' => $this->card_number,
            'app_name' => $this->app_name,
            'ref_no' => $this->ref_no,
            'status' => $this->status,
            'rrn' => $this->rrn,
            'timestamp' => $this->timestamp,
            'batch_no' => $this->batch_no,
            'amount' => $this->amount,
            'currency' => $this->currency_object ?? ($this->currency_symbol ? ['symbol' => $this->currency_symbol] : null),
            'partner_name' => $this->partner_name,
            'service_category_name' => $this->service_category_name,
            'service_name' => $this->service_name,
        ];
    }
}

