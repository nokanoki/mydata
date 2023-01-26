<?php

namespace Nokanoki\Enums;

enum VatCategory: string
{
    case FPA_24 = '1';
    case FPA_13 = '2';
    case FPA_6 = '3';
    case FPA_17 = '4';
    case FPA_9 = '5';
    case FPA_4 = '6';
    case FPA_0 = '7';
    case FPA_NONE = '8';

    public static function calcVat(float $value, VatCategory $vatCategory)
    {
        switch ($vatCategory) {
            case VatCategory::FPA_24:
                return $value * 0.24;
            case VatCategory::FPA_13:
                return $value * 0.13;
            case VatCategory::FPA_6:
                return $value * 0.06;
            case VatCategory::FPA_17:
                return $value * 0.17;
            case VatCategory::FPA_9:
                return $value * 0.09;
            case VatCategory::FPA_4:
                return $value * 0.04;
            case VatCategory::FPA_0;
                return 0;
            case VatCategory::FPA_NONE:
                return 0;
        }
        return 0;
    }
}
