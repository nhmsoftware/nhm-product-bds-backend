<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $statePath = $getStatePath();
        $mapsField = $getGoogleMapsUrlField();
        $latField = $getLatitudeField();
        $lngField = $getLongitudeField();
        
        // Resolve sibling field paths
        $mapsStatePath = null;
        if ($mapsField) {
            $parts = explode('.', $statePath);
            array_pop($parts);
            $parts[] = $mapsField;
            $mapsStatePath = implode('.', $parts);
        }
        
        $latStatePath = null;
        if ($latField) {
            $parts = explode('.', $statePath);
            array_pop($parts);
            $parts[] = $latField;
            $latStatePath = implode('.', $parts);
        }

        $lngStatePath = null;
        if ($lngField) {
            $parts = explode('.', $statePath);
            array_pop($parts);
            $parts[] = $lngField;
            $lngStatePath = implode('.', $parts);
        }
        
        $apiKey = config('services.goong.api_key') ?? env('GOONG_API_KEY');
    @endphp

    <div
        x-data="{
            state: $wire.entangle('{{ $statePath }}'),
            suggestions: [],
            showDropdown: false,
            isLoading: false,
            apiKey: '{{ $apiKey }}',
            search(query) {
                if (!this.apiKey) return;
                if (!query || query.length < 2) {
                    this.suggestions = [];
                    return;
                }
                this.isLoading = true;
                fetch('https://rsapi.goong.io/Place/AutoComplete?api_key=' + this.apiKey + '&input=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.predictions) {
                            this.suggestions = data.predictions;
                            this.showDropdown = true;
                        }
                    })
                    .catch(err => console.error('Goong Autocomplete error:', err))
                    .finally(() => {
                        this.isLoading = false;
                    });
            },
            select(prediction) {
                this.state = prediction.description;
                this.showDropdown = false;
                
                if (prediction.place_id && this.apiKey) {
                    fetch('https://rsapi.goong.io/Place/Detail?api_key=' + this.apiKey + '&place_id=' + prediction.place_id)
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.result && data.result.geometry) {
                                const lat = data.result.geometry.location.lat;
                                const lng = data.result.geometry.location.lng;
                                
                                @if ($mapsStatePath)
                                $wire.set('{{ $mapsStatePath }}', 'https://www.google.com/maps/search/?api=1&query=' + lat + ',' + lng);
                                @endif
                                
                                @if ($latStatePath)
                                $wire.set('{{ $latStatePath }}', lat);
                                @endif

                                @if ($lngStatePath)
                                $wire.set('{{ $lngStatePath }}', lng);
                                @endif
                            }
                        })
                        .catch(err => console.error('Goong Detail error:', err));
                }
            }
        }"
        x-on:click.away="showDropdown = false"
        class="relative w-full"
    >
        <x-filament::input.wrapper :valid="!$errors->has($statePath)">
            <x-filament::input
                type="text"
                x-model.debounce.300ms="state"
                x-on:input="search($event.target.value)"
                x-on:focus="if (suggestions.length > 0) showDropdown = true"
                placeholder="{{ $getPlaceholder() ?? 'Nhập địa chỉ hoặc tên vị trí để tìm kiếm...' }}"
                :disabled="$isDisabled()"
                :required="$isRequired()"
                class="w-full"
            />
            @if ($apiKey)
                <x-slot name="suffix">
                    <div class="flex items-center pr-2" x-show="isLoading" style="display: none;">
                        <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </x-slot>
            @endif
        </x-filament::input.wrapper>

        <!-- Warning check API Key -->
        @if (!$apiKey)
            <div class="mt-1.5 text-xs text-amber-600 dark:text-amber-400 flex items-center gap-1">
                <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <span>Chưa cấu hình GOONG_API_KEY trong file .env để tự động tìm kiếm.</span>
            </div>
        @endif

        <!-- Dropdown Suggestions -->
        <div
            x-show="showDropdown && suggestions.length > 0"
            class="absolute x-transition z-50 w-full mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700"
            style="display: none;"
        >
            <template x-for="item in suggestions" :key="item.place_id">
                <button
                    type="button"
                    x-on:click="select(item)"
                    class="w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 focus:bg-gray-50 dark:focus:bg-gray-700 text-sm text-gray-700 dark:text-gray-200 flex items-start gap-2.5 transition-colors duration-150"
                >
                    <!-- Pin Icon -->
                    <svg class="h-4 w-4 mt-0.5 text-gray-400 dark:text-gray-500 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    <span x-text="item.description" class="line-clamp-2"></span>
                </button>
            </template>
        </div>
    </div>
</x-dynamic-component>
