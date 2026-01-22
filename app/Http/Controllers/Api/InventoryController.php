<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreInventoryRequest;
use App\Http\Resources\WalletItemResource;
use App\Models\InventoryRequest;
use App\Models\InventoryRequestItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class InventoryController extends Controller
{
    #[OA\Get(
        path: '/api/inventory/wallet',
        summary: 'Get technician\'s current inventory wallet',
        security: [['BearerAuth' => []]],
        tags: ['Inventory'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wallet items retrieved successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', description: 'Inventory item ID'),
                                new OA\Property(property: 'name', type: 'string', description: 'Item name'),
                                new OA\Property(property: 'sku', type: 'string', description: 'Stock keeping unit'),
                                new OA\Property(property: 'type', type: 'string', description: 'Item type (connector, cable, etc.)'),
                                new OA\Property(property: 'quantity', type: 'integer', description: 'Current quantity in wallet'),
                            ],
                            type: 'object'
                        )
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function getWallet(): JsonResponse
    {
        $wallet = auth()->user()->inventoryWallet()->get();

        return response()->json(WalletItemResource::collection($wallet));
    }

    #[OA\Post(
        path: '/api/inventory/requests',
        summary: 'Submit an inventory replenishment request',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['pickup_date', 'pickup_location', 'items'],
                    properties: [
                        new OA\Property(property: 'pickup_date', type: 'string', format: 'date', description: 'Date to pick up inventory (today or future)', example: '2026-01-22'),
                        new OA\Property(property: 'pickup_location', type: 'string', enum: ['Daytona Beach', 'Melbourne', 'Fort Pierce'], description: 'Warehouse location for pickup', example: 'Daytona Beach'),
                        new OA\Property(property: 'notes', type: 'string', nullable: true, description: 'Additional notes for the request', example: 'Need ASAP for new installs'),
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            description: 'Inventory items requested',
                            minItems: 1,
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'item_id', type: 'integer', description: 'Inventory item ID', example: 1),
                                    new OA\Property(property: 'quantity', type: 'integer', description: 'Quantity requested', example: 10),
                                ],
                                type: 'object',
                                required: ['item_id', 'quantity']
                            )
                        ),
                    ]
                )
            )
        ),
        tags: ['Inventory'],
        responses: [
            new OA\Response(response: 200, description: 'Request submitted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function storeRequest(StoreInventoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            // Create the inventory request
            $inventoryRequest = InventoryRequest::create([
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'pickup_date' => $validated['pickup_date'],
                'pickup_location' => $validated['pickup_location'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create request items
            foreach ($validated['items'] as $item) {
                InventoryRequestItem::create([
                    'inventory_request_id' => $inventoryRequest->id,
                    'inventory_item_id' => $item['item_id'],
                    'quantity_requested' => $item['quantity'],
                ]);
            }
        });

        return response()->json(['message' => 'Request submitted successfully']);
    }
}
