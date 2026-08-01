<x-layouts.app title="Sıkça Sorulan Sorular">

    @push('head')
        @if ($faqs->isNotEmpty())
            <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqs->map(fn ($f) => [
                    '@type' => 'Question',
                    'name' => $f->question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f->answer],
                ])->values(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
            </script>
        @endif
    @endpush

    <header class="wrap page-head">
        <div class="page-head__text">
            <span class="eyebrow">Yardım</span>
            <h1 data-reveal="up">Sıkça sorulan sorular</h1>
        </div>
    </header>

    <section class="wrap wrap--narrow" style="padding-bottom:var(--sec)">
        @if ($faqs->isEmpty())
            <div class="empty">
                <h2>Henüz soru eklenmemiş</h2>
                <p class="lead">Aklınıza takılan bir şey varsa WhatsApp'tan sorabilirsiniz.</p>
                <a class="btn btn--wa" href="{{ wa_link('Merhaba, bir sorum var.') }}" target="_blank" rel="noopener">
                    <x-ay-icon name="whatsapp" :filled="true" /> Soru sor
                </a>
            </div>
        @else
            <div class="acc" data-accordion>
                @foreach ($faqs as $i => $faq)
                    <div class="acc__item {{ $i === 0 ? 'is-open' : '' }}" data-accordion-item>
                        <button class="acc__trigger" type="button" data-accordion-trigger
                                aria-expanded="{{ $i === 0 ? 'true' : 'false' }}">
                            {{ $faq->question }}<span class="acc__sign" aria-hidden="true"></span>
                        </button>
                        <div class="acc__panel" data-accordion-panel>
                            <div>{!! nl2br(e($faq->answer)) !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

</x-layouts.app>
