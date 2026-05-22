<?php

namespace App\Core\Traits;

trait EnumHelper
{
    /**
     * Convert enum to options for select
     * @return array
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function serialize(): string
    {
        return strtolower($this->name);
    }

    public static function deserialize(string|int $value): self
    {
        if (is_numeric($value)) {
            return self::from((int) $value);
        }

        foreach (self::cases() as $case) {
            if (strtolower($case->name) === strtolower($value)) {
                return $case;
            }
        }

        throw new \InvalidArgumentException("Invalid enum value: {$value}");
    }

    abstract public function label(): string;
}
