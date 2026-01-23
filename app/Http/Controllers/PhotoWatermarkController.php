<?php

namespace App\Http\Controllers;

use App\Models\TaskMedia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoWatermarkController extends Controller
{
    public function show($id)
    {
        $media = TaskMedia::findOrFail($id);
        $imagePath = Storage::disk('public')->path($media->file_path);

        // Load image using GD directly
        $image = imagecreatefromjpeg($imagePath);
        if ($image === false) {
            // Try PNG
            $image = imagecreatefrompng($imagePath);
        }
        if ($image === false) {
            abort(404, 'Image not found');
        }

        // Add watermark if watermark_data exists
        if ($media->watermark_data) {
            // watermark_data is already an array (from model casting)
            $watermarkData = is_array($media->watermark_data)
                ? $media->watermark_data
                : json_decode($media->watermark_data, true);

            // Format the text
            $timestamp = $watermarkData['timestamp'] ?? '';
            $location = $watermarkData['location'] ?? '';

            $watermarkText = $timestamp . "\n" . $location;

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
                $textY = $height - 160;

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

                // Draw each line
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

        // Output image
        header('Content-Type: image/jpeg');
        imagejpeg($image, null, 90);
        imagedestroy($image);
        exit;
    }
}
