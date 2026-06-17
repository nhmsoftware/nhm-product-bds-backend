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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <!-- Card Doanh Thu -->
        <div class="flex items-center p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="p-3 mr-4 text-emerald-500 bg-emerald-100 rounded-full dark:text-emerald-100 dark:bg-emerald-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Tổng doanh thu thực tế</p>
                <p class="text-3xl font-semibold text-gray-700 dark:text-gray-200">{{ number_format($report['total_revenue']) }} đ</p>
            </div>
        </div>

        <!-- Card Số Giao Dịch -->
        <div class="flex items-center p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Tổng số giao dịch</p>
                <p class="text-3xl font-semibold text-gray-700 dark:text-gray-200">{{ $report['total_transactions'] }} giao dịch</p>
            </div>
        </div>
    </div>

    <!-- Biểu đồ Doanh Thu -->
    <div x-data="{
        activeTab: 'month',
        initChart() {
            const canvas = document.getElementById('revenueChartCanvas');
            if (!canvas) return;

            // Destroy previous chart instance if it exists to avoid memory leak and canvas already in use error
            if (typeof Chart !== 'undefined') {
                const existingChart = Chart.getChart(canvas);
                if (existingChart) {
                    existingChart.destroy();
                }
            }

            const ctx = canvas.getContext('2d');
            const chartData = $wire.chartData || @js($report['chart_data']);
            let labels = [];
            let data = [];
            let labelStr = '';

            if (this.activeTab === 'month') {
                labels = chartData.by_month.labels;
                data = chartData.by_month.values;
                labelStr = 'Doanh thu theo Tháng (đ)';
            } else if (this.activeTab === 'quarter') {
                labels = chartData.by_quarter.labels;
                data = chartData.by_quarter.values;
                labelStr = 'Doanh thu theo Quý (đ)';
            } else {
                labels = chartData.by_year.labels;
                data = chartData.by_year.values;
                labelStr = 'Doanh thu theo Năm (đ)';
            }

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: labelStr,
                        data: data,
                        backgroundColor: 'rgba(16, 185, 129, 0.2)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 2,
                        borderRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000000) return (value / 1000000000).toFixed(1) + ' tỷ';
                                    if (value >= 1000000) return (value / 1000000).toFixed(0) + ' triệu';
                                    return value.toLocaleString('vi-VN') + ' đ';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let val = context.raw;
                                    return context.dataset.label + ': ' + val.toLocaleString('vi-VN') + ' đ';
                                }
                            }
                        }
                    }
                }
            });
        }
    }"
    x-init="
        if (typeof Chart === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = () => { initChart(); };
            document.head.appendChild(script);
        } else {
            initChart();
        }
        $watch('activeTab', () => initChart());
        $watch('$wire.chartData', () => initChart());
    "
    class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 mt-6"
    wire:ignore
    >
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Biểu đồ thống kê doanh thu</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Xem xu hướng doanh thu theo mốc thời gian</p>
            </div>
            
            <div class="flex rounded-lg bg-gray-100 p-1 dark:bg-gray-700">
                <button @click="activeTab = 'month'" :class="activeTab === 'month' ? 'bg-white shadow dark:bg-gray-600 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'" class="px-4 py-1.5 text-xs font-semibold rounded-md transition">
                    Tháng
                </button>
                <button @click="activeTab = 'quarter'" :class="activeTab === 'quarter' ? 'bg-white shadow dark:bg-gray-600 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'" class="px-4 py-1.5 text-xs font-semibold rounded-md transition">
                    Quý
                </button>
                <button @click="activeTab = 'year'" :class="activeTab === 'year' ? 'bg-white shadow dark:bg-gray-600 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'" class="px-4 py-1.5 text-xs font-semibold rounded-md transition">
                    Năm
                </button>
            </div>
        </div>

        <div class="h-80 relative">
            <canvas id="revenueChartCanvas" class="w-full h-full"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <!-- Doanh thu theo phòng ban -->
        <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Doanh thu theo phòng ban</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Phòng ban</th>
                            <th class="px-4 py-3 text-right">Doanh thu</th>
                            <th class="px-4 py-3 text-center">Số GD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['by_department'] as $item)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $item['department_name'] }}</td>
                                <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 font-semibold">{{ number_format($item['revenue']) }} đ</td>
                                <td class="px-4 py-3 text-center">{{ $item['transactions_count'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-center text-gray-400">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Doanh thu theo dự án -->
        <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Doanh thu theo dự án / Khu đất</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Dự án</th>
                            <th class="px-4 py-3 text-right">Doanh thu</th>
                            <th class="px-4 py-3 text-center">Số GD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['by_project'] as $item)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $item['project_name'] }}</td>
                                <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 font-semibold">{{ number_format($item['revenue']) }} đ</td>
                                <td class="px-4 py-3 text-center">{{ $item['transactions_count'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-center text-gray-400">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Doanh thu theo nhân viên -->
        <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Top nhân viên doanh số</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Nhân viên</th>
                            <th class="px-4 py-3 text-right">Doanh thu</th>
                            <th class="px-4 py-3 text-center">Số GD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['by_employee'] as $item)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $item['user_name'] }}</td>
                                <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 font-semibold">{{ number_format($item['revenue']) }} đ</td>
                                <td class="px-4 py-3 text-center">{{ $item['transactions_count'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-center text-gray-400">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Chi tiết giao dịch -->
    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 mt-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Danh sách giao dịch chi tiết (Tối đa 50 giao dịch mới nhất)</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Mã lô</th>
                        <th class="px-4 py-3">Dự án / Khu đất</th>
                        <th class="px-4 py-3">Nhân viên</th>
                        <th class="px-4 py-3">Phòng ban</th>
                        <th class="px-4 py-3">Chi nhánh</th>
                        <th class="px-4 py-3 text-right">Giá trị</th>
                        <th class="px-4 py-3 text-center">Ngày duyệt cọc</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['transactions'] as $tx)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white font-mono">
                                <a href="{{ \App\Filament\Resources\LotDepositRequestResource::getUrl('edit', ['record' => $tx['id']]) }}" class="text-primary-600 hover:underline dark:text-primary-400 font-semibold">
                                    {{ $tx['lot_code'] }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $tx['project_name'] }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $tx['user_name'] }}</td>
                            <td class="px-4 py-3">{{ $tx['department'] ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $tx['user_area'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 font-semibold">{{ number_format($tx['price']) }} đ</td>
                            <td class="px-4 py-3 text-center">{{ \Illuminate\Support\Carbon::parse($tx['created_at'])->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-3 text-center text-gray-400">Không tìm thấy giao dịch nào phù hợp</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
