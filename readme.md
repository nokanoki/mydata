composer 
```
composer require nokanoki/mydata
```

Αποστολη τιμολογιου

```php
<?php
require_once 'vendor/autoload.php';

use Nokanoki\Enums\ClassificationCategory;
use Nokanoki\Enums\ClassificationType;
use Nokanoki\Enums\Currency;
use Nokanoki\MyData;
use Nokanoki\Enums\InvoiceType;
use Nokanoki\Enums\VatCategory;
use Nokanoki\InvoiceParty;

$data = new MyData('user', 'token');
$real = new MyData('user', 'token', false);

//$real->requestMyIncomes();


//echo InvoiceType::APODIKSI_LIANIKIS_POLISIS->value;
/*
$invoice = new Invoice(
    InvoiceType::APODIKSI_LIANIKIS_POLISIS,
    ClassificationType::E3_561_001,
    ClassificationCategory::CATEGORY_1_1,
    Currency::EUR,
    'A',
    1,
    time()
);
*/
//or
$invoice = $data->makeInvoice(
    InvoiceType::APODIKSI_LIANIKIS_POLISIS,
    ClassificationType::E3_561_003,
    ClassificationCategory::CATEGORY_1_1,
    Currency::EUR,
    'A',
    1,
    time()
);


$invoice->setIssuer('139209465');
//$invoice->setCounterparty('801255659');
$invoice->addRow(7.5, VatCategory::FPA_24);
$invoice->generatePayment();
//or 
//$invoice->addPayment(new PaymentDetail(9.3, PaymentType::METRITA));
$invoice->generateSummary();


$ret = $data->SendInvoice($invoice);
var_dump($ret);
return;


```
