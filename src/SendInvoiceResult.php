<?php

namespace Nokanoki;

class SendInvoiceResult
{
    public string $uid;
    public string $mark;
    public string $error;
    public static function fromSuccess(string $uid, string $mark)
    {
        $res = new SendInvoiceResult;
        $res->uid = $uid;
        $res->mark = $mark;
        return $res;
    }
    public static function fromError(string $error)
    {
        $res = new SendInvoiceResult;
        $res->error = $error;
        return $res;
    }
}
