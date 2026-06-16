<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RecentActivitySeeder extends Seeder
{
    private const MEETING_IMAGE = 'storage/meetings/customer-meeting-proof.jpg';
    private const SITE_TOUR_IMAGE = 'storage/site_tours/site-tour-proof.jpg';

    public function run(): void
    {
        $now = Carbon::now();

        $this->ensurePlaceholderImages();

        DB::transaction(function () use ($now) {
            $areas = $this->areasByName();

            if ($areas->isEmpty()) {
                $this->command?->warn('Chưa có khu đất trong database, hãy chạy InventoryAreaSeeder trước.');
                return;
            }

            $this->seedForEmployee('employee@test.com', [
                'meetings' => [
                    [
                        'project' => 'Horizon Hills',
                        'customer_name' => 'Nguyễn Minh Anh',
                        'customer_phone' => '0901000001',
                        'latitude' => 11.925901,
                        'longitude' => 108.438913,
                        'created_at' => $now->copy()->subHours(1)->setTimeFromTimeString('09:20:00'),
                    ],
                    [
                        'project' => 'Riverfront City',
                        'customer_name' => 'Trần Quốc Bảo',
                        'customer_phone' => '0901000002',
                        'latitude' => 10.807639,
                        'longitude' => 106.732631,
                        'created_at' => $now->copy()->subDay()->setTimeFromTimeString('15:35:00'),
                    ],
                    [
                        'project' => 'The Solaria',
                        'customer_name' => 'Bùi Thanh Lam',
                        'customer_phone' => '0901000003',
                        'latitude' => 20.949083,
                        'longitude' => 107.073706,
                        'created_at' => $now->copy()->subDays(3)->setTimeFromTimeString('10:15:00'),
                    ],
                ],
                'site_tours' => [
                    [
                        'project' => 'Horizon Hills',
                        'unit_code' => 'H-02',
                        'customer_name' => 'Phạm Thu Hà',
                        'latitude' => 11.925901,
                        'longitude' => 108.438913,
                        'created_at' => $now->copy()->subHours(2)->setTimeFromTimeString('10:40:00'),
                    ],
                    [
                        'project' => 'Riverfront City',
                        'unit_code' => 'R-05',
                        'customer_name' => 'Võ Gia Hân',
                        'latitude' => 10.807639,
                        'longitude' => 106.732631,
                        'created_at' => $now->copy()->subDays(2)->setTimeFromTimeString('16:10:00'),
                    ],
                ],
            ], $areas);

            $this->seedForEmployee('employee2@test.com', [
                'meetings' => [
                    [
                        'project' => 'Eco Garden',
                        'customer_name' => 'Lê Hoài Nam',
                        'customer_phone' => '0902000001',
                        'latitude' => 21.008261,
                        'longitude' => 105.933951,
                        'created_at' => $now->copy()->subHours(3)->setTimeFromTimeString('11:05:00'),
                    ],
                    [
                        'project' => 'Metro Square',
                        'customer_name' => 'Đỗ Ngọc Mai',
                        'customer_phone' => '0902000002',
                        'latitude' => 10.802705,
                        'longitude' => 106.742747,
                        'created_at' => $now->copy()->subDays(1)->setTimeFromTimeString('14:25:00'),
                    ],
                ],
                'site_tours' => [
                    [
                        'project' => 'Riverfront City',
                        'unit_code' => 'R-07',
                        'customer_name' => 'Đặng Minh Khoa',
                        'latitude' => 10.807639,
                        'longitude' => 106.732631,
                        'created_at' => $now->copy()->subHours(5)->setTimeFromTimeString('13:45:00'),
                    ],
                    [
                        'project' => 'Coastal Bay',
                        'unit_code' => 'C-12',
                        'customer_name' => 'Nguyễn Hải Yến',
                        'latitude' => 10.945405,
                        'longitude' => 108.287894,
                        'created_at' => $now->copy()->subDays(4)->setTimeFromTimeString('09:50:00'),
                    ],
                ],
            ], $areas);
        });
    }

    private function seedForEmployee(string $email, array $data, $areas): void
    {
        $user = DB::table('users')->where('email', $email)->first();

        if (!$user) {
            $this->command?->warn("User {$email} không tồn tại, bỏ qua.");
            return;
        }

        $meetingPhones = array_column($data['meetings'], 'customer_phone');
        DB::table('customer_meetings')
            ->where('user_id', $user->id)
            ->whereIn('customer_phone', $meetingPhones)
            ->delete();

        $siteTourKeys = array_map(
            fn (array $tour) => [$tour['unit_code'], $tour['customer_name']],
            $data['site_tours']
        );

        foreach ($siteTourKeys as [$unitCode, $customerName]) {
            DB::table('site_tours')
                ->where('user_id', $user->id)
                ->where('unit_code', $unitCode)
                ->where('customer_name', $customerName)
                ->delete();
        }

        foreach ($data['meetings'] as $meeting) {
            $area = $areas->get($meeting['project']) ?? $areas->first();
            $createdAt = $meeting['created_at'];

            DB::table('customer_meetings')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'project_id' => $area->id,
                'customer_name' => $meeting['customer_name'],
                'customer_phone' => $meeting['customer_phone'],
                'image_path' => '/' . self::MEETING_IMAGE,
                'latitude' => $meeting['latitude'],
                'longitude' => $meeting['longitude'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'deleted_at' => null,
            ]);
        }

        foreach ($data['site_tours'] as $tour) {
            $area = $areas->get($tour['project']) ?? $areas->first();
            $createdAt = $tour['created_at'];

            DB::table('site_tours')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'project_id' => $area->id,
                'unit_code' => $tour['unit_code'],
                'customer_name' => $tour['customer_name'],
                'image_path' => '/' . self::SITE_TOUR_IMAGE,
                'latitude' => $tour['latitude'],
                'longitude' => $tour['longitude'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'deleted_at' => null,
            ]);
        }

        $this->command?->info("Seeded recent activities for {$email}.");
    }

    private function areasByName()
    {
        return DB::table('areas')
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->get()
            ->mapWithKeys(function ($area) {
                $baseName = trim(explode(' - ', $area->name)[0]);
                return [$baseName => $area, $area->name => $area];
            });
    }

    private function ensurePlaceholderImages(): void
    {
        $files = [
            public_path(self::MEETING_IMAGE),
            public_path(self::SITE_TOUR_IMAGE),
        ];

        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/ASP/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/ASP/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Al//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EFBQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8QH//EFBQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQIBAT8QH//EFBABAQAAAAAAAAAAAAAAAAAAABD/2gAIAQEAAT8QH//Z');

        foreach ($files as $file) {
            File::ensureDirectoryExists(dirname($file));

            if (!File::exists($file)) {
                File::put($file, $jpeg);
            }
        }
    }
}
