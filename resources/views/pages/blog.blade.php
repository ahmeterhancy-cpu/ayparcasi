<x-layouts.app title="Günlük">

    <header class="wrap page-head">
        <div class="page-head__text">
            <span class="eyebrow">Günlük</span>
            <h1 data-reveal="up">Çiçekle yaşamak üzerine</h1>
            <p class="lead">Bakım ipuçları, mevsim notları ve dükkândan küçük hikâyeler.</p>
        </div>
    </header>

    <section class="wrap" style="padding-bottom:var(--sec)">
        @if ($posts->isEmpty())
            <div class="empty">
                <h2>Henüz yazı yok</h2>
                <p class="lead">Yakında burada olacağız.</p>
            </div>
        @else
            <div class="journal" data-stagger="90">
                @foreach ($posts as $post)
                    <article class="journal__item">
                        <a href="{{ route('page.post', $post->slug) }}">
                            <figure>
                                <img src="{{ img_url($post->cover, 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=900&q=70') }}"
                                     alt="" loading="lazy" decoding="async">
                            </figure>
                            <span class="journal__meta">{{ $post->published_at?->translatedFormat('d F Y') }}</span>
                            <h2 style="font-size:1.28rem;margin-block:.55rem .5rem">{{ $post->title }}</h2>
                            <p class="muted" style="font-size:.92rem">{{ Str::limit($post->excerpt, 120) }}</p>
                        </a>
                    </article>
                @endforeach
            </div>

            {{ $posts->links() }}
        @endif
    </section>

</x-layouts.app>
