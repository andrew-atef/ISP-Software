<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WalletItem',
    properties: [
        new OA\Property(property: 'item_id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'quantity', type: 'integer'),
    ],
    type: 'object'
)]
class WalletItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'item_id' => $this->id,
            'name' => $this->name,
            'quantity' => (int) ($this->pivot->quantity ?? 0),
        ];
    }
}
