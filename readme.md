composer 
```
composer require anonymopapaki/mydata
```

Αποστολη τιμολογιου

```php
<?php
require_once 'vendor/autoload.php';

use Anonymopapaki\Mydata\MyData;


$data = new MyData('user', 'apikey',[$testServer = true/*false για production*/]);


$ret = $data->sendInvoices(array(
    [
        'issuerVat' => 'αφμ μου',
        'counterpartVat' => 'αφμ του',
        /* 
        //τιμολογιο πωλησης defualt
        'invoiceType' => '1.1',
        //πωληση αγαθων
        'classificationType' => 'E3_561_001',
        //εσοδα απο εμπορευματα
        'classificationCategory' => 'category1_1',
        //μετρητα
        'paymentType' => '3',
        */
        'series' => 'a',
        'aa' => '1929',
        'rows' => array(
            [
                'netValue' => '7.50',
                'vatAmount' => '1.80',
                //'vatCategory' => '1',
            ]
        ),
    ],
));

print_r($ret);
$cancelationMark = $data->cancelInvoice($ret['mark']);
print($cancelationMark);

```