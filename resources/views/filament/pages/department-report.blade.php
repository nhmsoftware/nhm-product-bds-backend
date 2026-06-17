<x-filament-panels::page>
    <style>
         .choices__list--single .choices__item {
             white-space: nowrap !important;
             overflow: hidden !important;
             text-overflow: ellipsis !important;
             max-width: calc(100% - 24px) !important;
             display: inline-block !important;
             vertical-align: middle !important;
         }
    </style>
    <form wire:submit.prevent="applyFilters" class="space-y-6">
        {{ $this->form }}
        
        <div class="flex justify-end">
            <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">
                Áp dụng bộ lọc
            </x-filament::button>
        </div>
    </form>

    @php
        $departments = $this->getReportData();
    @endphp

    {{-- Bảng tổng hợp phòng ban --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 mt-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-800 dark:text-white">Bảng xếp hạng hiệu suất phòng ban</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500">Xếp hạng theo tổng doanh thu giao dịch thành công</p>
            </div>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400" style="min-width: 900px;">
                <thead class="text-xs text-gray-600 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-center w-14">Hạng</th>
                        <th class="px-5 py-3">Tên phòng ban</th>
                        <th class="px-4 py-3 text-center">Nhân sự</th>
                        <th class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-400">Doanh thu</th>
                        <th class="px-4 py-3 text-center">GD thành công</th>
                        <th class="px-4 py-3 text-center">Dẫn khách</th>
                        <th class="px-4 py-3 text-center">Gặp khách</th>
                        <th class="px-4 py-3 text-center">Referral</th>
                        <th class="px-4 py-3 text-center">Ngày công</th>
                        <th class="px-4 py-3 text-center">Vắng</th>
                        <th class="px-4 py-3 text-center w-28">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($departments as $index => $dept)
                        @php $rank = $index + 1; @endphp
                        <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3.5 text-center font-semibold text-gray-700 dark:text-gray-300">
                                {{ $rank }}
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-gray-900 dark:text-white">{{ $dept['department_name'] }}</td>
                            <td class="px-4 py-3.5 text-center font-medium text-gray-700 dark:text-gray-300">{{ $dept['total_employees'] }}</td>
                            <td class="px-4 py-3.5 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                {{ number_format($dept['total_revenue'] / 1_000_000_000, 2) }} tỷ
                            </td>
                            <td class="px-4 py-3.5 text-center font-semibold text-blue-600 dark:text-blue-400">{{ $dept['successful_transactions'] }}</td>
                            <td class="px-4 py-3.5 text-center">{{ $dept['site_tours'] }}</td>
                            <td class="px-4 py-3.5 text-center">{{ $dept['customer_meetings'] }}</td>
                            <td class="px-4 py-3.5 text-center">{{ $dept['referrals'] }}</td>
                            <td class="px-4 py-3.5 text-center text-sky-600 dark:text-sky-400">{{ $dept['working_days'] }}</td>
                            <td class="px-4 py-3.5 text-center text-rose-500">{{ $dept['fixed_schedule_absences'] }}</td>
                            <td class="px-4 py-3.5 text-center">
                                <button
                                    wire:click="selectDepartment('{{ $dept['department_name'] }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition whitespace-nowrap"
                                >
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Chi tiết
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-10 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-400">
                                    <svg class="w-10 h-10 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="text-sm">Chưa có dữ liệu báo cáo phòng ban.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Drill-down: Chi tiết nhân viên trong phòng ban --}}
    @if($this->selectedDepartmentDetail)
        @php $dd = $this->selectedDepartmentDetail; @endphp
        <div class="bg-white border border-primary-200 rounded-2xl shadow-md dark:bg-gray-800 dark:border-primary-700 mt-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-primary-100 dark:border-primary-800 flex items-center justify-between bg-primary-50 dark:bg-primary-900/20">
                <div>
                    <h3 class="text-base font-bold text-gray-800 dark:text-white">
                        Chi tiết phòng ban: <span class="text-primary-600 dark:text-primary-400">{{ $dd['department_name'] }}</span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $dd['total_employees'] }} nhân viên &bull;
                        Doanh thu: <strong class="text-emerald-600">{{ number_format($dd['total_revenue'] / 1_000_000_000, 2) }} tỷ</strong>
                    </p>
                </div>
                <button
                    wire:click="closeDepartmentDetail"
                    class="text-gray-400 hover:text-gray-700 dark:hover:text-white transition p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                    title="Đóng"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400" style="min-width: 850px;">
                    <thead class="text-xs text-gray-600 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Mã NV</th>
                            <th class="px-4 py-3">Họ tên</th>
                            <th class="px-4 py-3">Chức vụ</th>
                            <th class="px-4 py-3 text-right text-emerald-700">Doanh thu</th>
                            <th class="px-4 py-3 text-center">GD thành công</th>
                            <th class="px-4 py-3 text-center">Tour</th>
                            <th class="px-4 py-3 text-center">Gặp khách</th>
                            <th class="px-4 py-3 text-center">Referral</th>
                            <th class="px-4 py-3 text-center">Ngày công</th>
                            <th class="px-4 py-3 text-center">Vắng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($dd['employees'] as $emp)
                            <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-4 py-3 font-mono text-xs text-gray-400 dark:text-gray-500">{{ $emp['staff_code'] }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $emp['name'] }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $emp['job_position'] }}</td>
                                <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ number_format($emp['revenue'] / 1_000_000_000, 2) }} tỷ
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-blue-600 dark:text-blue-400">{{ $emp['successful_transactions'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $emp['site_tours'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $emp['customer_meetings'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $emp['referrals'] }}</td>
                                <td class="px-4 py-3 text-center text-sky-600 dark:text-sky-400">{{ $emp['working_days'] }}</td>
                                <td class="px-4 py-3 text-center text-rose-500">{{ $emp['absences'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-6 text-center text-gray-400 text-sm">
                                    Không tìm thấy nhân viên nào trong phòng ban này.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>
