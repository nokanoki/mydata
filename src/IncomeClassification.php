<?php

namespace Nokanoki;

use Nokanoki\Enums\ClassificationCategory;
use Nokanoki\Enums\ClassificationType;

class IncomeClassification
{
    public ClassificationType $type;
    public ClassificationCategory $category;
    public float $netValue;

    public function __construct(float $netValue, ClassificationType $type = ClassificationType::E3_561_001, ClassificationCategory $category = ClassificationCategory::CATEGORY_1_1)
    {
        $this->netValue = $netValue;
        $this->type = $type;
        $this->category = $category;
    }
}
