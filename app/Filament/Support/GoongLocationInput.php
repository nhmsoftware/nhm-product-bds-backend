<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

class GoongLocationInput extends TextInput
{
    protected string $view = 'filament.components.goong-location-input';

    protected ?string $googleMapsUrlField = null;
    protected ?string $latitudeField = null;
    protected ?string $longitudeField = null;

    /**
     * Chế độ rút gọn: chỉ lưu phần đầu tiên của tên địa danh (trước dấu phẩy đầu tiên).
     * Phù hợp cho trường Tỉnh/Thành, Quận/Huyện.
     */
    protected bool $shortName = false;

    public function shortName(bool $condition = true): static
    {
        $this->shortName = $condition;
        return $this;
    }

    public function isShortName(): bool
    {
        return $this->shortName;
    }

    /**
     * Cấu hình tên của trường liên kết bản đồ Google Maps URL.
     */
    public function googleMapsUrlField(?string $field): static
    {
        $this->googleMapsUrlField = $field;
        return $this;
    }

    public function getGoogleMapsUrlField(): ?string
    {
        return $this->googleMapsUrlField;
    }

    /**
     * Cấu hình tên của trường Vĩ độ (latitude).
     */
    public function latitudeField(?string $field): static
    {
        $this->latitudeField = $field;
        return $this;
    }

    public function getLatitudeField(): ?string
    {
        return $this->latitudeField;
    }

    /**
     * Cấu hình tên của trường Kinh độ (longitude).
     */
    public function longitudeField(?string $field): static
    {
        $this->longitudeField = $field;
        return $this;
    }

    public function getLongitudeField(): ?string
    {
        return $this->longitudeField;
    }
}
