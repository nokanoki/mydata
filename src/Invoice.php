<?php

namespace Nokanoki;

use Nokanoki\Enums\ClassificationCategory;
use Nokanoki\Enums\ClassificationType;
use Nokanoki\Enums\Currency;
use Nokanoki\InvoiceParty;
use Nokanoki\InvoiceRow;
use Nokanoki\Enums\InvoiceType;
use Nokanoki\Enums\PaymentType;
use Nokanoki\Enums\VatCategory;
use SimpleXMLElement;

class Invoice
{
    private InvoiceParty $issuer;
    private InvoiceParty $counterPart;
    private string $series;
    private int $aa;
    private int $issueDate;
    private InvoiceType $type;
    private Currency $currency;

    //mono gia pistotiko
    public ?string $correlatedInvoices;

    public float $totalNetValue;
    public float $totalVatValue;
    public float $totalWithheldValue;
    public float $totalFeesValue;
    public float $totalStampDutyValue;
    public float $totalOtherTaxesValue;
    public float $totalDeductionValue;
    public float $totalGrossValue;

    private ClassificationType $classificationType;
    private ClassificationCategory $classificationCategory;

    private IncomeClassification $incomeClassification;

    public function __construct(
        InvoiceType $type,
        ClassificationType $classificationType,
        ClassificationCategory $classificationCategory,
        Currency $currency,
        string $series,
        int $aa,
        int $issueDate,
        string $correlatedInvoices = null
    ) {
        $this->type = $type;
        $this->classificationType = $classificationType;
        $this->classificationCategory = $classificationCategory;
        $this->currency = $currency;
        $this->series = $series;
        $this->aa = $aa;
        $this->issueDate = $issueDate;
        $this->correlatedInvoices = $correlatedInvoices;
    }

    public function setIssuer($afm, $branch = '0', $country = 'GR')
    {
        $this->issuer = new InvoiceParty($afm, $branch, $country);
        return $this;
    }
    public function setCounterparty($afm, $branch = '0', $country = 'GR')
    {
        $this->counterPart = new InvoiceParty($afm, $branch, $country);
        return $this;
    }




    private array $payments;
    public function addPayment(PaymentDetail $payment)
    {
        $this->payments[] = $payment;
    }
    private array $rows;
    private int $rowLineNumber = 1;
    public function addRow(float $netValue, VatCategory $vatCategory, string $description = null, ClassificationType $classificationType = null, ClassificationCategory $classificationCategory = null)
    {
        $row = new InvoiceRow;
        $row->lineNumber = $this->rowLineNumber;
        $row->netValue = $netValue;
        $row->vatCategory = $vatCategory;
        $row->description = $description;
        $row->vatValue = VatCategory::calcVat($netValue, $vatCategory);
        $row->classification = new IncomeClassification(
            $row->netValue,
            $classificationType ?? $this->classificationType,
            $classificationCategory ?? $this->classificationCategory
        );

        $this->rowLineNumber++;
        $this->rows[] = $row;
        return $this;
    }

    public function generatePayment(PaymentType $type = PaymentType::METRITA)
    {
        $value = 0;
        foreach ($this->rows as $row) {
            $value += $row->netValue;
            $value += $row->vatValue;
        }
        $this->payments = array();
        $this->addPayment(new PaymentDetail($value, $type));
        return $this;
    }

    public function generateSummary()
    {
        $this->totalNetValue = 0;
        $this->totalVatValue = 0;
        foreach ($this->rows as $row) {
            $this->totalNetValue += $row->netValue;
            $this->totalVatValue += $row->vatValue;
        }

        $this->totalWithheldValue ??= 0;
        $this->totalFeesValue ??= 0;
        $this->totalStampDutyValue ??= 0;
        $this->totalOtherTaxesValue ??= 0;
        $this->totalDeductionValue ??= 0;
        $this->totalGrossValue = $this->totalNetValue
            + $this->totalVatValue
            + $this->totalWithheldValue
            + $this->totalFeesValue
            + $this->totalStampDutyValue
            + $this->totalDeductionValue;

        $this->incomeClassification = new IncomeClassification(
            $this->totalNetValue,
            $this->classificationType,
            $this->classificationCategory
        );
        return $this;
    }

    public function toMydataXML()
    {
        $number = function ($value) {
            return number_format($value, 2, '.', '');
        };
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><InvoicesDoc xmlns="http://www.aade.gr/myDATA/invoice/v1.0" xmlns:ic="https://www.aade.gr/myDATA/incomeClassificaton/v1.0"></InvoicesDoc>');
        $invoice = $xml->addChild('invoice');

        $issuer = $invoice->addChild('issuer');
        $issuer->addChild('vatNumber', $this->issuer->vatNumber);
        $issuer->addChild('country', $this->issuer->country);
        $issuer->addChild('branch', $this->issuer->branch);

        if (isset($this->counterPart)) {
            $counterpart = $invoice->addChild('counterpart');
            $counterpart->addChild('vatNumber', $this->counterPart->vatNumber);
            $counterpart->addChild('country', $this->counterPart->country);
            $counterpart->addChild('branch', $this->counterPart->branch);
        }

        $header = $invoice->addChild('invoiceHeader');
        $header->addChild('series', $this->series);
        $header->addChild('aa', $this->aa);
        $header->addChild('issueDate', date('Y-m-d', $this->issueDate));
        $header->addChild('invoiceType', $this->type->value);
        $header->addChild('currency', $this->currency->value);
        //an einai pistotiko sysxetizomeno tote prepei na exei to mark
        if ($this->correlatedInvoices) {
            $header->addChild('correlatedInvoices', $this->correlatedInvoices);
        }

        $payments = $invoice->addChild('paymentMethods');
        foreach ($this->payments as $payment) {
            $tmp = $payments->addChild('paymentMethodDetails');
            $tmp->addChild('type', $payment->type->value);
            $tmp->addChild('amount', $number($payment->amount));
        }

        foreach ($this->rows as $row) {
            $tmp = $invoice->addChild('invoiceDetails');
            $tmp->addChild('lineNumber', $row->lineNumber);
            $tmp->addChild('netValue', $number($row->netValue));
            $tmp->addChild('vatCategory', $row->vatCategory->value);
            $tmp->addChild('vatAmount', $number($row->vatValue));

            $ict = $tmp->addChild('incomeClassification');
            $ict->addChild('ic:classificationType', $row->classification->type->value, 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');
            $ict->addChild('ic:classificationCategory', $row->classification->category->value, 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');
            $ict->addChild('ic:amount', $number($row->classification->netValue), 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');
        }

        $summary = $invoice->addChild('invoiceSummary');
        $summary->addChild('totalNetValue', $number($this->totalNetValue));
        $summary->addChild('totalVatAmount', $number($this->totalVatValue));
        $summary->addChild('totalWithheldAmount', $number($this->totalWithheldValue));
        $summary->addChild('totalFeesAmount', $number($this->totalFeesValue));
        $summary->addChild('totalStampDutyAmount', $number($this->totalStampDutyValue));
        $summary->addChild('totalOtherTaxesAmount', $number($this->totalOtherTaxesValue));
        $summary->addChild('totalDeductionsAmount', $number($this->totalDeductionValue));
        $summary->addChild('totalGrossValue', $number($this->totalGrossValue));

        $clf = $summary->addChild('incomeClassification');
        $clf->addChild('ic:classificationType', $this->incomeClassification->type->value, 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');
        $clf->addChild('ic:classificationCategory', $this->incomeClassification->category->value, 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');
        $clf->addChild('ic:amount', $number($this->totalNetValue), 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');

        return $xml->asXML();
    }
}
