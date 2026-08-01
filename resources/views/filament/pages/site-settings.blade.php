<x-filament-panels::page>
    <form wire:submit.prevent="save" class="fi-form">
        {{ $this->form }}

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                Kaydet
            </x-filament::button>

            <span class="fi-color-gray text-sm" wire:loading wire:target="save">
                Kaydediliyor…
            </span>
        </div>
    </form>
</x-filament-panels::page>
