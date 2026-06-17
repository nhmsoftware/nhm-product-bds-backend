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
        $report = $this->getReportData();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <!-- Tổng hoa hồng -->
        <div class="flex items-center p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Tổng hoa hồng phát sinh</p>
                <p class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ number_format($report['total_commission']) }} đ</p>
            </div>
        </div>

        <!-- Đã chi trả -->
        <div class="flex items-center p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="p-3 mr-4 text-emerald-500 bg-emerald-100 rounded-full dark:text-emerald-100 dark:bg-emerald-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Đã quyết toán / Chi trả</p>
                <p class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ number_format($report['total_paid']) }} đ</p>
            </div>
        </div>

        <!-- Chưa chi trả -->
        <div class="flex items-center p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="p-3 mr-4 text-amber-500 bg-amber-100 rounded-full dark:text-amber-100 dark:bg-amber-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Chờ thanh toán / Chưa trả</p>
                <p class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ number_format($report['total_unpaid']) }} đ</p>
            </div>
        </div>
    </div>

    <!-- Grouped by Referrer -->
    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 mt-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Thống kê hoa hồng theo nhân viên giới thiệu</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Mã NV</th>
                        <th class="px-4 py-3">Nhân viên giới thiệu</th>
                        <th class="px-4 py-3 text-center">Số lượt được tính</th>
                        <th class="px-4 py-3 text-right">Tổng hoa hồng</th>
                        <th class="px-4 py-3 text-right">Đã thanh toán</th>
                        <th class="px-4 py-3 text-right">Chưa thanh toán</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['by_referrer'] as $row)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">{{ $row['referrer_code'] }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['referrer_name'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $row['count'] }}</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">{{ number_format($row['total_amount']) }} đ</td>
                            <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 font-semibold">{{ number_format($row['paid_amount']) }} đ</td>
                            <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400 font-semibold">{{ number_format($row['unpaid_amount']) }} đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-center text-gray-400">Không có dữ liệu hoa hồng phù hợp</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detailed commissions list -->
    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 mt-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Chi tiết các khoản hoa hồng gần đây (Tối đa 50 khoản mới nhất)</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Nhân viên giới thiệu</th>
                        <th class="px-4 py-3">Chi nhánh</th>
                        <th class="px-4 py-3">Phòng ban</th>
                        <th class="px-4 py-3">Người được giới thiệu</th>
                        <th class="px-4 py-3 text-right">Số tiền</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3 text-center">Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['details'] as $item)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $item['referrer_name'] }}</td>
                            <td class="px-4 py-3">{{ $item['referrer_area'] ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $item['referrer_dept'] ?: '-' }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $item['referee_name'] }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 font-semibold">{{ number_format($item['amount']) }} đ</td>
                            <td class="px-4 py-3 text-center">
                                @if($item['status'] == 2)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300">
                                        Đã chi trả
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">
                                        Chờ thanh toán
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">{{ \Illuminate\Support\Carbon::parse($item['created_at'])->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-3 text-center text-gray-400">Không tìm thấy khoản hoa hồng nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
