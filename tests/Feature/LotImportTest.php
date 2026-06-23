<?php

namespace Tests\Feature;

use App\Modules\Area\Models\Area;
use App\Modules\Area\Models\Lot;
use App\Modules\Area\Models\Enums\AreaStatus;
use App\Modules\Area\Models\Enums\LotStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (\Illuminate\Support\Facades\Schema::hasTable('areas')) {
            if (!\Illuminate\Support\Facades\Schema::hasTable('projects')) {
                \Illuminate\Support\Facades\Schema::create('projects', function ($table) {
                    $table->uuid('id')->primary();
                    $table->timestamps();
                });
            }
        }
    }

    private function createDummyArea(): Area
    {
        return Area::create([
            'name' => 'Khu đất Test Import',
            'status' => AreaStatus::OPENING,
            'total_lots' => 10,
            'remaining_lots' => 10,
        ]);
    }

    public function test_status_normalization_logic(): void
    {
        $statusMap = [
            'Còn hàng' => LotStatus::AVAILABLE,
            'con hang' => LotStatus::AVAILABLE,
            '1' => LotStatus::AVAILABLE,
            'Đã bán' => LotStatus::SOLD,
            'da ban' => LotStatus::SOLD,
            '2' => LotStatus::SOLD,
            'Đang giữ chỗ' => LotStatus::RESERVED,
            'dang giu cho' => LotStatus::RESERVED,
            'giữ chỗ' => LotStatus::RESERVED,
            '3' => LotStatus::RESERVED,
            'Không bán' => LotStatus::UNAVAILABLE,
            'khong ban' => LotStatus::UNAVAILABLE,
            'Không khả dụng' => LotStatus::UNAVAILABLE,
            'khong kha dung' => LotStatus::UNAVAILABLE,
            '4' => LotStatus::UNAVAILABLE,
        ];

        foreach ($statusMap as $input => $expectedEnum) {
            $normalizedStatus = preg_replace('/[^a-z0-9àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/u', '', mb_strtolower($input));
            
            $status = null;
            if (in_array($normalizedStatus, ['conhang', 'cònhàng', '1'])) {
                $status = LotStatus::AVAILABLE;
            } elseif (in_array($normalizedStatus, ['daban', 'đãbán', '2'])) {
                $status = LotStatus::SOLD;
            } elseif (in_array($normalizedStatus, ['danggiucho', 'đanggiữchỗ', 'giucho', 'giữchỗ', '3'])) {
                $status = LotStatus::RESERVED;
            } elseif (in_array($normalizedStatus, ['khongban', 'khôngbán', 'khongkhadung', 'khôngkhảdụng', 'khongkhadong', 'khôngkhảdông', '4'])) {
                $status = LotStatus::UNAVAILABLE;
            }

            $this->assertEquals($expectedEnum, $status, "Failed parsing: '{$input}'");
        }
    }

    /**
     * Test mapping method matching the logic in ListLots
     */
    private function simulateImport(array $rows, Area $area): array
    {
        if (count($rows) <= 1) {
            return ['success' => false, 'message' => 'File trống hoặc không hợp lệ'];
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
            return [
                'success' => false,
                'message' => 'File Excel thiếu các cột sau: ' . implode(', ', $missingCols)
            ];
        }
        
        $errors = [];
        $count = 0;
        $areaId = $area->id;
        
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
            
            $direction = ($directionIdx !== -1 && isset($row[$directionIdx])) ? trim((string) $row[$directionIdx]) : null;
            $legal = ($legalIdx !== -1 && isset($row[$legalIdx])) ? trim((string) $row[$legalIdx]) : null;
            
            $frontage = null;
            if ($frontageIdx !== -1 && isset($row[$frontageIdx]) && $row[$frontageIdx] !== '') {
                $frontage = (float) preg_replace('/[^\d.]/', '', (string) $row[$frontageIdx]);
            }
            
            $isCornerVal = ($isCornerIdx !== -1 && isset($row[$isCornerIdx])) ? mb_strtolower(trim((string) $row[$isCornerIdx])) : '';
            $isCorner = in_array($isCornerVal, ['1', 'có', 'co', 'x', 'yes', 'true']);
            
            $description = ($descriptionIdx !== -1 && isset($row[$descriptionIdx])) ? trim((string) $row[$descriptionIdx]) : null;
            
            if (count($errors) === 0) {
                Lot::updateOrCreate(
                    [
                        'area_id' => $areaId,
                        'code' => $code,
                    ],
                    [
                        'status' => $status,
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
        }
        
        if (count($errors) > 0) {
            \Illuminate\Support\Facades\DB::rollBack();
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        \Illuminate\Support\Facades\DB::commit();
        return [
            'success' => true,
            'count' => $count
        ];
    }

    public function test_successful_import(): void
    {
        $area = $this->createDummyArea();
        
        $rows = [
            ['Mã lô', 'Trạng thái', 'Giá (VNĐ)', 'Diện tích (m2)', 'Hướng', 'Đơn giá/m2', 'Pháp lý', 'Mặt tiền (m)', 'Lô góc (X)', 'Mô tả'],
            ['A-01', 'Còn hàng', '1,500,000,000', '100', 'Đông Nam', '15,000,000', 'Sổ đỏ', '5', 'X', 'Mô tả A-01'],
            ['A-02', 'Đã bán', '2,000,000,000', '120', 'Tây Bắc', '16,600,000', 'Sổ đỏ', '6', '', 'Mô tả A-02'],
            ['', '', '', '', '', '', '', '', '', ''] // Empty row
        ];

        $result = $this->simulateImport($rows, $area);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['count']);
        
        $this->assertDatabaseHas('lots', [
            'area_id' => $area->id,
            'code' => 'A-01',
            'status' => LotStatus::AVAILABLE->value,
            'price' => 1500000000.0,
            'area_size' => 100.0,
            'direction' => 'Đông Nam',
            'legal' => 'Sổ đỏ',
            'frontage' => 5.0,
            'is_corner' => true,
            'description' => 'Mô tả A-01',
        ]);

        $this->assertDatabaseHas('lots', [
            'area_id' => $area->id,
            'code' => 'A-02',
            'status' => LotStatus::SOLD->value,
            'price' => 2000000000.0,
            'area_size' => 120.0,
            'direction' => 'Tây Bắc',
            'legal' => 'Sổ đỏ',
            'frontage' => 6.0,
            'is_corner' => false,
            'description' => 'Mô tả A-02',
        ]);
    }

    public function test_import_validation_missing_required_columns(): void
    {
        $area = $this->createDummyArea();
        
        // Missing "Trạng thái"
        $rows = [
            ['Mã lô', 'Giá (VNĐ)', 'Diện tích (m2)'],
            ['A-01', '1,500,000,000', '100']
        ];

        $result = $this->simulateImport($rows, $area);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('File Excel thiếu các cột sau', $result['message']);
        $this->assertStringContainsString('Trạng thái', $result['message']);
    }

    public function test_import_validation_invalid_status_rolls_back_entire_transaction(): void
    {
        $area = $this->createDummyArea();
        
        // Row 3 has invalid status 'Đã bán xong'
        $rows = [
            ['Mã lô', 'Trạng thái', 'Giá (VNĐ)'],
            ['A-01', 'Còn hàng', '1,500,000,000'],
            ['A-02', 'Đã bán xong', '2,000,000,000']
        ];

        $result = $this->simulateImport($rows, $area);

        $this->assertFalse($result['success']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Trạng thái \'Đã bán xong\' không hợp lệ', $result['errors'][0]);

        // Verify transaction rolled back (no lot is created, not even A-01)
        $this->assertDatabaseMissing('lots', [
            'area_id' => $area->id,
            'code' => 'A-01'
        ]);
        $this->assertDatabaseMissing('lots', [
            'area_id' => $area->id,
            'code' => 'A-02'
        ]);
    }
}
