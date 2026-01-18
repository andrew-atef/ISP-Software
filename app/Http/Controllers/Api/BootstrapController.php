<?php

namespace App\Http\Controllers\Api;

use App\Enums\InstallationType;
use App\Enums\InventoryItemType;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\WalletItemResource;
use App\Models\InventoryItem;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use OpenApi\Attributes as OA;

class BootstrapController extends Controller
{
    #[OA\Get(
        path: '/api/bootstrap',
        summary: 'Offline bootstrap for technician app',
        security: [ ['BearerAuth' => []] ],
        tags: ['Bootstrap'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bootstrap payload',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(ref: '#/components/schemas/User', property: 'user'),
                            new OA\Property(
                                property: 'inventory_wallet',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/WalletItem')
                            ),
                            new OA\Property(
                                property: 'inventory_catalog',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/InventoryItem')
                            ),
                            new OA\Property(
                                property: 'tasks',
                                type: 'array',
                                items: new OA\Items(ref: '#/components/schemas/Task')
                            ),
                            new OA\Property(
                                property: 'constants',
                                properties: [
                                    new OA\Property(property: 'task_types', type: 'array', items: new OA\Items(ref: '#/components/schemas/EnumOption')),
                                    new OA\Property(property: 'task_statuses', type: 'array', items: new OA\Items(ref: '#/components/schemas/EnumOption')),
                                    new OA\Property(property: 'installation_types', type: 'array', items: new OA\Items(ref: '#/components/schemas/EnumOption')),
                                    new OA\Property(property: 'inventory_item_types', type: 'array', items: new OA\Items(ref: '#/components/schemas/EnumOption')),
                                ],
                                type: 'object'
                            ),
                        ],
                        type: 'object'
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $cutoffDate = Carbon::now()->addDays(7)->toDateString();

        // Fetch actionable tasks: Assigned, Started, Paused, ReturnedForFix, and today's Completed (for reference)
        $tasks = Task::with(['customer', 'originalTech', 'detail'])
            ->where('assigned_tech_id', $user->id)
            ->where(function ($query) {
                $query->whereIn('status', [
                    TaskStatus::Assigned,
                    TaskStatus::Started,
                    TaskStatus::Paused,
                    TaskStatus::ReturnedForFix,
                ])
                ->orWhere(function ($q) {
                    // Include today's completed tasks for reference
                    $q->where('status', TaskStatus::Completed)
                        ->whereDate('completion_date', Carbon::today());
                });
            })
            ->where(function ($query) use ($cutoffDate) {
                $query->whereDate('scheduled_date', '<=', $cutoffDate)
                    ->orWhereNull('scheduled_date');
            })
            ->orderBy('scheduled_date')
            ->orderBy('time_slot_start')
            ->get();

        $inventoryWallet = $user->inventoryWallet()->withPivot('quantity')->get();
        $inventoryCatalog = InventoryItem::select('id', 'name', 'sku', 'type', 'is_tracked')->get();

        return response()->json([
            'user' => new UserResource($user),
            'inventory_wallet' => WalletItemResource::collection($inventoryWallet),
            'inventory_catalog' => InventoryItemResource::collection($inventoryCatalog),
            'tasks' => TaskResource::collection($tasks),
            'constants' => [
                'task_types' => $this->enumMap(TaskType::cases()),
                'task_statuses' => $this->enumMap(TaskStatus::cases()),
                'installation_types' => $this->enumMap(InstallationType::cases()),
                'inventory_item_types' => $this->enumMap(InventoryItemType::cases()),
            ],
        ]);
    }

    private function enumMap(array $cases): Collection
    {
        return collect($cases)->map(fn ($case) => [
            'label' => method_exists($case, 'getLabel') ? $case->getLabel() : $this->humanize($case->value),
            'value' => $case->value,
        ]);
    }

    private function humanize(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
