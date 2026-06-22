<?php
namespace App\Filament\Resources\LotResource\Pages;

use App\Filament\Resources\LotResource;
use App\Modules\Area\Models\Lot;
use App\Modules\Area\Models\Enums\LotStatus;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ListLots extends ListRecords
{
    protected static string $resource = LotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(fn (): string => LotResource::getUrl('create', [
                    'area_id' => request()->input('tableFilters.area.value'),
                ])),
            
            Actions\Action::make('downloadTemplate')
                ->label('Tải file mẫu')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    $headers = [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                        'Content-Disposition' => 'attachment; filename="mau_danh_sach_lo_dat.csv"',
                    ];
                    
                    $callback = function() {
                        $file = fopen('php://output', 'w');
                        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                        
                        fputcsv($file, [
                            'Mã lô',
                            'Giá (VNĐ)',
                            'Diện tích (m2)',
                            'Hướng',
                            'Đơn giá/m2',
                            'Pháp lý',
                            'Mặt tiền (m)',
                            'Lô góc (X)',
                            'Mô tả'
                        ]);
                        
                        fputcsv($file, [
                            'A-01',
                            '1500000000',
                            '100',
                            'Đông Nam',
                            '15000000',
                            'Sổ đỏ',
                            '5',
                            'X',
                            'Lô đất đẹp gần công viên'
                        ]);
                        
                        fclose($file);
                    };
                    
                    return response()->stream($callback, 200, $headers);
                }),

            Actions\Action::make('importExcel')
                ->label('Nhập từ Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('area_id')
                        ->label('Khu đất')
                        ->relationship('area', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(fn () => request()->input('tableFilters.area.value')),
                    Forms\Components\FileUpload::make('file')
                        ->label('Chọn tệp Excel/CSV')
                        ->disk('public')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                        ])
                        ->maxSize(5120),
                ])
                ->action(function (array $data): void {
                    $filePath = Storage::disk('public')->path($data['file']);
                    
                    try {
                        $spreadsheet = IOFactory::load($filePath);
                        $worksheet = $spreadsheet->getActiveSheet();
                        $rows = $worksheet->toArray();
                        
                        if (count($rows) <= 1) {
                            Notification::make()
                                ->title('File trống hoặc không hợp lệ')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $header = array_map(fn($val) => mb_strtolower(trim($val ?? '')), $rows[0]);
                        
                        $codeIdx = -1;
                        $priceIdx = -1;
                        $areaSizeIdx = -1;
                        $directionIdx = -1;
                        $unitPriceIdx = -1;
                        $legalIdx = -1;
                        $frontageIdx = -1;
                        $isCornerIdx = -1;
                        $descriptionIdx = -1;
                        
                        foreach ($header as $idx => $colName) {
                            $colName = str_replace([' ', '_', '-'], '', $colName);
                            
                            if (str_contains($colName, 'mãlô') || str_contains($colName, 'mãlo') || str_contains($colName, 'malo') || $colName === 'lô' || $colName === 'lo' || $colName === 'mã') {
                                $codeIdx = $idx;
                            } elseif (str_contains($colName, 'đơngiá') || str_contains($colName, 'dongia')) {
                                $unitPriceIdx = $idx;
                            } elseif (str_contains($colName, 'giá') || str_contains($colName, 'gia') || str_contains($colName, 'thanhtien') || str_contains($colName, 'thànhtiền')) {
                                if (!str_contains($colName, 'đơn') && !str_contains($colName, 'don')) {
                                    $priceIdx = $idx;
                                }
                            } elseif (str_contains($colName, 'diệntích') || str_contains($colName, 'dientich') || str_contains($colName, 'm2')) {
                                $areaSizeIdx = $idx;
                            } elseif (str_contains($colName, 'hướng') || str_contains($colName, 'huong')) {
                                $directionIdx = $idx;
                            } elseif (str_contains($colName, 'pháplý') || str_contains($colName, 'phaply') || str_contains($colName, 'sổđỏ') || str_contains($colName, 'sodo')) {
                                $legalIdx = $idx;
                            } elseif (str_contains($colName, 'mặttiền') || str_contains($colName, 'mattien')) {
                                $frontageIdx = $idx;
                            } elseif (str_contains($colName, 'lôgóc') || str_contains($colName, 'logoc') || $colName === 'góc' || $colName === 'goc') {
                                $isCornerIdx = $idx;
                            } elseif (str_contains($colName, 'môtả') || str_contains($colName, 'mota') || str_contains($colName, 'ghichú') || str_contains($colName, 'ghichu')) {
                                $descriptionIdx = $idx;
                            }
                        }
                        
                        if ($codeIdx === -1) $codeIdx = 0;
                        if ($priceIdx === -1) $priceIdx = 1;
                        if ($areaSizeIdx === -1) $areaSizeIdx = 2;
                        if ($directionIdx === -1) $directionIdx = 3;
                        if ($unitPriceIdx === -1) $unitPriceIdx = 4;
                        if ($legalIdx === -1) $legalIdx = 5;
                        if ($frontageIdx === -1) $frontageIdx = 6;
                        if ($isCornerIdx === -1) $isCornerIdx = 7;
                        if ($descriptionIdx === -1) $descriptionIdx = 8;
                        
                        $count = 0;
                        $areaId = $data['area_id'];
                        
                        for ($i = 1; $i < count($rows); $i++) {
                            $row = $rows[$i];
                            $code = isset($row[$codeIdx]) ? trim((string) $row[$codeIdx]) : '';
                            if (empty($code)) {
                                continue;
                            }
                            
                            $priceVal = isset($row[$priceIdx]) ? $row[$priceIdx] : null;
                            $unitPriceVal = isset($row[$unitPriceIdx]) ? $row[$unitPriceIdx] : null;
                            $areaSizeVal = isset($row[$areaSizeIdx]) ? $row[$areaSizeIdx] : null;
                            
                            $price = null;
                            if ($priceVal !== null && $priceVal !== '') {
                                $price = (float) preg_replace('/[^\d.]/', '', (string) $priceVal);
                            }
                            
                            $unitPrice = null;
                            if ($unitPriceVal !== null && $unitPriceVal !== '') {
                                $unitPrice = (float) preg_replace('/[^\d.]/', '', (string) $unitPriceVal);
                            }
                            
                            $areaSize = null;
                            if ($areaSizeVal !== null && $areaSizeVal !== '') {
                                $areaSize = (float) preg_replace('/[^\d.]/', '', (string) $areaSizeVal);
                            }
                            
                            $direction = isset($row[$directionIdx]) ? trim((string) $row[$directionIdx]) : null;
                            $legal = isset($row[$legalIdx]) ? trim((string) $row[$legalIdx]) : null;
                            
                            $frontage = null;
                            if (isset($row[$frontageIdx]) && $row[$frontageIdx] !== '') {
                                $frontage = (float) preg_replace('/[^\d.]/', '', (string) $row[$frontageIdx]);
                            }
                            
                            $isCornerVal = isset($row[$isCornerIdx]) ? mb_strtolower(trim((string) $row[$isCornerIdx])) : '';
                            $isCorner = in_array($isCornerVal, ['1', 'có', 'co', 'x', 'yes', 'true']);
                            
                            $description = isset($row[$descriptionIdx]) ? trim((string) $row[$descriptionIdx]) : null;
                            
                            Lot::updateOrCreate(
                                [
                                    'area_id' => $areaId,
                                    'code' => $code,
                                ],
                                [
                                    'status' => LotStatus::AVAILABLE,
                                    'price' => $price,
                                    'unit_price' => $unitPrice,
                                    'area_size' => $areaSize,
                                    'direction' => $direction,
                                    'legal' => $legal,
                                    'frontage' => $frontage,
                                    'is_corner' => $isCorner,
                                    'description' => $description,
                                ]
                            );
                            
                            $count++;
                        }
                        
                        if (Storage::disk('public')->exists($data['file'])) {
                            Storage::disk('public')->delete($data['file']);
                        }
                        
                        Notification::make()
                            ->title('Nhập danh sách lô đất thành công')
                            ->body("Đã xử lý {$count} lô đất.")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Lỗi khi đọc file Excel')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
