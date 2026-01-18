<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'current_week_earnings', type: 'number', format: 'float'),
        new OA\Property(property: 'avatar_url', type: 'string', nullable: true),
    ],
    type: 'object'
)]
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'current_week_earnings' => (float) ($this->current_week_earnings ?? 0),
            'avatar_url' => $this->avatar_url ?? null,
        ];
    }
}
