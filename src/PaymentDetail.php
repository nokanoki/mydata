<?php

namespace Nokanoki;

use Nokanoki\Enums\PaymentType;

class PaymentDetail
{
    public PaymentType $type;
    public float $amount;
    public function __construct(float $amount, PaymentType $type = PaymentType::METRITA)
    {
        $this->type = $type;
        $this->amount = $amount;
    }
}
