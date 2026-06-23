<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlutterTextWidgetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $style = $this->resource['style'] ?? [];

        return [
            'type' => 'text',
            'data' => (string) ($this->resource['data'] ?? ''),
            'textAlign' => $this->resource['textAlign'] ?? 'start',
            'maxLines' => $this->resource['maxLines'] ?? null,
            'overflow' => $this->resource['overflow'] ?? null,
            'style' => [
                'color' => $style['color'] ?? '#000000',
                'fontSize' => $style['fontSize'] ?? 14,
                'fontWeight' => $style['fontWeight'] ?? 'normal',
            ],
        ];
    }
}
