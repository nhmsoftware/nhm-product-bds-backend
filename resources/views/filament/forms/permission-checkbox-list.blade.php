@php
    $state = $getState();
    $isManageAll = in_array('manage_all', (array) $state);
@endphp

<div wire:ignore.self>
    {{-- Manage All toggle --}}
    <div class="mb-4 flex items-center gap-3 rounded-lg bg-warning-50 dark:bg-warning-950 p-3 border border-warning-200 dark:border-warning-800">
        <label class="relative inline-flex cursor-pointer items-center">
            <input type="checkbox"
                   wire:change="toggleManageAll"
                   @checked($isManageAll)
                   class="peer sr-only">
            <div class="h-5 w-9 rounded-full bg-gray-300 after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-warning-500 after:peer-checked:translate-x-full"></div>
        </label>
        <div>
            <span class="text-sm font-semibold text-warning-700 dark:text-warning-300">Quản lý toàn hệ thống (manage_all)</span>
            <p class="text-xs text-warning-600 dark:text-warning-400">Kích hoạt tất cả quyền. Các quyền bên dưới sẽ bị vô hiệu hóa.</p>
        </div>
    </div>

    {{-- Permission groups by module --}}
    <div class="space-y-3">
        @foreach($getModulePermissions() as $module => $permissions)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                @php
                    $modulePermIds = collect($permissions)->pluck('id')->toArray();
                    $moduleChecked = collect($state)->filter(fn($id) => in_array($id, $modulePermIds))->count();
                    $moduleAllChecked = $moduleChecked === count($modulePermIds);
                    $modulePartial = $moduleChecked > 0 && !$moduleAllChecked;
                @endphp

                {{-- Module header --}}
                <div class="flex items-center gap-3 px-4 py-2.5 bg-gray-50 dark:bg-gray-800">
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox"
                               wire:change="toggleModule('{{ $module }}')"
                               @checked($moduleAllChecked)
                               @if($modulePartial) class="indeterminate" @endif
                               {{ $isManageAll ? 'disabled' : '' }}
                               class="peer sr-only">
                        <div class="h-5 w-5 rounded border-2 {{ $isManageAll ? 'border-gray-300 bg-gray-100' : ($moduleAllChecked ? 'border-warning-500 bg-warning-500' : ($modulePartial ? 'border-warning-500 bg-warning-200' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700')) }} after:absolute after:left-[5px] after:top-[1px] after:h-2.5 after:w-1.5 after:border-b-2 after:border-r-2 after:border-white after:rotate-45 after:content-[''] peer-checked:border-warning-500 peer-checked:bg-warning-500 @unless($moduleAllChecked) peer-checked:after:hidden @endunless"></div>
                    </label>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $getModuleLabel($module) }}</span>
                    <span class="text-xs text-gray-400">({{ $moduleChecked }}/{{ count($modulePermIds) }})</span>
                </div>

                {{-- Permissions row --}}
                <div class="flex flex-wrap gap-x-6 gap-y-2 px-4 py-3">
                    @foreach($permissions as $permission)
                        <label class="inline-flex items-center gap-2 cursor-pointer {{ $isManageAll ? 'opacity-50' : '' }}">
                            <input type="checkbox"
                                   wire:change="togglePermission('{{ $permission['id'] }}')"
                                   @checked(in_array($permission['id'], (array) $state))
                                   {{ $isManageAll ? 'disabled' : '' }}
                                   class="rounded border-gray-300 text-warning-600 focus:ring-warning-500 disabled:opacity-50">
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $permission['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
