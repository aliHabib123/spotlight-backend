@php
use App\Models\Ad;

$videoId = null;

if (isset($videoUrl) && !empty($videoUrl)) {
    if (preg_match('/youtube\.com\/watch\?v=([\w-]+)/', $videoUrl, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtu\.be\/([\w-]+)/', $videoUrl, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtube\.com\/embed\/([\w-]+)/', $videoUrl, $matches)) {
        $videoId = $matches[1];
    }
}
@endphp

@if ($videoId)
    <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden">
        <iframe 
            src="https://www.youtube.com/embed/{{ $videoId }}" 
            title="YouTube video player" 
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen
            class="w-full h-full"
        ></iframe>
    </div>
@else
    <div class="flex items-center justify-center p-6 text-sm text-gray-500 border border-dashed rounded">
        <p>Enter a valid YouTube URL to see a preview</p>
    </div>
@endif
