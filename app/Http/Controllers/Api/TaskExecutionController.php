<?php

namespace App\Http\Controllers\Api;

use App\Enums\InstallationType;
use App\Enums\InventoryTransactionType;
use App\Enums\TaskMediaType;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CompleteTaskRequest;
use App\Models\InventoryTransaction;
use App\Models\InventoryWallet;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\TaskInventoryConsumption;
use App\Models\TaskMedia;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use OpenApi\Attributes as OA;

class TaskExecutionController extends Controller
{
    #[OA\Post(
        path: '/api/tasks/{id}/start',
        summary: 'Start working on a task',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['lat', 'lng'],
                    properties: [
                        new OA\Property(property: 'lat', type: 'number', format: 'float'),
                        new OA\Property(property: 'lng', type: 'number', format: 'float'),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', nullable: true),
                    ]
                )
            )
        ),
        tags: ['Tasks'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Task started'),
            new OA\Response(response: 403, description: 'Not assigned to this task'),
            new OA\Response(response: 404, description: 'Task not found'),
        ]
    )]
    public function start(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'timestamp' => ['nullable', 'date'],
        ]);

        $task = Task::findOrFail($id);

        if ($task->assigned_tech_id !== $request->user()->id) {
            return response()->json(['message' => 'Not authorized to start this task.'], 403);
        }

        // Reject if already completed or approved
        if (in_array($task->status, [TaskStatus::Completed, TaskStatus::Approved])) {
            return response()->json(['message' => 'Task already completed.'], 400);
        }

        // Allow starting if: Assigned, ReturnedForFix, or Paused (resuming)
        if (!in_array($task->status, [TaskStatus::Assigned, TaskStatus::ReturnedForFix, TaskStatus::Paused, TaskStatus::Started])) {
            return response()->json(['message' => 'Task cannot be started in current status.'], 400);
        }

        $startTime = isset($validated['timestamp']) ? Carbon::parse($validated['timestamp']) : now();

        $task->update(['status' => TaskStatus::Started]);

        TaskDetail::updateOrCreate(
            ['task_id' => $task->id],
            [
                'start_lat' => $validated['lat'],
                'start_lng' => $validated['lng'],
                'start_time_actual' => $startTime,
            ]
        );

        return response()->json(['message' => 'Task started successfully.']);
    }

    #[OA\Post(
        path: '/api/tasks/{id}/pause',
        summary: 'Pause a started task',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['reason'],
                    properties: [
                        new OA\Property(property: 'reason', type: 'string'),
                        new OA\Property(property: 'lat', type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'lng', type: 'number', format: 'float', nullable: true),
                    ]
                )
            )
        ),
        tags: ['Tasks'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Task paused'),
            new OA\Response(response: 400, description: 'Task not started'),
            new OA\Response(response: 403, description: 'Not authorized'),
        ]
    )]
    public function pause(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        $task = Task::findOrFail($id);

        if ($task->assigned_tech_id !== $request->user()->id) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        if ($task->status !== TaskStatus::Started) {
            return response()->json(['message' => 'Task must be started to pause.'], 400);
        }

        $task->update(['status' => TaskStatus::Paused]);

        $detail = $task->detail;
        if ($detail) {
            $pauseNote = "\n\n[PAUSED - " . now()->format('Y-m-d H:i') . "]: " . $validated['reason'];
            $detail->update(['tech_notes' => ($detail->tech_notes ?? '') . $pauseNote]);
        }

        return response()->json(['message' => 'Task paused.']);
    }

    #[OA\Post(
        path: '/api/tasks/{id}/media',
        summary: 'Upload task media (photo)',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file', 'type'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'Image file (JPEG/PNG, max 10MB)'),
                        new OA\Property(property: 'type', type: 'string', enum: ['work', 'bury', 'bore', 'general'], description: 'Media type classification'),
                        new OA\Property(property: 'watermark_meta', type: 'string', description: 'JSON-encoded watermark metadata (location, timestamp, etc.)'),
                    ]
                )
            )
        ),
        tags: ['Tasks'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Media uploaded successfully'),
            new OA\Response(response: 403, description: 'Not authorized to upload media for this task'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function uploadMedia(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpeg,png,jpg', 'max:10240'],
            'type' => ['required', Rule::enum(TaskMediaType::class)],
            'watermark_meta' => ['nullable', 'string'],
        ]);

        $task = Task::findOrFail($id);

        if ($task->assigned_tech_id !== $request->user()->id) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $file = $request->file('file');

        // Safely parse watermark metadata JSON
        $watermarkData = null;
        if (!empty($validated['watermark_meta'])) {
            $decoded = json_decode($validated['watermark_meta'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $watermarkData = $decoded;
            }
        }

        // Read image and add watermark if metadata exists
        $tempPath = $file->store('temp', 'local');
        $fullPath = Storage::disk('local')->path($tempPath);

        // Load image using GD directly
        $image = imagecreatefromjpeg($fullPath);
        if ($image === false) {
            // Try PNG
            $image = imagecreatefrompng($fullPath);
        }
        if ($image === false) {
            return response()->json(['message' => 'Invalid image file.'], 422);
        }

        if ($watermarkData) {
            $timestamp = $watermarkData['timestamp'] ?? '';
            $location = $watermarkData['location'] ?? '';

            // Format watermark text
            $watermarkText = trim($timestamp . "\n" . $location);

            // Get image dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Define colors
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);

            // Use TTF font with readable size (1/30 of image width)
            $fontSize = max(4, intval($width / 30));
            $fontPath = public_path('fonts/arial.ttf');

            // If font doesn't exist, try alternative
            if (!file_exists($fontPath)) {
                $fontPath = __DIR__ . '/../../../public/fonts/arial.ttf';
            }

            // If still doesn't exist, use a basic font file
            if (!file_exists($fontPath)) {
                $fontPath = 'C:\Windows\Fonts\arial.ttf'; // Windows path
            }

            // Check if we can use TTF
            if (file_exists($fontPath)) {
                // Position: left side, moved up from bottom
                $textX = 20;
                $textY = $height - 120;

                // Draw each line with black shadow
                $lines = explode("\n", $watermarkText);
                $lineOffset = 0;

                foreach ($lines as $line) {
                    // Draw black outline (shadow effect)
                    for ($offsetX = -2; $offsetX <= 2; $offsetX++) {
                        for ($offsetY = -2; $offsetY <= 2; $offsetY++) {
                            if ($offsetX != 0 || $offsetY != 0) {
                                imagettftext($image, $fontSize, 0, $textX + $offsetX, $textY + $offsetY + $lineOffset, $black, $fontPath, $line);
                            }
                        }
                    }
                    // Draw white text on top
                    imagettftext($image, $fontSize, 0, $textX, $textY + $lineOffset, $white, $fontPath, $line);
                    $lineOffset += $fontSize + 5;
                }
            } else {
                // Fallback to built-in font if TTF not found
                $fontSize = 5;
                $fontHeight = 20;
                $fontWidth = 12;

                // Calculate text dimensions
                $lines = explode("\n", $watermarkText);
                $textWidth = max(array_map(function($line) use ($fontWidth) {
                    return strlen($line) * $fontWidth;
                }, $lines));

                // Position: left side
                $textX = 20;
                $textY = $height - ($fontHeight * count($lines)) - 20;

                // Draw each line with shadow effect
                $lineY = $textY;
                foreach ($lines as $line) {
                    // Draw black outline (shadow effect)
                    for ($offsetX = -3; $offsetX <= 3; $offsetX++) {
                        for ($offsetY = -3; $offsetY <= 3; $offsetY++) {
                            if ($offsetX != 0 || $offsetY != 0) {
                                imagestring($image, $fontSize, $textX + $offsetX, $lineY + $offsetY, $line, $black);
                            }
                        }
                    }
                    // Draw white text on top
                    imagestring($image, $fontSize, $textX, $lineY, $line, $white);
                    $lineY += $fontHeight;
                }
            }
        }

        // Save the (possibly watermarked) image to storage
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = "task-media/{$task->id}/" . $filename;

        // Create a temporary output file
        $outputPath = Storage::disk('local')->path('temp/' . $filename);
        imagejpeg($image, $outputPath, 95);
        imagedestroy($image);

        // Move to public storage
        Storage::disk('public')->put($path, file_get_contents($outputPath));
        Storage::disk('local')->delete('temp/' . $filename);
        Storage::disk('local')->delete($tempPath);
        TaskMedia::create([
            'task_id' => $task->id,
            'file_path' => $path,
            'type' => $validated['type'],
            'watermark_data' => $watermarkData,
            'taken_at' => now(),
        ]);

        return response()->json(['message' => 'Media uploaded successfully.', 'path' => $path]);
    }

    #[OA\Post(
        path: '/api/tasks/{id}/complete',
        summary: 'Mark task as completed with full details and inventory consumption',
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    required: ['end_lat', 'end_lng', 'installation_type', 'drop_bury_status', 'sidewalk_bore_status'],
                    properties: [
                        new OA\Property(property: 'end_lat', type: 'number', format: 'float', description: 'End location latitude (-90 to 90)', example: 40.7128),
                        new OA\Property(property: 'end_lng', type: 'number', format: 'float', description: 'End location longitude (-180 to 180)', example: -74.0060),
                        new OA\Property(property: 'installation_type', type: 'string', enum: ['aerial', 'underground', 'combination', 'mdu'], description: 'Installation method used', example: 'aerial'),
                        new OA\Property(property: 'drop_bury_status', type: 'boolean', description: 'Was drop bury completed?', example: true),
                        new OA\Property(property: 'sidewalk_bore_status', type: 'boolean', description: 'Was sidewalk bore performed?', example: false),
                        new OA\Property(property: 'ont_serial', type: 'string', nullable: true, description: 'ONT serial number', example: 'ONT123456'),
                        new OA\Property(property: 'eero_serial_1', type: 'string', nullable: true, description: 'First Eero device serial', example: 'EERO001'),
                        new OA\Property(property: 'eero_serial_2', type: 'string', nullable: true, description: 'Second Eero device serial', example: null),
                        new OA\Property(property: 'eero_serial_3', type: 'string', nullable: true, description: 'Third Eero device serial', example: null),
                        new OA\Property(property: 'tech_notes', type: 'string', nullable: true, description: 'Technician completion notes', example: 'Installation completed without issues'),
                        new OA\Property(
                            property: 'inventory_used',
                            type: 'array',
                            description: 'Inventory items consumed during task',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'item_id', type: 'integer', description: 'Inventory item ID', example: 1),
                                    new OA\Property(property: 'quantity', type: 'integer', description: 'Quantity consumed (positive integer)', example: 1),
                                ],
                                type: 'object'
                            ),
                            nullable: true,
                            example: [['item_id' => 1, 'quantity' => 1]]
                        ),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', nullable: true, description: 'Offline completion timestamp (ISO 8601)', example: '2026-01-19T14:30:00Z'),
                    ]
                )
            )
        ),
        tags: ['Tasks'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Task ID', schema: new OA\Schema(type: 'integer', example: 16))],
        responses: [
            new OA\Response(response: 200, description: 'Task completed successfully, inventory deducted'),
            new OA\Response(response: 403, description: 'Not authorized to complete this task'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function complete(CompleteTaskRequest $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);

        if ($task->assigned_tech_id !== $request->user()->id) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($task, $validated, $request) {
            $completionTime = isset($validated['timestamp']) ? Carbon::parse($validated['timestamp']) : now();

            // Update task
            $task->update([
                'status' => TaskStatus::Completed,
                'installation_type' => InstallationType::from($validated['installation_type']),
                'completion_date' => $completionTime,
            ]);

            // Update or create task detail
            TaskDetail::updateOrCreate(
                ['task_id' => $task->id],
                [
                    'ont_serial' => $validated['ont_serial'] ?? null,
                    'eero_serial_1' => $validated['eero_serial_1'] ?? null,
                    'eero_serial_2' => $validated['eero_serial_2'] ?? null,
                    'eero_serial_3' => $validated['eero_serial_3'] ?? null,
                    'drop_bury_status' => $validated['drop_bury_status'],
                    'sidewalk_bore_status' => $validated['sidewalk_bore_status'],
                    'end_lat' => $validated['end_lat'],
                    'end_lng' => $validated['end_lng'],
                    'end_time_actual' => $completionTime,
                    'tech_notes' => $validated['tech_notes'] ?? null,
                ]
            );

            // Process inventory consumption
            if (! empty($validated['inventory_used'])) {
                foreach ($validated['inventory_used'] as $item) {
                    // Record consumption
                    TaskInventoryConsumption::create([
                        'task_id' => $task->id,
                        'inventory_item_id' => $item['item_id'],
                        'quantity' => $item['quantity'],
                    ]);

                    // Deduct from wallet
                    $wallet = InventoryWallet::firstOrCreate(
                        ['user_id' => $request->user()->id, 'inventory_item_id' => $item['item_id']],
                        ['quantity' => 0]
                    );
                    $wallet->decrement('quantity', $item['quantity']);

                    // Create transaction log
                    InventoryTransaction::create([
                        'inventory_item_id' => $item['item_id'],
                        'source_user_id' => $request->user()->id,
                        'task_id' => $task->id,
                        'quantity' => -$item['quantity'],
                        'type' => InventoryTransactionType::Consumed,
                        'notes' => "Consumed on task #{$task->id}",
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Task completed successfully.']);
    }
}
