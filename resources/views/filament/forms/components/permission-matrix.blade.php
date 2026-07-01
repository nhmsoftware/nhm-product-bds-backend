@php
    $allPermissions = \App\Modules\Auth\Models\Permission::query()
        ->active()
        ->where('name', '!=', 'manage_all')
        ->where('module', '!=', 'mobile')
        ->ordered()
        ->get();

    $modules = [
        'branch' => 'Chi nhánh',
        'department' => 'Phòng ban',
        'employee' => 'Nhân sự',
        'recruitment' => 'Tuyển dụng',
        'leave' => 'Nghỉ phép',
        'contract' => 'Hợp đồng lao động',
        'warehouse' => 'Kho hàng',
        'activity' => 'Lịch sử hoạt động',
        'attendance' => 'Chấm công',
        'ranking' => 'Xếp hạng',
        'dashboard' => 'Dashboard',
        'news' => 'Tin tức',
    ];

    $actions = [
        'view' => 'Xem',
        'create' => 'Tạo',
        'edit' => 'Sửa',
        'delete' => 'Xóa',
        'approve' => 'Duyệt / Khác',
    ];
@endphp

<div x-data="{
    state: @entangle($getStatePath()),
    manageAll: @entangle('data.manage_all'),
    get isDisabled() {
        if (this.manageAll === true) return true;
        let toggleEl = document.querySelector('button[role=switch][id*=\'manage_all\']') 
            || document.querySelector('input[type=checkbox][id*=\'manage_all\']')
            || document.querySelector('[name*=\'manage_all\']');
        if (toggleEl) {
            if (toggleEl.getAttribute('role') === 'switch') {
                return toggleEl.getAttribute('aria-checked') === 'true';
            }
            return toggleEl.checked;
        }
        return false;
    },
    isAllChecked(module) {
        let modulePerms = this.getModulePermIds(module);
        if (modulePerms.length === 0) return false;
        let currentState = Array.isArray(this.state) ? this.state : [];
        return modulePerms.every(id => currentState.includes(id));
    },
    toggleModule(module) {
        if (this.isDisabled) return;
        let modulePerms = this.getModulePermIds(module);
        let allChecked = this.isAllChecked(module);
        let currentState = Array.isArray(this.state) ? this.state : [];
        if (allChecked) {
            this.state = currentState.filter(id => !modulePerms.includes(id));
        } else {
            let newState = [...currentState];
            modulePerms.forEach(id => {
                if (!newState.includes(id)) {
                    newState.push(id);
                }
            });
            this.state = newState;
        }
    },
    togglePermission(id) {
        if (this.isDisabled) return;
        let currentState = Array.isArray(this.state) ? this.state : [];
        if (currentState.includes(id)) {
            this.state = currentState.filter(x => x !== id);
        } else {
            this.state = [...currentState, id];
        }
    },
    getModulePermIds(module) {
        let ids = [];
        @foreach ($allPermissions as $perm)
            if ('{{ $perm->module }}' === module) {
                ids.push('{{ $perm->id }}');
            }
        @endforeach
        return ids;
    }
}" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <th class="p-4 text-sm font-semibold text-gray-700 dark:text-gray-200 w-1/4">Chức năng</th>
                    <th class="p-4 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center w-24">Tất cả</th>
                    @foreach ($actions as $action => $label)
                        <th class="p-4 text-sm font-semibold text-gray-700 dark:text-gray-200 text-center">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($modules as $moduleKey => $moduleLabel)
                    @php
                        $modulePerms = $allPermissions->where('module', $moduleKey);
                    @endphp
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="p-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $moduleLabel }}
                        </td>
                        <td class="p-4 text-center">
                            <input 
                                type="checkbox" 
                                :checked="isAllChecked('{{ $moduleKey }}')"
                                @change="toggleModule('{{ $moduleKey }}')"
                                :disabled="isDisabled"
                                class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 shadow-sm focus:ring-primary-500 dark:focus:ring-offset-gray-800 disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed"
                            />
                        </td>
                        @foreach ($actions as $actionKey => $actionLabel)
                            @php
                                $perm = null;
                                if ($actionKey === 'approve') {
                                    $perm = $modulePerms->first(function ($p) {
                                        return str_starts_with($p->name, 'approve_') || 
                                               str_starts_with($p->name, 'checkin_') || 
                                               str_starts_with($p->name, 'grade_') || 
                                               str_starts_with($p->name, 'manage_') ||
                                               (!in_array(explode('_', $p->name)[0], ['view', 'create', 'edit', 'delete']));
                                    });
                                } else {
                                    $perm = $modulePerms->first(function ($p) use ($actionKey) {
                                        return str_starts_with($p->name, "{$actionKey}_");
                                    });
                                }
                            @endphp
                            <td class="p-4 text-center">
                                @if ($perm)
                                    <input 
                                        type="checkbox" 
                                        :checked="Array.isArray(state) && state.includes('{{ $perm->id }}')"
                                        @change="togglePermission('{{ $perm->id }}')"
                                        :disabled="isDisabled"
                                        class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 shadow-sm focus:ring-primary-500 dark:focus:ring-offset-gray-800 disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed"
                                    />
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-600">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
