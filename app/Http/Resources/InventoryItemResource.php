<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'InventoryItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'sku', type: 'string'),
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'is_tracked', type: 'boolean'),
    ],
    type: 'object'
)]
class InventoryItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'type' => $this->type?->value,
            'is_tracked' => (bool) $this->is_tracked,
        ];
    }
}
