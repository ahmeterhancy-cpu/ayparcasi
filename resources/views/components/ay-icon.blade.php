@props(['name', 'filled' => false])

@php
    $paths = [
        'cart' => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h2.2l2.4 12.2a1.6 1.6 0 0 0 1.6 1.3h9a1.6 1.6 0 0 0 1.6-1.3L21 7H5.2"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M20.5 20.5 16.5 16.5"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
        'user' => '<circle cx="12" cy="8" r="3.8"/><path d="M4.5 20.5a7.5 7.5 0 0 1 15 0"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'check' => '<path d="M4 12.5l5.2 5.2L20 7"/>',
        'arrow-right' => '<path d="M4 12h15M13 6l6 6-6 6"/>',
        'arrow-down' => '<path d="M12 4v15M6 13l6 6 6-6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'star' => '<path d="M12 3.2l2.6 5.4 5.9.8-4.3 4.1 1 5.9-5.2-2.8-5.2 2.8 1-5.9L3.5 9.4l5.9-.8z"/>',
        'truck' => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>',
        'shield' => '<path d="M12 3l7 3v5.5c0 4.3-3 8.2-7 9.5-4-1.3-7-5.2-7-9.5V6z"/><path d="M9 12l2.2 2.2L15.5 10"/>',
        'leaf' => '<path d="M4 20c0-8 5-13 16-13 0 9-5 13-11 13a5 5 0 0 1-5-5z"/><path d="M9.5 14.5C12 12 15 10.5 18 10"/>',
        'phone' => '<path d="M5 3h3.6l1.6 4.4-2.1 1.5a12.5 12.5 0 0 0 6 6l1.5-2.1L20 14.4V18a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 3 5.2 2 2 0 0 1 5 3z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6.5 12 12.5l8.5-6"/>',
        'pin' => '<path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
        'gift' => '<rect x="3" y="9" width="18" height="12" rx="1.6"/><path d="M3 13h18M12 9v12"/><path d="M12 9S10.5 4 8 4a2.4 2.4 0 0 0 0 5zM12 9s1.5-5 4-5a2.4 2.4 0 0 1 0 5z"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'card' => '<rect x="2.5" y="5" width="19" height="14" rx="2.2"/><path d="M2.5 10h19"/>',
        'cash' => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/>',
        'bank' => '<path d="M3 10 12 4l9 6"/><path d="M5 10v9M19 10v9M9.5 10v9M14.5 10v9M3 20h18"/>',
        'sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/>',
        'heart' => '<path d="M12 20s-7-4.4-7-9.3A4.2 4.2 0 0 1 12 8a4.2 4.2 0 0 1 7 2.7C19 15.6 12 20 12 20z"/>',
        'whatsapp' => '<path d="M12.04 2.5a9.4 9.4 0 0 0-8.1 14.1L2.5 21.5l5-1.35A9.4 9.4 0 1 0 12.04 2.5z"/><path d="M8.9 7.9c.2-.45.42-.46.6-.47h.5c.17 0 .4-.06.63.48l.85 2.05c.07.17.12.37.02.57l-.4.6c-.1.14-.2.3-.08.51.5.87 1.13 1.53 2.03 2.05.2.12.34.1.47-.03l.55-.63c.15-.17.3-.14.5-.07l1.98.94c.2.1.34.14.4.22.05.1.05.55-.14 1.08-.2.53-1.16 1.03-1.6 1.07-.4.04-.83.06-1.34-.09a11.7 11.7 0 0 1-4.55-3.1c-1.28-1.5-1.77-2.75-1.86-3.2-.1-.45.08-.9.3-1.2z"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1.1" fill="currentColor" stroke="none"/>',
        'facebook' => '<path d="M14.5 8.5H17V5h-2.6C12 5 10.6 6.5 10.6 8.9v1.9H8v3.5h2.6V21h3.6v-6.7h2.5l.4-3.5h-2.9V9.4c0-.6.2-.9.8-.9z"/>',
        'tiktok' => '<path d="M14.5 3v10.6a3.1 3.1 0 1 1-2.6-3.05"/><path d="M14.5 3c.4 2.3 1.9 3.8 4.3 4.1"/>',
        'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-5A8 8 0 1 1 21 12z"/>',
        'flower' => '<circle cx="12" cy="9" r="2.2"/><path d="M12 6.8c0-2 1-3.3 2.6-3.3S17 5 15.4 6.6 12 8.8 12 6.8zM12 6.8c0-2-1-3.3-2.6-3.3S7 5 8.6 6.6 12 8.8 12 6.8zM14.2 9c2 0 3.3 1 3.3 2.6S15.4 14 13.8 12.4 12.2 9 14.2 9zM9.8 9c-2 0-3.3 1-3.3 2.6S8.6 14 10.2 12.4 11.8 9 9.8 9z"/><path d="M12 12.5V21"/>',
    ];
@endphp

<svg
    {{ $attributes->merge(['aria-hidden' => 'true', 'focusable' => 'false']) }}
    viewBox="0 0 24 24"
    fill="{{ $filled ? 'currentColor' : 'none' }}"
    stroke="{{ $filled ? 'none' : 'currentColor' }}"
    stroke-width="1.6"
    stroke-linecap="round"
    stroke-linejoin="round"
>{!! $paths[$name] ?? '' !!}</svg>
