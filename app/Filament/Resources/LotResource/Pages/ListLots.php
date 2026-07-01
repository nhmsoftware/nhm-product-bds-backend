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
                    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    
                    $headers = [
                        'Mã lô',
                        'Trạng thái',
                        'Giá (VNĐ)',
                        'Diện tích (m2)',
                        'Hướng',
                        'Đơn giá/m2',
                        'Pháp lý',
                        'Mặt tiền (m)',
                        'Lô góc (X)',
                        'Mô tả'
                    ];
                    
                    $row1 = [
                        'A-01',
                        'Còn hàng',
                        '1500000000',
                        '100',
                        'Đông Nam',
                        '15000000',
                        'Sổ đỏ',
                        '5',
                        'X',
                        'Lô đất đẹp gần công viên'
                    ];

                    foreach ($headers as $colIdx => $header) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                        $sheet->setCellValueExplicit(
                            $colLetter . '1',
                            $header,
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );
                    }

                    foreach ($row1 as $colIdx => $val) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                        $sheet->setCellValueExplicit(
                            $colLetter . '2',
                            $val,
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );
                    }

                    // Auto-size columns for readability
                    foreach (range(1, count($headers)) as $colIdx) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                    }

                    $responseHeaders = [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'Content-Disposition' => 'attachment; filename="mau_danh_sach_lo_dat.xlsx"',
                        'Cache-Control' => 'max-age=0',
                    ];

                    $callback = function() use ($spreadsheet) {
                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $writer->save('php://output');
                    };

                    return response()->stream($callback, 200, $responseHeaders);
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
                        $statusIdx = -1;
                        $priceIdx = -1;
                        $areaSizeIdx = -1;
                        $directionIdx = -1;
                        $unitPriceIdx = -1;
                        $legalIdx = -1;
                        $frontageIdx = -1;
                        $isCornerIdx = -1;
                        $descriptionIdx = -1;
                        
                        foreach ($header as $idx => $colName) {
                            $colNameClean = str_replace([' ', '_', '-'], '', $colName);
                            
                            if (str_contains($colNameClean, 'mãlô') || str_contains($colNameClean, 'mãlo') || str_contains($colNameClean, 'malo') || $colNameClean === 'lô' || $colNameClean === 'lo' || $colNameClean === 'mã') {
                                $codeIdx = $idx;
                            } elseif (str_contains($colNameClean, 'trạngthái') || str_contains($colNameClean, 'trangthai') || $colNameClean === 'status' || $colNameClean === 'tt') {
                                $statusIdx = $idx;
                            } elseif (str_contains($colNameClean, 'đơngiá') || str_contains($colNameClean, 'dongia')) {
                                $unitPriceIdx = $idx;
                            } elseif (str_contains($colNameClean, 'giá') || str_contains($colNameClean, 'gia') || str_contains($colNameClean, 'thanhtien') || str_contains($colNameClean, 'thànhtiền')) {
                                if (!str_contains($colNameClean, 'đơn') && !str_contains($colNameClean, 'don')) {
                                    $priceIdx = $idx;
                                }
                            } elseif (str_contains($colNameClean, 'diệntích') || str_contains($colNameClean, 'dientich') || str_contains($colNameClean, 'm2')) {
                                $areaSizeIdx = $idx;
                            } elseif (str_contains($colNameClean, 'hướng') || str_contains($colNameClean, 'huong')) {
                                $directionIdx = $idx;
                            } elseif (str_contains($colNameClean, 'pháplý') || str_contains($colNameClean, 'phaply') || str_contains($colNameClean, 'sổđỏ') || str_contains($colNameClean, 'sodo')) {
                                $legalIdx = $idx;
                            } elseif (str_contains($colNameClean, 'mặttiền') || str_contains($colNameClean, 'mattien')) {
                                $frontageIdx = $idx;
                            } elseif (str_contains($colNameClean, 'lôgóc') || str_contains($colNameClean, 'logoc') || $colNameClean === 'góc' || $colNameClean === 'goc') {
                                $isCornerIdx = $idx;
                            } elseif (str_contains($colNameClean, 'môtả') || str_contains($colNameClean, 'mota') || str_contains($colNameClean, 'ghichú') || str_contains($colNameClean, 'ghichu')) {
                                $descriptionIdx = $idx;
                            }
                        }
                        
                        $missingCols = [];
                        if ($codeIdx === -1) $missingCols[] = '"Mã lô"';
                        if ($statusIdx === -1) $missingCols[] = '"Trạng thái"';
                        
                        if (count($missingCols) > 0) {
                            Notification::make()
                                ->title('Thiếu cột bắt buộc')
                                ->body('File Excel thiếu các cột sau: ' . implode(', ', $missingCols))
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $errors = [];
                        $seenCodes = [];
                        $count = 0;
                        $areaId = $data['area_id'];
                        
                        \Illuminate\Support\Facades\DB::beginTransaction();
                        
                        for ($i = 1; $i < count($rows); $i++) {
                            $row = $rows[$i];
                            
                            // Bỏ qua dòng trống hoàn toàn
                            $isEmptyRow = true;
                            foreach ($row as $cell) {
                                if ($cell !== null && trim((string)$cell) !== '') {
                                    $isEmptyRow = false;
                                    break;
                                }
                            }
                            if ($isEmptyRow) {
                                continue;
                            }
                            
                            $rowNum = $i + 1;
                            
                            $code = isset($row[$codeIdx]) ? trim((string) $row[$codeIdx]) : '';
                            if (empty($code)) {
                                $errors[] = "Dòng {$rowNum}: Mã lô không được để trống.";
                            } else {
                                if (isset($seenCodes[$code])) {
                                    $errors[] = "Dòng {$rowNum}: Mã lô '{$code}' bị trùng lặp với dòng {$seenCodes[$code]} trong file Excel.";
                                } else {
                                    $seenCodes[$code] = $rowNum;
                                }
                            }
                            
                            $statusVal = isset($row[$statusIdx]) ? trim((string) $row[$statusIdx]) : '';
                            $status = null;
                            if (empty($statusVal)) {
                                $errors[] = "Dòng {$rowNum}: Trạng thái không được để trống.";
                            } else {
                                $normalizedStatus = preg_replace('/[^a-z0-9àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/u', '', mb_strtolower($statusVal));
                                if (in_array($normalizedStatus, ['conhang', 'cònhàng', '1'])) {
                                    $status = LotStatus::AVAILABLE;
                                } elseif (in_array($normalizedStatus, ['daban', 'đãbán', '2'])) {
                                    $status = LotStatus::SOLD;
                                } elseif (in_array($normalizedStatus, ['danggiucho', 'đanggiữchỗ', 'giucho', 'giữchỗ', '3'])) {
                                    $status = LotStatus::RESERVED;
                                } elseif (in_array($normalizedStatus, ['khongban', 'khôngbán', 'khongkhadung', 'khôngkhảdụng', 'khongkhadong', 'khôngkhảdông', '4'])) {
                                    $status = LotStatus::UNAVAILABLE;
                                } else {
                                    $errors[] = "Dòng {$rowNum}: Trạng thái '{$statusVal}' không hợp lệ. Các trạng thái hợp lệ: 'Còn hàng', 'Đã bán', 'Đang giữ chỗ', 'Không bán', 'Không khả dụng'.";
                                }
                            }
                            
                            $priceVal = ($priceIdx !== -1 && isset($row[$priceIdx])) ? $row[$priceIdx] : null;
                            $unitPriceVal = ($unitPriceIdx !== -1 && isset($row[$unitPriceIdx])) ? $row[$unitPriceIdx] : null;
                            $areaSizeVal = ($areaSizeIdx !== -1 && isset($row[$areaSizeIdx])) ? $row[$areaSizeIdx] : null;
                            
                            $price = \App\Filament\Support\AdminOptions::parseDecimal($priceVal);
                            
                            $unitPrice = \App\Filament\Support\AdminOptions::parseDecimal($unitPriceVal);
                            
                            $areaSize = \App\Filament\Support\AdminOptions::parseDecimal($areaSizeVal);
                            
                            $direction = ($directionIdx !== -1 && isset($row[$directionIdx])) ? trim((string) $row[$directionIdx]) : null;
                            $legal = ($legalIdx !== -1 && isset($row[$legalIdx])) ? trim((string) $row[$legalIdx]) : null;
                            
                            $frontage = ($frontageIdx !== -1 && isset($row[$frontageIdx])) ? \App\Filament\Support\AdminOptions::parseDecimal($row[$frontageIdx]) : null;
                            
                            $isCornerVal = ($isCornerIdx !== -1 && isset($row[$isCornerIdx])) ? mb_strtolower(trim((string) $row[$isCornerIdx])) : '';
                            $isCorner = in_array($isCornerVal, ['1', 'có', 'co', 'x', 'yes', 'true']);
                            
                            $description = ($descriptionIdx !== -1 && isset($row[$descriptionIdx])) ? trim((string) $row[$descriptionIdx]) : null;
                            
                            if (count($errors) === 0) {
                                // Tìm cả bản ghi đã xóa mềm (soft deleted) để tránh lỗi UNIQUE CONSTRAINT của DB
                                $lot = Lot::withTrashed()
                                    ->where('area_id', $areaId)
                                    ->where('code', $code)
                                    ->first();

                                if ($lot) {
                                    $lot->update([
                                        'status' => $status,
                                        'price' => $price,
                                        'unit_price' => $unitPrice,
                                        'area_size' => $areaSize,
                                        'direction' => $direction,
                                        'legal' => $legal,
                                        'frontage' => $frontage,
                                        'is_corner' => $isCorner,
                                        'description' => $description,
                                    ]);
                                    if ($lot->trashed()) {
                                        $lot->restore();
                                    }
                                } else {
                                    Lot::create([
                                        'area_id' => $areaId,
                                        'code' => $code,
                                        'status' => $status,
                                        'price' => $price,
                                        'unit_price' => $unitPrice,
                                        'area_size' => $areaSize,
                                        'direction' => $direction,
                                        'legal' => $legal,
                                        'frontage' => $frontage,
                                        'is_corner' => $isCorner,
                                        'description' => $description,
                                    ]);
                                }
                                $count++;
                            }
                        }
                        
                        if (count($errors) > 0) {
                            \Illuminate\Support\Facades\DB::rollBack();
                            
                            $displayErrors = array_slice($errors, 0, 10);
                            $bodyMsg = implode("\n", $displayErrors);
                            if (count($errors) > 10) {
                                $bodyMsg .= "\n... và " . (count($errors) - 10) . " lỗi khác.";
                            }
                            
                            Notification::make()
                                ->title('Nhập dữ liệu thất bại (Không có dữ liệu nào được lưu)')
                                ->body($bodyMsg)
                                ->danger()
                                ->persistent()
                                ->send();
                                
                            if (Storage::disk('public')->exists($data['file'])) {
                                Storage::disk('public')->delete($data['file']);
                            }
                            return;
                        }
                        
                        \Illuminate\Support\Facades\DB::commit();
                        
                        if (Storage::disk('public')->exists($data['file'])) {
                            Storage::disk('public')->delete($data['file']);
                        }
                        
                        Notification::make()
                            ->title('Nhập danh sách lô đất thành công')
                            ->body("Đã xử lý {$count} lô đất.")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\DB::rollBack();
                        
                        $message = $e->getMessage();
                        if (str_contains($message, 'Duplicate entry') || str_contains($message, '23000')) {
                            $message = 'Mã lô đất đã tồn tại trong hệ thống (có thể ở trạng thái đã xóa tạm thời hoặc thuộc khu đất khác). Vui lòng kiểm tra lại.';
                        }
                        
                        Notification::make()
                            ->title('Lỗi khi đọc file Excel')
                            ->body($message)
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
