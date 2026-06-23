<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'code' => $this->code,
            'status' => $this->status,
            'flag_url' => $this->getFlagUrl(),
            
        ];
    }

    /**
     * Get the flag URL for the country
     */
    private function getFlagUrl(): ?string
    {
        if (!$this->short_name) {
            return null;
        }

        $flagPath = "flags/{$this->short_name}.jpg";
        $fullPath = public_path($flagPath);
        
        // Check if flag file exists
        if (file_exists($fullPath)) {
            return asset($flagPath);
        }

        // Return null if flag doesn't exist
        return null;
    }
}
