@if (get_payment_setting('status', CALLPAY_PAYMENT_METHOD_NAME) == 1)
    <x-plugins-payment::payment-method
        :name="CALLPAY_PAYMENT_METHOD_NAME"
        paymentName="Callpay"
        :supportedCurrencies="(new Botble\Callpay\Services\Gateways\CallpayPaymentService)->supportedCurrencyCodes()"
    >
        <x-slot name="currencyNotSupportedMessage">
            <p class="mt-1 mb-0">
                {{ __('Learn more') }}:
                {{ Html::link('https://www.callpay.com/contact-us/', attributes: ['target' => '_blank', 'rel' => 'nofollow']) }}.
            </p>
        </x-slot>
    </x-plugins-payment::payment-method>
@endif
