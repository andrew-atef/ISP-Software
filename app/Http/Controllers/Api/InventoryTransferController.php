<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\InventoryTransactionType;
use App\Http\Requests\Api\StoreInventoryTransferRequest;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransaction;
use App\Models\InventoryWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class InventoryTransferController extends Controller
{
    #[OA\Post(
        path: '/api/inventory/transfer',
        summary: 'Create a P2P inventory transfer request',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['receiver_id', 'items'],
                    properties: [
                        new OA\Property(property: 'receiver_id', type: 'integer', description: 'Technician ID to receive items', example: 2),
                        new OA\Property(property: 'notes', type: 'string', nullable: true, description: 'Optional notes for transfer', example: 'Extra connectors for upcoming installs'),
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            description: 'Items to transfer',
                            minItems: 1,
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'item_id', type: 'integer', description: 'Inventory item ID', example: 1),
                                    new OA\Property(property: 'quantity', type: 'integer', description: 'Quantity to transfer', example: 5),
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
            new OA\Response(response: 200, description: 'Transfer request created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error (insufficient stock or invalid receiver)'),
        ]
    )]
    public function store(StoreInventoryTransferRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $senderId = auth()->id();

        // Verify sender has enough stock for all items
        foreach ($validated['items'] as $item) {
            $wallet = InventoryWallet::where('user_id', $senderId)
                ->where('inventory_item_id', $item['item_id'])
                ->first();

            $currentQuantity = $wallet?->quantity ?? 0;

            if ($currentQuantity < $item['quantity']) {
                return response()->json([
                    'message' => "Insufficient stock for item ID {$item['item_id']}. Available: {$currentQuantity}, Requested: {$item['quantity']}",
                ], 422);
            }
        }

        DB::transaction(function () use ($validated, $senderId) {
            $transfer = InventoryTransfer::create([
                'sender_id' => $senderId,
                'receiver_id' => $validated['receiver_id'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $transfer->items()->attach($item['item_id'], ['quantity' => $item['quantity']]);
            }
        });

        return response()->json(['message' => 'Transfer request created successfully']);
    }

    #[OA\Get(
        path: '/api/inventory/transfer/incoming',
        summary: 'List pending incoming inventory transfers',
        security: [['BearerAuth' => []]],
        tags: ['Inventory'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of pending incoming transfers',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'sender_id', type: 'integer'),
                                new OA\Property(property: 'sender_name', type: 'string'),
                                new OA\Property(property: 'status', type: 'string', enum: ['pending', 'accepted', 'rejected']),
                                new OA\Property(property: 'notes', type: 'string', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(
                                    property: 'items',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'item_id', type: 'integer'),
                                            new OA\Property(property: 'name', type: 'string'),
                                            new OA\Property(property: 'quantity', type: 'integer'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ],
                            type: 'object'
                        )
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function incomingList(): JsonResponse
    {
        $transfers = InventoryTransfer::where('receiver_id', auth()->id())
            ->where('status', 'pending')
            ->with(['sender', 'items'])
            ->orderByDesc('created_at')
            ->get();

        $response = $transfers->map(function ($transfer) {
            return [
                'id' => $transfer->id,
                'sender_id' => $transfer->sender_id,
                'sender_name' => $transfer->sender->name,
                'status' => $transfer->status,
                'notes' => $transfer->notes,
                'created_at' => $transfer->created_at,
                'items' => $transfer->items->map(function ($item) {
                    return [
                        'item_id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->pivot->quantity,
                    ];
                }),
            ];
        });

        return response()->json($response);
    }

    #[OA\Post(
        path: '/api/inventory/transfer/{id}/accept',
        summary: 'Accept an incoming inventory transfer',
        security: [['BearerAuth' => []]],
        tags: ['Inventory'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Transfer accepted successfully'),
            new OA\Response(response: 403, description: 'Not the recipient of this transfer'),
            new OA\Response(response: 404, description: 'Transfer not found'),
            new OA\Response(response: 422, description: 'Transfer not pending or sender insufficient stock'),
        ]
    )]
    public function accept(int $id): JsonResponse
    {
        $transfer = InventoryTransfer::lockForUpdate()->findOrFail($id);
        $receiverId = auth()->id();

        if ($transfer->receiver_id !== $receiverId) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Transfer is not pending.'], 422);
        }

        // Re-verify sender stock (race condition check)
        foreach ($transfer->items as $item) {
            $wallet = InventoryWallet::lockForUpdate()
                ->where('user_id', $transfer->sender_id)
                ->where('inventory_item_id', $item->id)
                ->first();

            $currentQuantity = $wallet?->quantity ?? 0;
            $requestedQuantity = $item->pivot->quantity;

            if ($currentQuantity < $requestedQuantity) {
                return response()->json([
                    'message' => "Sender no longer has sufficient stock for item ID {$item->id}.",
                ], 422);
            }
        }

        DB::transaction(function () use ($transfer, $receiverId) {
            foreach ($transfer->items as $item) {
                $quantity = $item->pivot->quantity;

                // Decrement sender wallet
                $senderWallet = InventoryWallet::firstOrCreate(
                    ['user_id' => $transfer->sender_id, 'inventory_item_id' => $item->id],
                    ['quantity' => 0]
                );
                $senderWallet->decrement('quantity', $quantity);

                // Increment receiver wallet
                $receiverWallet = InventoryWallet::firstOrCreate(
                    ['user_id' => $receiverId, 'inventory_item_id' => $item->id],
                    ['quantity' => 0]
                );
                $receiverWallet->increment('quantity', $quantity);

                // Log sender transaction (Transfer Out)
                InventoryTransaction::create([
                    'inventory_item_id' => $item->id,
                    'source_user_id' => $transfer->sender_id,
                    'destination_user_id' => $receiverId,
                    'quantity' => -$quantity,
                    'type' => InventoryTransactionType::TransferOut,
                    'notes' => "Transferred to {$transfer->receiver->name} (Transfer ID: {$transfer->id})",
                ]);

                // Log receiver transaction (Transfer In)
                InventoryTransaction::create([
                    'inventory_item_id' => $item->id,
                    'source_user_id' => $transfer->sender_id,
                    'destination_user_id' => $receiverId,
                    'quantity' => $quantity,
                    'type' => InventoryTransactionType::TransferIn,
                    'notes' => "Received from {$transfer->sender->name} (Transfer ID: {$transfer->id})",
                ]);
            }

            $transfer->update(['status' => 'accepted']);
        });

        return response()->json(['message' => 'Transfer accepted successfully']);
    }

    #[OA\Post(
        path: '/api/inventory/transfer/{id}/reject',
        summary: 'Reject an incoming inventory transfer',
        security: [['BearerAuth' => []]],
        tags: ['Inventory'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Transfer rejected successfully'),
            new OA\Response(response: 403, description: 'Not the recipient of this transfer'),
            new OA\Response(response: 404, description: 'Transfer not found'),
        ]
    )]
    public function reject(int $id): JsonResponse
    {
        $transfer = InventoryTransfer::findOrFail($id);

        if ($transfer->receiver_id !== auth()->id()) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $transfer->update(['status' => 'rejected']);

        return response()->json(['message' => 'Transfer rejected']);
    }
}
