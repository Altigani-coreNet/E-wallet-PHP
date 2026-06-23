<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlutterCardWidgetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $margin = $this->resource['margin'] ?? [];

        return [
            'type' => 'card',
            'color' => $this->resource['color'] ?? '#FFFFFF',
            'shadowColor' => $this->resource['shadowColor'] ?? '#000000',
            'surfaceTintColor' => $this->resource['surfaceTintColor'] ?? '#000000',
            'elevation' => $this->resource['elevation'] ?? 0,
            'shape' => [
                'type' => 'roundedRectangle',
                'borderRadius' => $this->resource['borderRadius'] ?? 10.0,
            ],
            'borderOnForeground' => $this->resource['borderOnForeground'] ?? true,
            'margin' => [
                'left' => $margin['left'] ?? 0,
                'top' => $margin['top'] ?? 0,
                'right' => $margin['right'] ?? 0,
                'bottom' => $margin['bottom'] ?? 0,
            ],
            'clipBehavior' => $this->resource['clipBehavior'] ?? 'antiAlias',
            'child' => FlutterTextWidgetResource::make([
                'data' => (string) ($this->resource['childText'] ?? ''),
            ]),
            'semanticContainer' => $this->resource['semanticContainer'] ?? true,
        ];
    }
}
