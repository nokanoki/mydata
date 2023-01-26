<?php

namespace Nokanoki;

use Exception;
use SimpleXmlElement;

class MyData
{
    private $aadeId;
    private $aadeKey;
    private $testServer;
    public function __construct($aadeId, $aadeKey, $testServer = true)
    {
        $this->aadeId = $aadeId;
        $this->aadeKey = $aadeKey;
        $this->testServer = $testServer;
    }
    public function cancelInvoice($mark)
    {
        throw new Exception("Not impl");
        $ch = curl_init();
        if ($this->testServer)
            curl_setopt($ch, CURLOPT_URL, "https://mydata-dev.azure-api.net/CancelInvoice?mark=$mark");
        else
            curl_setopt($ch, CURLOPT_URL, "https://mydatapi.aade.gr/myDATA//CancelInvoice?mark=$mark");


        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "aade-user-id: $this->aadeId",
            "Ocp-Apim-Subscription-Key: $this->aadeKey",
            'Content-Type: application/xml'
        ));
        $responseStr = curl_exec($ch);
        if ($responseStr === FALSE)
            throw new Exception('Κατι δε πηγε καλα ' . curl_error($ch));
        curl_close($ch);
        $responseXml = simplexml_load_string($responseStr);
        print_r($responseXml);
        if (!strcmp($responseXml->response->statusCode, 'Success')) {
            return (string)$responseXml->response->cancellationMark;
        }
        return 0;
    }


    public function sendInvoices($invoices)
    {
        if (count($invoices) != 1)
            throw new Exception('Δεν δουλευει με πολλαπλα invoices, βαλε μονο ενα');
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><InvoicesDoc xmlns="http://www.aade.gr/myDATA/invoice/v1.0" xmlns:ic="https://www.aade.gr/myDATA/incomeClassificaton/v1.0"></InvoicesDoc>');
        foreach ($invoices as $inv) {
            $invNode = $xml->addChild('invoice');
            $issuerNode = $invNode->addChild('issuer');
            $issuerNode->addChild('vatNumber', $inv['issuerVat']);
            $issuerNode->addChild('country', $inv['issuerCountry'] ?? 'GR');
            $issuerNode->addChild('branch', $inv['issuerBranch'] ?? '0');

            $counterpart = $invNode->addChild('counterpart');
            $counterpart->addChild('vatNumber', $inv['counterpartVat']);
            $counterpart->addChild('country', $inv['counterpartCountry'] ?? 'GR');
            $counterpart->addChild('branch', $inv['counterpartBranch'] ?? '0');

            $invHeader = $invNode->addChild('invoiceHeader');
            $invHeader->addChild('series', $inv['series']);
            $invHeader->addChild('aa', $inv['aa']);
            $invHeader->addChild('issueDate', date('Y-m-d', $inv['issueDate'] ?? time()));
            $invHeader->addChild('invoiceType', $inv['invoiceType'] ?? '1.1'); //1.1 => τιμολογιο πωλησης
            $invHeader->addChild('currency', $inv['currency'] ?? 'EUR');

            $paymentMethods = $invNode->addChild('paymentMethods');
            $payment = $paymentMethods->addChild('paymentMethodDetails');
            $payment->addChild('type', $inv['paymentType'] ?? '3'); //3 = μετρητα
            //$payment->addChild('amount', $inv['paymentAmount']); αυτο θα παει πιο κατω να παρω τιμη

            $line = 1;
            $totalNetValue = 0;
            $totalVatValue = 0;
            foreach ($inv['rows'] as $row) {
                $invRow = $invNode->addChild('invoiceDetails');
                $invRow->addChild('lineNumber', $line);

                //$invRow->addChild('quantity', $row['quantity']); DEN DOYLEYEI
                $invRow->addChild('netValue', $row['netValue']);
                $invRow->addChild('vatCategory', $row['vatCategory'] ?? '1');
                $invRow->addChild('vatAmount', $row['vatAmount']);

                $ict = $invRow->addChild('incomeClassification');
                $ict->addChild('ic:classificationType', $inv['classificationType'] ?? 'E3_561_001', 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');
                $ict->addChild('ic:classificationCategory', $inv['classificationCategory'] ?? 'category1_1', 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');
                $ict->addChild('ic:amount', $row['netValue'], 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');

                $line++;
                $totalNetValue += ($row['netValue']); // * $row['quantity']);
                $totalVatValue += ($row['vatAmount']); // * $row['quantity']);
            }
            //αυτο ηταν πιο πανω
            $payment->addChild('amount', $inv['paymentAmount'] ?? ($totalNetValue + $totalVatValue));
            //

            $summary = $invNode->addChild('invoiceSummary');
            $summary->addChild('totalNetValue', $inv['totalNetValue'] ?? $totalNetValue);
            $summary->addChild('totalVatAmount', $inv['totalVatAmount'] ?? $totalVatValue);
            $summary->addChild('totalWithheldAmount', $inv['totalWithheldAmount'] ?? '0');
            $summary->addChild('totalFeesAmount', $inv['totalFeesAmount'] ?? '0');
            $summary->addChild('totalStampDutyAmount', $inv['totalStampDutyAmount'] ?? '0');
            $summary->addChild('totalOtherTaxesAmount', $inv['totalOtherTaxesAmount'] ?? '0');
            $summary->addChild('totalDeductionsAmount', $inv['totalDeductionsAmount'] ?? '0');
            $summary->addChild('totalGrossValue', $inv['totalGrossValue'] ?? ($totalNetValue + $totalVatValue));

            $clf = $summary->addChild('incomeClassification');
            $clf->addChild('ic:classificationType', $inv['classificationType'] ?? 'E3_561_001', 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');
            $clf->addChild('ic:classificationCategory', $inv['classificationCategory'] ?? 'category1_1', 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');
            $clf->addChild('ic:amount', $inv['totalNetValue'] ?? $totalNetValue, 'https://www.aade.gr/myDATA/incomeClassificaton/v1.0');
        }

        $ch = curl_init();
        if ($this->testServer) {
            curl_setopt($ch, CURLOPT_URL, 'https://mydataapidev.aade.gr/SendInvoices');
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        } else
            curl_setopt($ch, CURLOPT_URL, 'https://mydatapi.aade.gr/myDATA/SendInvoices');


        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "aade-user-id: $this->aadeId",
            "Ocp-Apim-Subscription-Key: $this->aadeKey",
            'Content-Type: application/xml'
        ));
        $responseStr = curl_exec($ch);
        if ($responseStr === FALSE)
            throw new Exception('Κατι δε πηγε καλα ' . curl_error($ch));
        curl_close($ch);

        try {
            $responseXml = simplexml_load_string($responseStr);
            //errors
            if (!strcmp($responseXml->response->statusCode, 'ValidationError')) {
                if (!strcmp($responseXml->response->errors[0]->error->code, '228')) {
                    //το 228 το στελνει σιγουρα οταν ξαναστελνουμε το ιδιο τιμολογιο
                    throw new Exception('Το τιμολογιο εχει ξανασταλει');
                } else {
                    //βλεποντας και κανοντας
                    throw new Exception('Κατι πηγε στραβα ' . $responseXml->asXml());
                }
            }
            //Success μεταβηβαση τιμολογιου
            else if (!strcmp($responseXml->response->statusCode, 'Success')) {
                return [
                    'uid' => (string)$responseXml->response->invoiceUid,
                    'mark' => (string)$responseXml->response->invoiceMark,
                    'authCode' => (string)$responseXml->response->authenticationCode
                ];
            }
            //το unknown
            else {
                throw new Exception('Αγνωστο ' . $responseXml->asXml());
            }
        } catch (Exception $e) {
            print('κατι δε πηγε καλα ' . $e->getMessage());
        }
    }
    //test real server worked
    public function requestMyIncomes()
    {
        $dateFrom = '01/01/2022';
        $dateTo = '17/09/2022';

        $ch = curl_init();
        if ($this->testServer)
            curl_setopt($ch, CURLOPT_URL, "https://mydata-dev.azure-api.net/RequestMyIncome?dateFrom=$dateFrom&dateTo=$dateTo");
        else
            curl_setopt($ch, CURLOPT_URL, "https://mydatapi.aade.gr/myDATA/RequestMyIncome?dateFrom=$dateFrom&dateTo=$dateTo");


        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "aade-user-id: $this->aadeId",
            "Ocp-Apim-Subscription-Key: $this->aadeKey"
        ));
        $responseStr = curl_exec($ch);
        if ($responseStr === FALSE)
            throw new Exception('Κατι δε πηγε καλα ' . curl_error($ch));
        curl_close($ch);
        print($responseStr);
        $responseXml = simplexml_load_string($responseStr);
        print_r($responseXml);
        if (!strcmp($responseXml->response->statusCode, 'Success')) {
            return 0;
        }
        return 0;
    }
}
