<?php

namespace App\Http\Resources;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EnumOption',
    properties: [
        new OA\Property(property: 'label', type: 'string'),
        new OA\Property(property: 'value', type: 'string'),
    ],
    type: 'object'
)]
class EnumOption
{
    // Marker class for schema only.
}
