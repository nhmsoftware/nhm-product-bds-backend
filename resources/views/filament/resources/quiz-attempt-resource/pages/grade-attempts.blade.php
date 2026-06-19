<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-3">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-check">
                Lưu kết quả chấm bài
            </x-filament::button>

            <x-filament::button
                tag="a"
                :href="$this->getResource()::getUrl('index')"
                color="gray"
                size="lg"
                icon="heroicon-o-arrow-left"
            >
                Quay lại
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
