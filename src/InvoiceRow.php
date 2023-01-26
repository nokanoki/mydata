<?php

namespace Nokanoki;

use Nokanoki\Enums\VatCategory;

class InvoiceRow
{
    public int $lineNumber;
    public float $netValue;
    public float $vatValue;
    public VatCategory $vatCategory;
    public IncomeClassification $classification;

    
}
