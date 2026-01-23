@php
    use Illuminate\Support\Facades\Storage;

    // Handle both callable and direct collection
    if (is_callable($photos)) {
        $photos = $photos();
    }
@endphp

@if($photos && count($photos) > 0)
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
        @foreach($photos as $photo)
            @php
                $url = asset('storage/' . $photo->file_path);
            @endphp
            <a href="{{ $url }}"
               class="group relative overflow-hidden rounded-lg bg-gray-200"
               data-fslightbox="gallery-{{ $photoType }}"
               data-alt="{{ $photo->type ?? $photoType }} photo">
                <img
                    src="{{ $url }}"
                    alt="{{ $photo->type ?? $photoType }} photo"
                    class="h-48 w-full object-cover transition-transform duration-300 group-hover:scale-110"
                    loading="lazy">
                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 transition-all duration-300 group-hover:bg-opacity-30">
                    <svg class="h-8 w-8 text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path>
                    </svg>
                </div>
                <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 px-2 py-1 text-xs text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                    {{ $photo->taken_at?->format('M d, Y H:i') ?? 'No date' }}
                </div>
            </a>
        @endforeach
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fslightbox@3.4.0/index.js"></script>
@else
    <div class="rounded-lg border-2 border-dashed border-gray-300 px-6 py-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <p class="mt-4 text-sm text-gray-600">No {{ $photoType }} photos uploaded yet.</p>
    </div>
@endif
