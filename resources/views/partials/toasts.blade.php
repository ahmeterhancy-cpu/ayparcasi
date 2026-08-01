@if (session('success') || session('error') || session('info'))
    <div class="toasts" role="status" aria-live="polite">
        @foreach (['success' => '', 'info' => 'toast--info', 'error' => 'toast--error'] as $key => $class)
            @if (session($key))
                <div class="toast {{ $class }}" data-toast>
                    <span style="flex:1">{{ session($key) }}</span>
                    <button type="button" data-toast-close aria-label="Kapat" style="opacity:.5">
                        <x-ay-icon name="close" style="width:16px;height:16px" />
                    </button>
                </div>
            @endif
        @endforeach
    </div>
@endif
