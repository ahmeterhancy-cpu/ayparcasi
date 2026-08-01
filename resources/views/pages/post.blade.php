<x-layouts.app :title="$post->title" :description="$post->excerpt">

    <div class="wrap">
        <ol class="crumbs">
            <li><a href="{{ route('home') }}">Ana sayfa</a></li>
            <li><a href="{{ route('page.blog') }}">Günlük</a></li>
            <li aria-current="page">{{ $post->title }}</li>
        </ol>
    </div>

    <article>
        <header class="wrap wrap--narrow page-head">
            <span class="eyebrow">{{ $post->published_at?->translatedFormat('d F Y') }}</span>
            <h1 style="margin-block:.7rem 1rem" data-reveal="up" data-split="words">{{ $post->title }}</h1>
            @if ($post->excerpt)
                <p class="lead">{{ $post->excerpt }}</p>
            @endif
        </header>

        @if ($post->cover)
            <div class="wrap wrap--wide" data-scrub data-scrub-range="enter">
                <figure style="border-radius:var(--radius-lg);overflow:hidden;aspect-ratio:16/9;background:var(--sand)">
                    <img src="{{ img_url($post->cover) }}" alt="" decoding="async"
                         style="width:100%;height:100%;object-fit:cover;transform:translate3d(0,var(--shift,0),0) scale(1.1)"
                         data-parallax="-0.08">
                </figure>
            </div>
        @endif

        <div class="wrap wrap--narrow" style="padding-block:clamp(2.5rem,5vw,4rem) var(--sec)">
            <div class="prose">{!! nl2br(e($post->body)) !!}</div>
        </div>
    </article>

    @if ($more->isNotEmpty())
        <section class="section section--sand section--tight">
            <div class="wrap">
                <div class="section-head">
                    <div class="section-head__text">
                        <span class="eyebrow">Devamı</span>
                        <h2>Bunları da okuyun</h2>
                    </div>
                </div>

                <div class="journal" data-stagger="80">
                    @foreach ($more as $item)
                        <article class="journal__item">
                            <a href="{{ route('page.post', $item->slug) }}">
                                <figure>
                                    <img src="{{ img_url($item->cover, 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=900&q=70') }}"
                                         alt="" loading="lazy" decoding="async">
                                </figure>
                                <h3>{{ $item->title }}</h3>
                            </a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</x-layouts.app>
