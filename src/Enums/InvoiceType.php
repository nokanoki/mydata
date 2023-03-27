<?php

namespace Nokanoki\Enums;

enum InvoiceType: string
{
    case TIMOLOGIO_POLISIS = '1.1';
    case TIMOLOGIO_PAROXIS = '2.1';
    case TIMOLOGIO_PISTOTIKO_SYS = '5.1';
    case APODIKSI_LIANIKIS_POLISIS = '11.1';
    case APODIKSI_YPERESION = '11.2';
}
