<?php

namespace Botble\Callpay;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Facades\Setting;

class Plugin extends PluginOperationAbstract
{
    /**
     * Remove plugin data.
     *
     * @return void
     */
    public static function remove(): void
    {
        Setting::delete([
            'payment_callpay_name',
            'payment_callpay_description',
            'payment_callpay_username',
            'payment_callpay_salt_key',
            'payment_callpay_user_id',
            'payment_callpay_status',
        ]);
    }
}
