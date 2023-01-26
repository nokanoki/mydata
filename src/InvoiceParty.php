<?php

namespace Nokanoki;

class InvoiceParty
{
    public $vatNumber;
    public $country;
    public $branch;

    public function __construct($vatNumber,  $branch = '0', $country = 'GR')
    {
        $this->vatNumber = $vatNumber;
        $this->country = $country;
        $this->branch = $branch;
    }
}
