<?php

namespace Botble\Callpay\Services;

use Exception;
use Zaqueuorlando870\Callpay\Callpay as BaseCallpay;

class Callpay extends BaseCallpay
{
    public function refundOrder($paymentId, $amount)
    {
        $relativeUrl = '/v1/gateway-transaction/refund';

        $data = [
            'body' => json_encode([
                'transaction' => $paymentId,
                'amount' => $amount * 100,
            ]),
        ];

        do_action('payment_before_making_api_request', CALLPAY_PAYMENT_METHOD_NAME, $data);

        $this->response = $this->client->post($this->baseUrl . $relativeUrl, $data);

        do_action('payment_after_api_response', CALLPAY_PAYMENT_METHOD_NAME, $data, (array) $this->response);

        if ($this->isValid()) {
            return $this->getResponse();
        }

        throw new Exception('Invalid Refund Order Callpay');
    }

    protected function getResponse(): array
    {
        return json_decode($this->response->getBody(), true);
    }

    public function isValid(): bool
    {
        return $this->getResponse()['status'];
    }

    public function getPaymentDetails($transactionId)
    {
        $relativeUrl = '/v1/gateway-transaction/' . $transactionId;

        do_action('payment_before_making_api_request', CALLPAY_PAYMENT_METHOD_NAME, ['transaction_id' => $transactionId]);

        $this->response = $this->client->get($this->baseUrl . $relativeUrl);

        do_action('payment_after_api_response', CALLPAY_PAYMENT_METHOD_NAME, ['transaction_id' => $transactionId], (array) $this->response);

        if ($this->isValid()) {
            return $this->getResponse();
        }

        throw new Exception('Invalid Get Payment Details Callpay');
    }

    public function getListTransactions(array $params = [])
    {
        $relativeUrl = '/v1/user/'.getenv('FROM_CALLPAY_USER_ID').'/gateway-transaction' . ($params ? ('?' . http_build_query($params)) : '');

        do_action('payment_before_making_api_request', CALLPAY_PAYMENT_METHOD_NAME, $params);

        $this->response = $this->client->get($this->baseUrl . $relativeUrl);

        do_action('payment_after_api_response', CALLPAY_PAYMENT_METHOD_NAME, $params, (array) $this->response);

        if ($this->isValid()) {
            return $this->getResponse();
        }

        throw new Exception('Invalid Get List Transactions Callpay');
    }

    public function getRefundDetails($refundId)
    {
        $relativeUrl = '/v1/gateway-transaction/refund/' . $refundId;

        do_action('payment_before_making_api_request', CALLPAY_PAYMENT_METHOD_NAME, ['refund_id' => $refundId]);

        $this->response = $this->client->get($this->baseUrl . $relativeUrl);

        do_action('payment_after_api_response', CALLPAY_PAYMENT_METHOD_NAME, ['refund_id' => $refundId], (array) $this->response);

        if ($this->isValid()) {
            return $this->getResponse();
        }

        throw new Exception('Invalid Refund Order Callpay');
    }
}
