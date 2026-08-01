@props(['title', 'heading', 'lead' => null])

<x-layouts.app :title="$title">
    <div class="auth">
        <div class="auth__panel" data-reveal="up">
            <a class="auth__brand" href="{{ route('home') }}">
                <x-logo class="brand__mark" />
                <span class="brand__name">{{ setting('shop_name', 'Ay Parçası') }}</span>
            </a>

            <h1 class="auth__title">{{ $heading }}</h1>

            @if ($lead)
                <p class="lead" style="font-size:.95rem">{{ $lead }}</p>
            @endif

            {{ $slot }}
        </div>

        <aside class="auth__aside" aria-hidden="true">
            <img src="{{ img_url(setting('hero_image'), 'https://images.unsplash.com/photo-1487070183336-b863922373d4?auto=format&fit=crop&w=1200&q=72') }}"
                 alt="" decoding="async">
            <div class="auth__aside-text">
                <p class="serif-em">“Bir çiçek, bir cümleden fazlasını söyler.”</p>
            </div>
        </aside>
    </div>
</x-layouts.app>
