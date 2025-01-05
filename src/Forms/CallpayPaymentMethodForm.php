<?php

namespace Botble\Callpay\Forms;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\TextField;
use Botble\Payment\Forms\PaymentMethodForm;

class CallpayPaymentMethodForm extends PaymentMethodForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->paymentId(CALLPAY_PAYMENT_METHOD_NAME)
            ->paymentName('Callpay')
            ->paymentDescription(__('Customer can buy product and pay directly using Angolan Banks via :name', ['name' => 'Callpay']))
            ->paymentLogo(url('vendor/core/plugins/callpay/images/callpay.png'))
            ->paymentUrl('https://callpay.com')
            ->paymentInstructions(view('plugins/callpay::instructions')->render())
            ->add(
                sprintf('payment_%s_username', CALLPAY_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Username Key'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('username', CALLPAY_PAYMENT_METHOD_NAME))
            )
            ->add(
                sprintf('payment_%s_password', CALLPAY_PAYMENT_METHOD_NAME),
                'password',
                TextFieldOption::make()
                    ->label(__('Password Key'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('password', CALLPAY_PAYMENT_METHOD_NAME))
            )

            ->add(
                sprintf('payment_%s_salt_key', CALLPAY_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Salt Key'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('salt_key', CALLPAY_PAYMENT_METHOD_NAME))
            );
    }
}
