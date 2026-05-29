<?php

namespace Database\Factories;

use App\Modules\Recruitment\Models\RecruitmentPost;
use App\Modules\Recruitment\Models\Enums\RecruitmentPostStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecruitmentPostFactory extends Factory
{
    protected $model = RecruitmentPost::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'title' => $this->faker->sentence(6),
            'image' => $this->faker->imageUrl(),
            'branch_name' => 'Chi nhánh ' . $this->faker->city(),
            'job_position' => $this->faker->jobTitle(),
            'department' => $this->faker->randomElement(['Kinh doanh', 'Marketing', 'Nhân sự', 'Kế toán']),
            'short_description' => $this->faker->paragraph(2),
            'content' => $this->faker->paragraphs(3, true),
            'job_description' => $this->faker->paragraphs(2, true),
            'candidate_requirements' => $this->faker->paragraphs(2, true),
            'benefits' => $this->faker->paragraphs(2, true),
            'status' => $this->faker->randomElement(RecruitmentPostStatus::cases())->value,
        ];
    }
}
