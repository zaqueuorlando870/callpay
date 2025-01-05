<?php

namespace Botble\Callpay\Services\Gateways;

use Botble\Callpay\Services\Abstracts\CallpayPaymentAbstract;
use Illuminate\Http\Request;

class CallpayPaymentService extends CallpayPaymentAbstract
{
    public function makePayment(Request $request)
    {
    }

    public function afterMakePayment(Request $request)
    {
    }

    /**
     * List currencies supported https://www.callpay.com/contact-us/
     */
    public function supportedCurrencyCodes(): array
    {
        return [
            'AOA',  
            'ZAR', 
        ];
    }
}
