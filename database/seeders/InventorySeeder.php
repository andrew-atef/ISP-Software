<?php

namespace Database\Seeders;

use App\Enums\InventoryItemType;
use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Tracked Items (is_tracked = true):
     * - Major devices that are counted during task completion
     * - EERO Pro 7, EERO 7 MAX
     * - Nokia ONT (XS-010X-Q)
     *
     * Untracked Items (is_tracked = false):
     * - Consumables and support items that don't require per-task tracking
     * - Cables, squids, outdoor hardware, OFS items, brackets, patch cords
     */
    public function run(): void
    {
        // =====================
        // TRACKED ITEMS (is_tracked = true)
        // =====================
        InventoryItem::firstOrCreate(
            ['sku' => 'EERO-PRO-7'],
            [
                'name' => 'EERO Pro 7',
                'type' => InventoryItemType::Indoor,
                'description' => 'High-performance mesh WiFi router (Pro 7).',
                'is_tracked' => true,
            ]
        );

        InventoryItem::firstOrCreate(
            ['sku' => 'EERO-7-MAX'],
            [
                'name' => 'EERO 7 MAX',
                'type' => InventoryItemType::Indoor,
                'description' => 'Premium mesh WiFi router (7 MAX).',
                'is_tracked' => true,
            ]
        );

        InventoryItem::firstOrCreate(
            ['sku' => 'NOKIA-ONT-XS-010X-Q'],
            [
                'name' => 'Nokia ONT (XS-010X-Q)',
                'type' => InventoryItemType::Indoor,
                'description' => 'Optical Network Terminal (Nokia XS-010X-Q).',
                'is_tracked' => true,
            ]
        );

        // =====================
        // UNTRACKED ITEMS (is_tracked = false)
        // =====================

        // CABLES
        InventoryItem::firstOrCreate(
            ['sku' => 'FIBER-TERM-TAIL'],
            [
                'name' => 'Fiber Cable - Term Tail',
                'type' => InventoryItemType::Outdoor,
                'description' => 'Fiber termination tail cable.',
                'is_tracked' => false,
            ]
        );

        InventoryItem::firstOrCreate(
            ['sku' => 'DROP-CBL-WHT'],
            [
                'name' => 'Drop Cable - WHT',
                'type' => InventoryItemType::Outdoor,
                'description' => 'White drop cable for external runs.',
                'is_tracked' => false,
            ]
        );

        InventoryItem::firstOrCreate(
            ['sku' => 'PATCH-CAT6-1M'],
            [
                'name' => 'Ethernet Patch Cord - Cat6',
                'type' => InventoryItemType::Indoor,
                'description' => '1-meter Cat6 Ethernet patch cord.',
                'is_tracked' => false,
            ]
        );

        // SQUIDS & CONNECTORS
        InventoryItem::firstOrCreate(
            ['sku' => 'MST-SQUID'],
            [
                'name' => 'MST Squid',
                'type' => InventoryItemType::Outdoor,
                'description' => 'Multi-use splice terminal squid.',
                'is_tracked' => false,
            ]
        );

        // OUTDOOR HARDWARE
        InventoryItem::firstOrCreate(
            ['sku' => 'NID-OUTDOOR'],
            [
                'name' => 'Outdoor NID',
                'type' => InventoryItemType::Outdoor,
                'description' => 'Network Interface Device for outdoor installations.',
                'is_tracked' => false,
            ]
        );

        InventoryItem::firstOrCreate(
            ['sku' => 'FLAGS-MARKERS'],
            [
                'name' => 'Flags & Markers',
                'type' => InventoryItemType::Outdoor,
                'description' => 'Safety flags and dig markers.',
                'is_tracked' => false,
            ]
        );

        // BRACKETS & FASTENERS
        InventoryItem::firstOrCreate(
            ['sku' => 'BRACKET-WALL'],
            [
                'name' => 'Wall Mount Bracket',
                'type' => InventoryItemType::Indoor,
                'description' => 'Standard wall mounting bracket for indoor devices.',
                'is_tracked' => false,
            ]
        );

        InventoryItem::firstOrCreate(
            ['sku' => 'BRACKET-POLE'],
            [
                'name' => 'Pole Mount Bracket',
                'type' => InventoryItemType::Outdoor,
                'description' => 'Pole mounting bracket for outdoor units.',
                'is_tracked' => false,
            ]
        );

        // OFS SUPPLIES
        InventoryItem::firstOrCreate(
            ['sku' => 'OFS-SPOOL'],
            [
                'name' => 'OFS Spool',
                'type' => InventoryItemType::Outdoor,
                'description' => 'Optical Fiber Splice (OFS) fiber spool.',
                'is_tracked' => false,
            ]
        );

        InventoryItem::firstOrCreate(
            ['sku' => 'OFS-GLUE'],
            [
                'name' => 'OFS Splice Glue',
                'type' => InventoryItemType::Outdoor,
                'description' => 'Optical fiber splice adhesive/glue.',
                'is_tracked' => false,
            ]
        );

        // MISCELLANEOUS
        InventoryItem::firstOrCreate(
            ['sku' => 'CABLE-TIES'],
            [
                'name' => 'Cable Ties & Clips',
                'type' => InventoryItemType::Outdoor,
                'description' => 'Assorted cable ties and management clips.',
                'is_tracked' => false,
            ]
        );

        InventoryItem::firstOrCreate(
            ['sku' => 'DESICCANT-PKG'],
            [
                'name' => 'Desiccant Packs',
                'type' => InventoryItemType::Outdoor,
                'description' => 'Moisture control desiccant packets.',
                'is_tracked' => false,
            ]
        );
    }
}
