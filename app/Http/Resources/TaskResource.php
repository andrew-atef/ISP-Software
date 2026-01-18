<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Task',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'task_type', type: 'string'),
        new OA\Property(property: 'status', type: 'string'),
        new OA\Property(property: 'financial_status', type: 'string', nullable: true),
        new OA\Property(property: 'scheduled_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'time_slot_start', type: 'string', nullable: true),
        new OA\Property(property: 'time_slot_end', type: 'string', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'saf_link', type: 'string', nullable: true),
        new OA\Property(
            property: 'customer',
            properties: [
                new OA\Property(property: 'id', type: 'integer', nullable: true),
                new OA\Property(property: 'name', type: 'string', nullable: true),
                new OA\Property(property: 'address', type: 'string', nullable: true),
                new OA\Property(property: 'phone', type: 'string', nullable: true),
                new OA\Property(property: 'lat', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'lng', type: 'number', format: 'float', nullable: true),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'original_tech',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'code', type: 'string'),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'detail',
            nullable: true,
            properties: [
                new OA\Property(property: 'ont_serial', type: 'string', nullable: true),
                new OA\Property(property: 'eero_serial_1', type: 'string', nullable: true),
                new OA\Property(property: 'eero_serial_2', type: 'string', nullable: true),
                new OA\Property(property: 'eero_serial_3', type: 'string', nullable: true),
                new OA\Property(property: 'drop_bury_status', type: 'boolean', nullable: true),
                new OA\Property(property: 'sidewalk_bore_status', type: 'boolean', nullable: true),
                new OA\Property(property: 'tech_notes', type: 'string', nullable: true),
                new OA\Property(property: 'start_time_actual', type: 'string', format: 'date-time', nullable: true),
                new OA\Property(property: 'end_time_actual', type: 'string', format: 'date-time', nullable: true),
            ],
            type: 'object'
        ),
    ],
    type: 'object'
)]
class TaskResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'task_type' => $this->task_type?->value,
            'status' => $this->status?->value,
            'financial_status' => $this->financial_status?->value,
            'scheduled_date' => $this->scheduled_date,
            'time_slot_start' => $this->time_slot_start,
            'time_slot_end' => $this->time_slot_end,
            'description' => $this->description,
            'saf_link' => $this->saf_link,
            'customer' => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
                'address' => $this->customer?->address,
                'phone' => $this->customer?->phone,
                'lat' => $this->customer?->lat,
                'lng' => $this->customer?->lng,
            ],
            'original_tech' => $this->whenLoaded('originalTech', function () {
                return [
                    'id' => $this->originalTech?->id,
                    'name' => $this->originalTech?->name,
                    'code' => $this->originalTech?->code,
                ];
            }),
            'detail' => $this->whenLoaded('detail', function () {
                return $this->detail ? [
                    'ont_serial' => $this->detail->ont_serial,
                    'eero_serial_1' => $this->detail->eero_serial_1,
                    'eero_serial_2' => $this->detail->eero_serial_2,
                    'eero_serial_3' => $this->detail->eero_serial_3,
                    'drop_bury_status' => $this->detail->drop_bury_status,
                    'sidewalk_bore_status' => $this->detail->sidewalk_bore_status,
                    'tech_notes' => $this->detail->tech_notes,
                    'start_time_actual' => optional($this->detail->start_time_actual)?->toDateTimeString(),
                    'end_time_actual' => optional($this->detail->end_time_actual)?->toDateTimeString(),
                ] : null;
            }),
        ];
    }
}
