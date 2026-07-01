@php
    $allPermissions = \App\Modules\Auth\Models\Permission::query()
        ->active()
        ->where('module', 'mobile')
        ->where('name', '!=', 'manage_all_mobile')
        ->ordered()
        ->get();
@endphp

<div x-data="{
    state: @entangle($getStatePath()),
    manageAll: @entangle('data.manage_all_mobile'),
    get isDisabled() {
        if (this.manageAll === true) return true;
        let toggleEl = document.querySelector('button[role=switch][id*=\'manage_all_mobile\']') 
            || document.querySelector('input[type=checkbox][id*=\'manage_all_mobile\']')
            || document.querySelector('[name*=\'manage_all_mobile\']');
        if (toggleEl) {
            if (toggleEl.getAttribute('role') === 'switch') {
                return toggleEl.getAttribute('aria-checked') === 'true';
            }
            return toggleEl.checked;
        }
        return false;
    },
    togglePermission(id) {
        if (this.isDisabled) return;
        let currentState = Array.isArray(this.state) ? this.state : [];
        if (currentState.includes(id)) {
            this.state = currentState.filter(x => x !== id);
        } else {
            this.state = [...currentState, id];
        }
    }
}" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <th class="p-4 text-sm font-semibold text-gray-700 dark:text-gray-200 w-2/3">Chức năng Mobile</th>
                    <th class="p-4 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center w-1/3">Kích hoạt</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($allPermissions as $perm)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="p-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $perm->label }}
                        </td>
                        <td class="p-4 text-center">
                            <input 
                                type="checkbox" 
                                :checked="Array.isArray(state) && state.includes('{{ $perm->id }}')"
                                @change="togglePermission('{{ $perm->id }}')"
                                :disabled="isDisabled"
                                class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-primary-600 shadow-sm focus:ring-primary-500 dark:focus:ring-offset-gray-800 disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed"
                            />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
