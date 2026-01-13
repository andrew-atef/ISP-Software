<div class="w-full h-64 rounded-lg overflow-hidden border border-gray-300 dark:border-gray-700">
    @if($getRecord()->current_lat && $getRecord()->current_lng)
        <iframe
            width="100%"
            height="100%"
            frameborder="0"
            style="border:0"
            src="https://maps.google.com/maps?q={{ $getRecord()->current_lat }},{{ $getRecord()->current_lng }}&hl=en&z=15&output=embed"
            allowfullscreen>
        </iframe>
    @else
        <div class="flex items-center justify-center h-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
            No location data available
        </div>
    @endif
</div>
