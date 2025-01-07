<?php

/*
 * This file is part of the Laravel Callpay package.
 *
 * (c) Prosper Zaqueu Orlando <zaqueuorlando870@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Botble\Callpay\Services\Handler;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Botble\Callpay\Handler\Exceptions\InvalidArgumentException;
use Botble\Callpay\Handler\Exceptions\IsNullException;
use Botble\Callpay\Handler\Exceptions\PaymentVerificationFailedException;

class CallpayHandler
{
    /**
     * Transaction Verification Successful
     */
    const VS = 'Verification successful';

    /**
     *  Invalid Transaction reference
     */
    const ITF = "Invalid transaction reference";

    /**
     * Issue Username Key from your Callpay Dashboard
     * @var string
     */
    protected $username;

     /**
     * Issue Password Key from your Callpay Dashboard
     * @var string
     */
    protected $password;


     /**
     * Issue Salt Key from your Callpay Dashboard
     * @var string
     */
    protected $saltKey;

    /**
     * Instance of Client
     * @var Client
     */
    protected $client;

    /**
     *  Response from requests made to Callpay
     * @var mixed
     */
    protected $response;

    /**
     * Callpay API base Url
     * @var string
     */
    protected $baseUrl;

    /**
     * Authorization Url - Callpay payment page
     * @var string
     */
    protected $authorizationUrl;

    public function __construct()
    {
        $this->setKey();
        $this->setBaseUrl();
        $this->setRequestOptions();
    }

    /**
     * Get Base Url from Callpay config file
     */
    public function setBaseUrl()
    {
        $this->baseUrl = Config::get('callpay.paymentUrl');
    }

    /**
     * Get secret key from Callpay config file
     */
    public function setKey()
    {
        $this->username = Config::get('callpay.usernameKey');
        $this->password = Config::get('callpay.passwordKey');
        return base64_encode($this->username . ':' . $this->password);
    }

    /**
     * Set options for making the Client request
     */
    private function setRequestOptions()
    {  
        $authBearer = $this->setKey();

        $this->client = new Client([
            'headers' => [
                'Authorization' => 'Basic ' . $authBearer,
            ],
        ]);
    }


    /**

     * Initiate a payment request to CallpayHandler
     * Included the option to pass the payload to this method for situations
     * when the payload is built on the fly (not passed to the controller from a view)
     * @return CallpayHandler
     */

    public function makePaymentRequest($data = null)
    {
        if ($data == null) {

            $quantity = intval(request()->quantity ?? 1);
            $data = [
                        'form_params' => [
                            'amount' => intval(request()->amount) * $quantity,
                            'merchant_reference' => request()->reference,
                        ]
                    ];

        
        }
        $this->setHttpResponse('/v1/payment-key', 'POST', $data);
        return $this;
    }


    /**
     * @param string $relativeUrl
     * @param string $method
     * @param array $body
     * @return CallpayHandler
     * @throws IsNullException
     */private function setHttpResponse($relativeUrl, $method, $body = [])
        {
            if (!is_string($method)) {
                throw new InvalidArgumentException("Method must be a string");
            }
        
            if (empty($method)) {
                throw new IsNullException("Empty method not allowed");
            }
        
            $this->response = $this->client->{strtolower($method)}($this->baseUrl . $relativeUrl, $body); 
            return $this;
        }

    /**
     * Get the authorization url from the callback response
     * @return CallpayHandler
     */
    public function getAuthorizationUrl($data = null)
    { 
        $this->makePaymentRequest($data);
        $dataArray = [
            'status' => isset($this->getResponse()['key']),
            'data' => $this->getResponse(),
        ]; 
        $this->url = $dataArray['data']['url'];
        return $this;
    }

    /**
     * Get the authorization callback response
     * In situations where Laravel serves as an backend for a detached UI, the api cannot redirect
     * and might need to take different actions based on the success or not of the transaction
     * @return array
     */
    public function getAuthorizationResponse($data)
    { 
        $this->makePaymentRequest($data); 
        $dataArray = [
                'status' => isset($this->getResponse()['key']),
                'data' => $this->getResponse(),
            ]; 
        $this->url = $dataArray['data']['url']; 
        return $dataArray;
    }

    /**
     * Hit Callpay Gateway to Verify that the transaction is valid
     */
    private function verifyTransactionAtGateway($transaction_id = null)
    {
        $transactionRef = $transaction_id ?? request()->query('trxref');

        $relativeUrl = "/v1/gateway-transaction/{$transactionRef}";

        $this->response = $this->client->get($this->baseUrl . $relativeUrl, []);
    }

    /**
     * True or false condition whether the transaction is verified
     * @return boolean
     */
    public function isTransactionVerificationValid($transaction_id = null)
    {
        $this->verifyTransactionAtGateway($transaction_id);

        $result = $this->getResponse()['message'];

        switch ($result) {
            case self::VS:
                $validate = true;
                break;
            case self::ITF:
                $validate = false;
                break;
            default:
                $validate = false;
                break;
        }

        return $validate;
    }

    /**
     * Get Payment details if the transaction was verified successfully
     * @return json
     * @throws PaymentVerificationFailedException
     */
    public function getPaymentData()
    {
        if ($this->isTransactionVerificationValid()) {
            return $this->getResponse();
        } else {
            throw new PaymentVerificationFailedException("Invalid Transaction Reference");
        }
    }

    /**
     * Fluent method to redirect to Callpay Payment Page
     */
    public function redirectNow()
    {
        return redirect($this->url);
    }

    /**
     * Get Access code from transaction callback respose
     * @return string
     */
    public function getAccessCode()
    {
        return $this->getResponse()['data']['access_code'];
    }

    /**
     * Generate a Unique Transaction Reference
     * @return string
     */
    public function genTranxRef()
    {
        return TransRef::getHashedToken();
    }

    /**
     * Get all the customers that have made transactions on your platform
     * @return array
     */
    public function getAllCustomers()
    {
        $this->setRequestOptions();

        return $this->setHttpResponse("/customer", 'GET', [])->getData();
    }

    /**
     * Get all the plans that you have on Callpay
     * @return array
     */
    public function getAllPlans()
    {
        $this->setRequestOptions();

        return $this->setHttpResponse("/plan", 'GET', [])->getData();
    }

    /**
     * Get all the transactions that have happened overtime
     * @return array
     */
    public function getAllTransactions()
    {
        $this->setRequestOptions();

        return $this->setHttpResponse("/transaction", 'GET', [])->getData();
    }

    /**
     * Get the whole response from a get operation
     * @return array
     */
    private function getResponse()
    {
        return json_decode($this->response->getBody(), true);
    }

    /**
     * Get the data response from a get operation
     * @return array
     */
    private function getData()
    {
        return $this->getResponse()['data'];
    }

    /**
     * Create a plan
     */
    public function createPlan()
    {
        $data = [
            "name" => request()->name,
            "description" => request()->desc,
            "amount" => intval(request()->amount),
            "interval" => request()->interval,
            "send_invoices" => request()->send_invoices,
            "send_sms" => request()->send_sms,
            "currency" => request()->currency,
        ];

        $this->setRequestOptions();

        return $this->setHttpResponse("/plan", 'POST', $data)->getResponse();
    }

    /**
     * Fetch any plan based on its plan id or code
     * @param $plan_code
     * @return array
     */
    public function fetchPlan($plan_code)
    {
        $this->setRequestOptions();
        return $this->setHttpResponse('/plan/' . $plan_code, 'GET', [])->getResponse();
    }

    /**
     * Update any plan's details based on its id or code
     * @param $plan_code
     * @return array
     */
    public function updatePlan($plan_code)
    {
        $data = [
            "name" => request()->name,
            "description" => request()->desc,
            "amount" => intval(request()->amount),
            "interval" => request()->interval,
            "send_invoices" => request()->send_invoices,
            "send_sms" => request()->send_sms,
            "currency" => request()->currency,
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/plan/' . $plan_code, 'PUT', $data)->getResponse();
    }

    /**
     * Create a customer
     */
    public function createCustomer($data = null)
    {
        if ($data == null) {

            $data = [
                "email" => request()->email,
                "first_name" => request()->fname,
                "last_name" => request()->lname,
                "phone" => request()->phone,
                "metadata" => request()->additional_info /* key => value pairs array */

            ];
        }
        
        $this->setRequestOptions();
        return $this->setHttpResponse('/customer', 'POST', $data)->getResponse();
    }

    /**
     * Fetch a customer based on id or code
     * @param $customer_id
     * @return array
     */
    public function fetchCustomer($customer_id)
    {
        $this->setRequestOptions();
        return $this->setHttpResponse('/customer/' . $customer_id, 'GET', [])->getResponse();
    }

    /**
     * Update a customer's details based on their id or code
     * @param $customer_id
     * @return array
     */
    public function updateCustomer($customer_id)
    {
        $data = [
            "email" => request()->email,
            "first_name" => request()->fname,
            "last_name" => request()->lname,
            "phone" => request()->phone,
            "metadata" => request()->additional_info /* key => value pairs array */

        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/customer/' . $customer_id, 'PUT', $data)->getResponse();
    }

    /**
     * Export transactions in .CSV
     * @return array
     */
    public function exportTransactions()
    {
        $data = [
            "from" => request()->from,
            "to" => request()->to,
            'settled' => request()->settled
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/transaction/export', 'GET', $data)->getResponse();
    }

    /**
     * Create a subscription to a plan from a customer.
     */
    public function createSubscription()
    {
        $data = [
            "customer" => request()->customer, //Customer email or code
            "plan" => request()->plan,
            "authorization" => request()->authorization_code
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/subscription', 'POST', $data)->getResponse();
    }

    /**
     * Get all the subscriptions made on Callpay.
     *
     * @return array
     */
    public function getAllSubscriptions()
    {
        $this->setRequestOptions();

        return $this->setHttpResponse("/subscription", 'GET', [])->getData();
    }

    /**
     * Get customer subscriptions
     *
     * @param integer $customer_id
     * @return array
     */
    public function getCustomerSubscriptions($customer_id)
    {
        $this->setRequestOptions();

        return $this->setHttpResponse('/subscription?customer=' . $customer_id, 'GET', [])->getData();
    }

    /**
     * Get plan subscriptions
     *
     * @param  integer $plan_id
     * @return array
     */
    public function getPlanSubscriptions($plan_id)
    {
        $this->setRequestOptions();

        return $this->setHttpResponse('/subscription?plan=' . $plan_id, 'GET', [])->getData();
    }

    /**
     * Enable a subscription using the subscription code and token
     * @return array
     */
    public function enableSubscription()
    {
        $data = [
            "code" => request()->code,
            "token" => request()->token,
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/subscription/enable', 'POST', $data)->getResponse();
    }

    /**
     * Disable a subscription using the subscription code and token
     * @return array
     */
    public function disableSubscription()
    {
        $data = [
            "code" => request()->code,
            "token" => request()->token,
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/subscription/disable', 'POST', $data)->getResponse();
    }

    /**
     * Fetch details about a certain subscription
     * @param mixed $subscription_id
     * @return array
     */
    public function fetchSubscription($subscription_id)
    {
        $this->setRequestOptions();
        return $this->setHttpResponse('/subscription/' . $subscription_id, 'GET', [])->getResponse();
    }

    /**
     * Create pages you can share with users using the returned slug
     */
    public function createPage()
    {
        $data = [
            "name" => request()->name,
            "description" => request()->description,
            "amount" => request()->amount
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/page', 'POST', $data)->getResponse();
    }

    /**
     * Fetches all the pages the merchant has
     * @return array
     */
    public function getAllPages()
    {
        $this->setRequestOptions();
        return $this->setHttpResponse('/page', 'GET', [])->getResponse();
    }

    /**
     * Fetch details about a certain page using its id or slug
     * @param mixed $page_id
     * @return array
     */
    public function fetchPage($page_id)
    {
        $this->setRequestOptions();
        return $this->setHttpResponse('/page/' . $page_id, 'GET', [])->getResponse();
    }

    /**
     * Update the details about a particular page
     * @param $page_id
     * @return array
     */
    public function updatePage($page_id)
    {
        $data = [
            "name" => request()->name,
            "description" => request()->description,
            "amount" => request()->amount
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/page/' . $page_id, 'PUT', $data)->getResponse();
    }

    /**
     * Creates a subaccount to be used for split payments . Required    params are business_name , settlement_bank , account_number ,   percentage_charge
     *
     * @return array
     */

    public function createSubAccount()
    {
        $data = [
            "business_name" => request()->business_name,
            "settlement_bank" => request()->settlement_bank,
            "account_number" => request()->account_number,
            "percentage_charge" => request()->percentage_charge,
            "primary_contact_email" => request()->primary_contact_email,
            "primary_contact_name" => request()->primary_contact_name,
            "primary_contact_phone" => request()->primary_contact_phone,
            "metadata" => request()->metadata,
            'settlement_schedule' => request()->settlement_schedule
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse('/subaccount', 'POST', array_filter($data))->getResponse();
    }

    /**
     * Fetches details of a subaccount
     * @param subaccount code
     * @return array
     */
    public function fetchSubAccount($subaccount_code)
    {

        $this->setRequestOptions();
        return $this->setHttpResponse("/subaccount/{$subaccount_code}", "GET", [])->getResponse();
    }

    /**
     * Lists all the subaccounts associated with the account
     * @param $per_page - Specifies how many records to retrieve per page , $page - SPecifies exactly what page to retrieve
     * @return array
     */
    public function listSubAccounts($per_page, $page)
    {

        $this->setRequestOptions();
        return $this->setHttpResponse("/subaccount/?perPage=" . (int) $per_page . "&page=" . (int) $page, "GET")->getResponse();
    }


    /**
     * Updates a subaccount to be used for split payments . Required params are business_name , settlement_bank , account_number , percentage_charge
     * @param subaccount code
     * @return array
     */

    public function updateSubAccount($subaccount_code)
    {
        $data = [
            "business_name" => request()->business_name,
            "settlement_bank" => request()->settlement_bank,
            "account_number" => request()->account_number,
            "percentage_charge" => request()->percentage_charge,
            "description" => request()->description,
            "primary_contact_email" => request()->primary_contact_email,
            "primary_contact_name" => request()->primary_contact_name,
            "primary_contact_phone" => request()->primary_contact_phone,
            "metadata" => request()->metadata,
            'settlement_schedule' => request()->settlement_schedule
        ];

        $this->setRequestOptions();
        return $this->setHttpResponse("/subaccount/{$subaccount_code}", "PUT", array_filter($data))->getResponse();
    }

    
    /**
     * Get a list of all supported banks and their properties
     * @param $country - The country from which to obtain the list of supported banks, $per_page - Specifies how many records to retrieve per page , 
     * $use_cursor - Flag to enable cursor pagination on the endpoint
     * @return array
     */
    public function getBanks(?string $country, int $per_page = 50, bool $use_cursor = false)
    {
        if (!$country)
            $country = request()->country ?? 'nigeria';

        $this->setRequestOptions();
        return $this->setHttpResponse("/bank/?country=" . $country . "&use_cursor=" . $use_cursor . "&perPage=" . (int) $per_page, "GET")->getResponse();
    }

    /**
     * Confirm an account belongs to the right customer
     * @param $account_number - Account Number, $bank_code - You can get the list of bank codes by calling the List Banks endpoint
     * @return array
     */
    public function confirmAccount(string $account_number, string $bank_code)
    {

        $this->setRequestOptions();
        return $this->setHttpResponse("/bank/resolve/?account_number=" . $account_number . "&bank_code=" . $bank_code, "GET")->getResponse();
    }
}
