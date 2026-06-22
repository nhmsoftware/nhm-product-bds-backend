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
     * Cấu hình tên của trường liên kết bản đồ Google Maps URL (để tự động đồng bộ khi chọn vị trí).
     */
    public function googleMapsUrlField(?string $field): static
    {
        $this->googleMapsUrlField = $field;
        return $this;
    }

    /**
     * Lấy tên trường liên kết bản đồ Google Maps URL.
     */
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

    /**
     * Lấy tên trường Vĩ độ.
     */
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

    /**
     * Lấy tên trường Kinh độ.
     */
    public function getLongitudeField(): ?string
    {
        return $this->longitudeField;
    }
}
