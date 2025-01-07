<?php

namespace Botble\Callpay\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Illuminate\Support\ServiceProvider;

class CallpayServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        if (! is_plugin_active('payment')) {
            return;
        }

        $this->setNamespace('plugins/callpay')
            ->loadHelpers()
            ->loadRoutes()
            ->loadAndPublishViews()
            ->publishAssets();

        $this->app->register(HookServiceProvider::class);

        $config = $this->app['config'];

        $config->set([
            'callpay.usernameKey' => get_payment_setting('username', CALLPAY_PAYMENT_METHOD_NAME) ?? getenv('CALLPAY_USERNAME_KEY'),
            'callpay.passwordKey' => get_payment_setting('password', CALLPAY_PAYMENT_METHOD_NAME) ?? getenv('CALLPAY_PASSWORD_KEY'),
            'callpay.saltKey' => get_payment_setting('salt_key', CALLPAY_PAYMENT_METHOD_NAME) ?? getenv('CALLPAY_SALT_KEY'),
            'callpay.userId' => get_payment_setting('user_id', CALLPAY_PAYMENT_METHOD_NAME) ?? getenv('CALLPAY_USER_ID'),
            'callpay.paymentUrl' => 'https://services.callpay.com/api',
        ]);
    }
}
