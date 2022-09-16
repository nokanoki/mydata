composer 
```
composer require anonymopapaki/mydata
```

Αποστολη τιμολογιου

```php
<?php
require_once 'vendor/autoload.php';

use Anonymopapaki\Mydata\MyData;


$data = new MyData('<user>', '<api key>',[$testServer = true]);//false για production
print_r($data->sendInvoices(array(
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
        'series' => 'σειρα',
        'aa' => 'α/α',
        'rows' => array(
            [
                'quantity' => '1',
                'netValue' => '7.50',
                'vatAmount' => '1.80',
                //φπα 24% default
                //'vatCategory' => '1',
            ]
        ),
    ],
)));

```