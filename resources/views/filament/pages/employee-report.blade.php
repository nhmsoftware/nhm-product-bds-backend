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
    @if(!$selectedEmployeeDetails)
        <form wire:submit.prevent="applyFilters" class="space-y-6">
            {{ $this->form }}
            
            <div class="flex justify-end">
                <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">
                    Áp dụng bộ lọc
                </x-filament::button>
            </div>
        </form>

        @php
            $reportData = $this->getReportData();
        @endphp

        <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 mt-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Bảng xếp hạng thành tích nhân sự</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 text-center">Hạng</th>
                            <th class="px-4 py-3">Mã NV</th>
                            <th class="px-4 py-3">Họ tên</th>
                            <th class="px-4 py-3">Phòng ban</th>
                            <th class="px-4 py-3">Chức danh</th>
                            <th class="px-4 py-3">Khu vực</th>
                            <th class="px-4 py-3 text-center">Tổng điểm KPI</th>
                            <th class="px-4 py-3 text-center">Giao dịch thành công</th>
                            <th class="px-4 py-3 text-center">Dẫn khách (Tour)</th>
                            <th class="px-4 py-3 text-center">Gặp khách</th>
                            <th class="px-4 py-3 text-center">Giới thiệu (Ref)</th>
                            <th class="px-4 py-3 text-center">Ngày công</th>
                            <th class="px-4 py-3 text-center">Vắng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportData as $index => $row)
                            @php
                                $currentUser = \Filament\Facades\Filament::auth()->user();
                                $isClickable = false;
                                if ($currentUser) {
                                    if ($currentUser->hasAnyPermission(['manage_all', 'manage_employees'])) {
                                        $isClickable = true;
                                    } elseif ($currentUser->id === $row['id']) {
                                        $isClickable = true;
                                    } elseif ($currentUser->role?->name === 'tp_kd' && $currentUser->department_id && $row['department_id'] === $currentUser->department_id) {
                                        $isClickable = true;
                                    } elseif ($currentUser->role?->name === 'gdkd' && $currentUser->branch_id && $row['branch_id'] === $currentUser->branch_id) {
                                        $isClickable = true;
                                    }
                                }
                            @endphp
                            <tr @if($isClickable) wire:click="selectEmployee('{{ $row['id'] }}')" @endif class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 @if($isClickable) cursor-pointer @else cursor-default @endif transition">
                                <td class="px-4 py-3 text-center font-extrabold text-base">
                                    @if($index === 0)
                                        1
                                    @elseif($index === 1)
                                        2
                                    @elseif($index === 2)
                                        3
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">{{ $row['staff_code'] }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                                <td class="px-4 py-3">{{ $row['department'] }}</td>
                                <td class="px-4 py-3">{{ $row['job_position'] }}</td>
                                <td class="px-4 py-3">{{ $row['area'] }}</td>
                                <td class="px-4 py-3 text-center font-bold text-primary-600 dark:text-primary-400">{{ $row['total_kpi_points'] }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-emerald-600 dark:text-emerald-400">{{ $row['successful_transactions'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $row['site_tours_count'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $row['customer_meetings_count'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $row['referrals_count'] }}</td>
                                <td class="px-4 py-3 text-center text-blue-600 dark:text-blue-400 font-semibold">{{ $row['working_days'] }}</td>
                                <td class="px-4 py-3 text-center text-rose-500">{{ $row['absences'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="px-4 py-3 text-center text-gray-400">Không tìm thấy dữ liệu nhân viên phù hợp</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- Trang Xem Chi Tiết Báo Cáo Toàn Diện -->
        <div class="space-y-6">
            <!-- Nút Quay Lại và Tiêu Đề -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-4">
                    <x-filament::button wire:click="closeEmployeeDetail" color="gray" icon="heroicon-m-arrow-left">
                        Quay lại danh sách
                    </x-filament::button>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Chi tiết hiệu suất: {{ $selectedEmployeeDetails['name'] }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            Mã nhân viên: <span class="font-mono font-semibold">{{ $selectedEmployeeDetails['staff_code'] }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Khối thông tin chung của Nhân Viên -->
            <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4 tracking-wider">Thông tin nhân sự</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <span class="text-xs text-gray-400 dark:text-gray-500 block">Phòng ban</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-white mt-1 block">{{ $selectedEmployeeDetails['department'] }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 dark:text-gray-500 block">Chức danh</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-white mt-1 block">{{ $selectedEmployeeDetails['job_position'] }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Khu vực / Chi nhánh</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-white mt-1 block">{{ $selectedEmployeeDetails['area'] }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block font-bold text-primary-600 dark:text-primary-400">Tổng điểm KPI</span>
                        <span class="text-sm font-bold text-primary-600 dark:text-primary-400 mt-1 block">{{ $selectedEmployeeDetails['total_kpi_points'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Thống kê Tóm Tắt (Overview Cards) -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-primary-50 dark:bg-primary-950/20 p-5 rounded-2xl border border-primary-100 dark:border-primary-900/30 text-center">
                    <span class="text-xs text-primary-600 dark:text-primary-400 font-semibold uppercase block">Tổng điểm KPI</span>
                    <span class="text-3xl font-black text-primary-600 dark:text-primary-400 mt-2 block">{{ $selectedEmployeeDetails['total_kpi_points'] }}</span>
                </div>
                <div class="bg-emerald-50 dark:bg-emerald-950/20 p-5 rounded-2xl border border-emerald-100 dark:border-emerald-900/30 text-center">
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold uppercase block">Giao dịch thành công</span>
                    <span class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2 block">{{ count($selectedEmployeeDetails['deposits']) }}</span>
                </div>
                <div class="bg-blue-50 dark:bg-blue-950/20 p-5 rounded-2xl border border-blue-100 dark:border-blue-900/30 text-center">
                    <span class="text-xs text-blue-600 dark:text-blue-400 font-semibold uppercase block">Dẫn khách (Tour)</span>
                    <span class="text-3xl font-black text-blue-600 dark:text-blue-400 mt-2 block">{{ count($selectedEmployeeDetails['tours']) }}</span>
                </div>
                <div class="bg-purple-50 dark:bg-purple-950/20 p-5 rounded-2xl border border-purple-100 dark:border-purple-900/30 text-center">
                    <span class="text-xs text-purple-600 dark:text-purple-400 font-semibold uppercase block">Gặp khách</span>
                    <span class="text-3xl font-black text-purple-600 dark:text-purple-400 mt-2 block">{{ count($selectedEmployeeDetails['meetings']) }}</span>
                </div>
                <div class="bg-rose-50 dark:bg-rose-950/20 p-5 rounded-2xl border border-rose-100 dark:border-rose-900/30 text-center col-span-2 md:col-span-1">
                    <span class="text-xs text-rose-600 dark:text-rose-400 font-semibold uppercase block">Giới thiệu (Ref)</span>
                    <span class="text-3xl font-black text-rose-600 dark:text-rose-400 mt-2 block">{{ count($selectedEmployeeDetails['referrals']) }}</span>
                </div>
            </div>

            <!-- Các Bảng Chi Tiết Hoạt Động (Tabs & Tables) -->
            <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700" x-data="{ tab: 'deposits' }">
                <!-- Tab Menu -->
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex flex-wrap gap-4 sm:space-x-8" aria-label="Tabs">
                        <button @click="tab = 'deposits'" :class="tab === 'deposits' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm transition">
                            💼 Giao dịch đặt cọc ({{ count($selectedEmployeeDetails['deposits']) }})
                        </button>
                        <button @click="tab = 'tours'" :class="tab === 'tours' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm transition">
                            🚗 Lượt dẫn khách ({{ count($selectedEmployeeDetails['tours']) }})
                        </button>
                        <button @click="tab = 'meetings'" :class="tab === 'meetings' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm transition">
                            🤝 Lượt gặp khách ({{ count($selectedEmployeeDetails['meetings']) }})
                        </button>
                        <button @click="tab = 'referrals'" :class="tab === 'referrals' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm transition">
                            📣 Giới thiệu ({{ count($selectedEmployeeDetails['referrals']) }})
                        </button>
                        <button @click="tab = 'attendances'" :class="tab === 'attendances' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-semibold text-sm transition">
                            ⏰ Chấm công ({{ count($selectedEmployeeDetails['attendances']) }})
                        </button>
                    </nav>
                </div>

                <!-- Tab Contents -->
                <div class="mt-6">
                    <!-- Deposits -->
                    <div x-show="tab === 'deposits'" class="space-y-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">Mã lô</th>
                                        <th class="px-4 py-3">Dự án / Khu vực</th>
                                        <th class="px-4 py-3">Thời gian</th>
                                        <th class="px-4 py-3">Trạng thái</th>
                                        <th class="px-4 py-3">Lý do / Nội dung cọc</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedEmployeeDetails['deposits'] as $dep)
                                        <tr class="border-b dark:border-gray-800">
                                            <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">{{ $dep['lot_code'] }}</td>
                                            <td class="px-4 py-3">{{ $dep['area_name'] }}</td>
                                            <td class="px-4 py-3">{{ $dep['created_at'] }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-{{ $dep['status_color'] }}-100 text-{{ $dep['status_color'] }}-800 dark:bg-{{ $dep['status_color'] }}-900/30 dark:text-{{ $dep['status_color'] }}-400">
                                                    {{ $dep['status_label'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-xs">{{ $dep['reason'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">Không có dữ liệu giao dịch đặt cọc trong kỳ</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tours -->
                    <div x-show="tab === 'tours'" class="space-y-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">Tên khách hàng</th>
                                        <th class="px-4 py-3">Mã căn hộ / lô</th>
                                        <th class="px-4 py-3">Dự án tham quan</th>
                                        <th class="px-4 py-3">Thời gian dẫn đi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedEmployeeDetails['tours'] as $tour)
                                        <tr class="border-b dark:border-gray-800">
                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $tour['customer_name'] }}</td>
                                            <td class="px-4 py-3 font-mono">{{ $tour['unit_code'] }}</td>
                                            <td class="px-4 py-3">{{ $tour['area_name'] }}</td>
                                            <td class="px-4 py-3">{{ $tour['created_at'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">Không có lượt dẫn khách nào trong kỳ</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Meetings -->
                    <div x-show="tab === 'meetings'" class="space-y-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">Tên khách hàng</th>
                                        <th class="px-4 py-3">Địa điểm gặp mặt</th>
                                        <th class="px-4 py-3">Nội dung / Mục đích cuộc gặp</th>
                                        <th class="px-4 py-3">Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedEmployeeDetails['meetings'] as $meet)
                                        <tr class="border-b dark:border-gray-800">
                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $meet['customer_name'] }}</td>
                                            <td class="px-4 py-3">{{ $meet['location'] }}</td>
                                            <td class="px-4 py-3">{{ $meet['purpose'] }}</td>
                                            <td class="px-4 py-3">{{ $meet['meeting_date'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">Không có lịch sử gặp khách nào trong kỳ</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Referrals -->
                    <div x-show="tab === 'referrals'" class="space-y-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">Tên người được giới thiệu</th>
                                        <th class="px-4 py-3">Phân loại</th>
                                        <th class="px-4 py-3">Trạng thái duyệt</th>
                                        <th class="px-4 py-3">Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedEmployeeDetails['referrals'] as $ref)
                                        <tr class="border-b dark:border-gray-800">
                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $ref['referred_name'] }}</td>
                                            <td class="px-4 py-3">{{ $ref['referral_type'] }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">
                                                    {{ $ref['status_label'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">{{ $ref['created_at'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">Không có dữ liệu giới thiệu nào trong kỳ</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Attendances -->
                    <div x-show="tab === 'attendances'" class="space-y-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">Ngày làm việc</th>
                                        <th class="px-4 py-3">Giờ vào</th>
                                        <th class="px-4 py-3">Giờ ra</th>
                                        <th class="px-4 py-3">Trạng thái chấm công</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($selectedEmployeeDetails['attendances'] as $att)
                                        <tr class="border-b dark:border-gray-800">
                                            <td class="px-4 py-3 font-mono">{{ $att['work_date'] }}</td>
                                            <td class="px-4 py-3 font-mono text-emerald-600 dark:text-emerald-400">{{ $att['check_in'] }}</td>
                                            <td class="px-4 py-3 font-mono text-blue-600 dark:text-blue-400">{{ $att['check_out'] }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $att['status_color'] }}-100 text-{{ $att['status_color'] }}-800 dark:bg-{{ $att['status_color'] }}-900/30 dark:text-{{ $att['status_color'] }}-400">
                                                    {{ $att['status_label'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">Không có lịch sử chấm công nào trong kỳ</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
