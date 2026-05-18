<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegalVideoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $videos = [
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Quy trình kiểm tra pháp lý dự án căn hộ hình thành trong tương lai',
                'slug' => 'quy-trinh-kiem-tra-phap-ly-du-an-can-ho-hinh-thanh-trong-tuong-lai',
                'short_description' => 'Hướng dẫn chi tiết các bước kiểm tra pháp lý dự án từ giấy phép xây dựng đến bảo lãnh ngân hàng.',
                'description' => 'Trong video này, chuyên gia pháp lý bất động sản sẽ hướng dẫn bạn cách kiểm tra tính pháp lý của một dự án căn hộ chung cư hình thành trong tương lai trước khi ký hợp đồng mua bán. Các giấy tờ quan trọng cần kiểm tra bao gồm: Quyết định giao đất, Giấy phép xây dựng, Biên bản nghiệm thu phần móng, Văn bản đủ điều kiện bán nhà của Sở Xây dựng và Chứng thư bảo lãnh của ngân hàng.',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=600&q=80',
                'duration_seconds' => 642,
                'category' => 'project_legal',
                'is_active' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => '5 Bẫy pháp lý trong Hợp đồng mua bán căn hộ cần tránh',
                'slug' => '5-bay-phap-ly-trong-hop-dong-mua-ban-can-ho-can-tranh',
                'short_description' => 'Nhận diện những điều khoản bất lợi trong hợp đồng mua bán nhà đất và cách thương thảo lại.',
                'description' => 'Ký hợp đồng mua bán là bước quan trọng nhất quyết định giao dịch bất động sản của bạn. Video này sẽ vạch rõ 5 điều khoản mập mờ phổ biến mà người mua thường bỏ qua: Cách tính diện tích thông thủy so với tim tường, thời gian bàn giao sổ hồng thực tế, mức phạt chậm thanh toán so với chậm bàn giao nhà, điều khoản miễn trừ trách nhiệm của chủ đầu tư và cách thức giải quyết tranh chấp.',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&w=600&q=80',
                'duration_seconds' => 480,
                'category' => 'contract',
                'is_active' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Cách tự kiểm tra quy hoạch đất đai trực tuyến chính xác nhất',
                'slug' => 'cach-tu-kiem-tra-quy-hoach-dat-dai-truc-tuyen-chinh-xac-nhat',
                'short_description' => 'Hướng dẫn tra cứu quy hoạch đất đai thông qua các ứng dụng bản đồ số của nhà nước.',
                'description' => 'Làm thế nào để biết thửa đất bạn sắp mua có nằm trong vùng quy hoạch cây xanh, giao thông hay dự án công cộng không? Video hướng dẫn bạn từng bước cách tự sử dụng điện thoại và máy tính để tra cứu thông tin quy hoạch đất đai trên các cổng thông tin của thành phố Hồ Chí Minh, Hà Nội, Đồng Nai... và cách đọc bản đồ quy hoạch màu sắc chuẩn xác.',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&w=600&q=80',
                'duration_seconds' => 715,
                'category' => 'planning',
                'is_active' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Quy trình công chứng mua bán nhà đất an toàn từ A đến Z',
                'slug' => 'quy-trinh-cong-chung-mua-ban-nha-dat-an-toan-tu-a-den-z',
                'short_description' => 'Tất tần tật về quy trình giao dịch tại phòng công chứng, đóng thuế thu nhập cá nhân và đăng bộ.',
                'description' => 'Từ lúc đặt cọc đến khi cầm được sổ hồng trên tay, bạn phải trải qua quy trình công chứng mua bán vô cùng nghiêm ngặt. Video chia sẻ chi tiết về: Hồ sơ cần chuẩn bị cho cả bên mua và bên bán, quy trình ký kết tại tổ chức hành nghề công chứng, việc thanh toán tiền qua ngân hàng đảm bảo an toàn, cách kê khai thuế thu nhập cá nhân/lệ phí trước bạ và thủ tục đăng bộ sang tên.',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1554415707-6e8cfc93fe23?auto=format&fit=crop&w=600&q=80',
                'duration_seconds' => 920,
                'category' => 'transaction_process',
                'is_active' => true,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'title' => 'Chuyên đề: Phân tích dự thảo Luật Đất đai mới nhất (Không khả dụng)',
                'slug' => 'chuyen-de-phan-tich-du-thao-luat-dat-dai-moi-nhat',
                'short_description' => 'Video thảo luận chuyên sâu về luật đất đai sửa đổi bổ sung năm nay.',
                'description' => 'Video này hiện đang được biên tập lại để cập nhật các nghị định mới nhất của chính phủ và tạm thời không khả dụng.',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&w=600&q=80',
                'duration_seconds' => 1200,
                'category' => 'project_legal',
                'is_active' => false,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('legal_videos')->insert($videos);
    }
}
