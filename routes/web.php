<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Botble\Callpay\Http\Controllers', 'middleware' => ['web', 'core']], function (): void {
    Route::get('callpay/payment/callback', [
        'as' => 'callpay.payment.callback',
        'uses' => 'CallpayController@getPaymentStatus',
    ]);
});
