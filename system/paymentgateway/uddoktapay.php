<?php

/**
 * UddoktaPay Payment Gateway for PHPNuxBill
 * 
 * API Documentation:
 * - Create Charge: https://uddoktapay.readme.io/reference/create-charge-api-guideline
 * - Verify Payment: https://uddoktapay.readme.io/reference/verify-payment-api-guideline
 * - Validate Webhook: https://uddoktapay.readme.io/reference/validate-webhook
 */

function uddoktapay_validate_config()
{
    global $config;
    if (empty($config['uddoktapay_api_key'])) {
        r2(U . "paymentgateway/uddoktapay", 'e', 'UddoktaPay API Key is required');
    }
    if (empty($config['uddoktapay_api_url'])) {
        r2(U . "paymentgateway/uddoktapay", 'e', 'UddoktaPay API URL is required');
    }
    if (empty($config['uddoktapay_store_id'])) {
        r2(U . "paymentgateway/uddoktapay", 'e', 'UddoktaPay Store ID is required');
    }
}

function uddoktapay_show_config()
{
    global $ui, $config;
    $ui->assign('_title', 'UddoktaPay - Payment Gateway');
    $ui->assign('_system_menu', 'paymentgateway');
    $ui->assign('config', $config);
    $ui->display('paymentgateway/uddoktapay.tpl');
}

function uddoktapay_save_config()
{
    global $admin;
    $api_key = _post('uddoktapay_api_key');
    $api_url = _post('uddoktapay_api_url');
    $store_id = _post('uddoktapay_store_id');
    $webhook_key = _post('uddoktapay_webhook_key');

    $d = ORM::for_table('tbl_appconfig')->where('setting', 'uddoktapay_api_key')->find_one();
    if ($d) {
        $d->value = $api_key;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'uddoktapay_api_key';
        $d->value = $api_key;
        $d->save();
    }

    $d = ORM::for_table('tbl_appconfig')->where('setting', 'uddoktapay_api_url')->find_one();
    if ($d) {
        $d->value = $api_url;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'uddoktapay_api_url';
        $d->value = $api_url;
        $d->save();
    }

    $d = ORM::for_table('tbl_appconfig')->where('setting', 'uddoktapay_store_id')->find_one();
    if ($d) {
        $d->value = $store_id;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'uddoktapay_store_id';
        $d->value = $store_id;
        $d->save();
    }

    $d = ORM::for_table('tbl_appconfig')->where('setting', 'uddoktapay_webhook_key')->find_one();
    if ($d) {
        $d->value = $webhook_key;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'uddoktapay_webhook_key';
        $d->value = $webhook_key;
        $d->save();
    }

    _log('[' . $admin['username'] . ']: UddoktaPay Config Updated', 'Admin', $admin['id']);
    r2(U . "paymentgateway/uddoktapay", 's', 'UddoktaPay Configuration Saved Successfully');
}

function uddoktapay_create_transaction($trx, $user)
{
    global $config;
    
    // Validate configuration
    uddoktapay_validate_config();
    
    // Prepare API request data
    $invoice_id = $trx['id'];
    $amount = $trx['price'];
    $return_url = U . 'order/view/' . $invoice_id;
    $cancel_url = U . 'order/package';
    $webhook_url = U . 'callback/uddoktapay';
    
    $api_data = [
        'store_id' => $config['uddoktapay_store_id'],
        'amount' => $amount,
        'payment_type' => 'no-emi',
        'currency' => 'BDT',
        'tran_id' => $invoice_id,
        'success_url' => $return_url,
        'fail_url' => $cancel_url,
        'cancel_url' => $cancel_url,
        'ipn_url' => $webhook_url,
        'cus_name' => $user['fullname'],
        'cus_email' => $user['email'],
        'cus_add1' => $user['address'] ?: 'N/A',
        'cus_add2' => 'N/A',
        'cus_city' => 'N/A',
        'cus_state' => 'N/A',
        'cus_postcode' => 'N/A',
        'cus_country' => 'Bangladesh',
        'cus_phone' => $user['phonenumber'],
        'cus_fax' => 'N/A',
        'ship_name' => $user['fullname'],
        'ship_add1' => $user['address'] ?: 'N/A',
        'ship_add2' => 'N/A',
        'ship_city' => 'N/A',
        'ship_state' => 'N/A',
        'ship_postcode' => 'N/A',
        'ship_country' => 'Bangladesh',
        'product_name' => $trx['plan_name'],
        'product_category' => 'Internet Package',
        'product_profile' => 'general',
        'hours_till_departure' => '',
        'flight_type' => '',
        'pnr' => '',
        'journey_from_to' => '',
        'third_party_booking' => ''
    ];
    
    // Make API request to UddoktaPay
    $response = uddoktapay_make_request('/api/charge-init', $api_data, 'POST');
    
    if ($response && isset($response['payment_url'])) {
        // Update transaction with payment URL and gateway transaction ID
        $d = ORM::for_table('tbl_payment_gateway')->find_one($invoice_id);
        $d->pg_url_payment = $response['payment_url'];
        $d->gateway_trx_id = $response['invoice_id'] ?? $invoice_id;
        $d->pg_request = json_encode($api_data);
        $d->save();
        
        // Log the transaction creation
        _log('[' . $user['username'] . ']: UddoktaPay Payment Created - #' . $invoice_id, 'User', $user['id']);
        
        // Redirect to payment URL
        r2($response['payment_url'], 's', '');
    } else {
        // Handle error
        $error_msg = isset($response['message']) ? $response['message'] : 'Failed to create payment request';
        _log('[' . $user['username'] . ']: UddoktaPay Payment Failed - ' . $error_msg, 'User', $user['id']);
        r2(U . "order/package", 'e', 'Payment gateway error: ' . $error_msg);
    }
}

function uddoktapay_payment_notification()
{
    global $config;
    
    // Get webhook data
    $webhook_data = file_get_contents('php://input');
    $data = json_decode($webhook_data, true);
    
    // Log webhook received
    _log('UddoktaPay Webhook Received: ' . $webhook_data, 'System');
    
    if (!$data) {
        http_response_code(400);
        echo 'Invalid JSON data';
        return;
    }
    
    // Validate webhook signature if webhook key is configured
    if (!empty($config['uddoktapay_webhook_key'])) {
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
        $expected_signature = hash_hmac('sha256', $webhook_data, $config['uddoktapay_webhook_key']);
        
        if (!hash_equals($expected_signature, $signature)) {
            http_response_code(401);
            echo 'Invalid signature';
            return;
        }
    }
    
    // Get transaction data
    $invoice_id = $data['invoice_id'] ?? '';
    $status = $data['status'] ?? '';
    
    if (empty($invoice_id)) {
        http_response_code(400);
        echo 'Missing invoice ID';
        return;
    }
    
    // Find transaction in database
    $trx = ORM::for_table('tbl_payment_gateway')->find_one($invoice_id);
    if (!$trx) {
        http_response_code(404);
        echo 'Transaction not found';
        return;
    }
    
    // Verify payment with UddoktaPay API
    $verify_response = uddoktapay_verify_payment($invoice_id);
    
    if ($verify_response && $verify_response['status'] === 'COMPLETED') {
        // Payment is successful
        if ($trx['status'] != 2) { // Only process if not already paid
            $trx->pg_paid_response = json_encode($verify_response);
            $trx->payment_method = 'UddoktaPay';
            $trx->payment_channel = $verify_response['payment_method'] ?? 'UddoktaPay';
            $trx->paid_date = date('Y-m-d H:i:s');
            $trx->status = 2; // Paid
            $trx->save();
            
            // Get user and activate package
            $user = ORM::for_table('tbl_customers')->where('username', $trx['username'])->find_one();
            if ($user) {
                if (Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], 'UddoktaPay', $trx['gateway_trx_id'])) {
                    _log('[' . $user['username'] . ']: UddoktaPay Payment Success - #' . $invoice_id, 'User', $user['id']);
                    
                    // Send Telegram notification to admin
                    $plan = ORM::for_table('tbl_plans')->find_one($trx['plan_id']);
                    if ($plan) {
                        Message::sendTelegram("UddoktaPay Payment Success\n\n#" . $user['username'] . " #pay \n" . $plan['name_plan'] .
                            "\nRouter: " . $trx['routers'] .
                            "\nPrice: " . $trx['price'] .
                            "\nTrx ID: " . $trx['gateway_trx_id']);
                    }
                } else {
                    _log('[' . $user['username'] . ']: UddoktaPay Package Activation Failed - #' . $invoice_id, 'User', $user['id']);
                }
            }
        }
        
        http_response_code(200);
        echo 'Payment processed successfully';
    } else if ($verify_response && in_array($verify_response['status'], ['FAILED', 'CANCELLED'])) {
        // Payment failed or cancelled
        $trx->pg_paid_response = json_encode($verify_response);
        $trx->status = 3; // Failed
        $trx->save();
        
        _log('UddoktaPay Payment Failed/Cancelled - #' . $invoice_id, 'System');
        
        http_response_code(200);
        echo 'Payment failed/cancelled';
    } else {
        // Unknown status or verification failed
        _log('UddoktaPay Payment Verification Failed - #' . $invoice_id, 'System');
        
        http_response_code(400);
        echo 'Payment verification failed';
    }
}

function uddoktapay_verify_payment($invoice_id)
{
    global $config;
    
    $verify_data = [
        'store_id' => $config['uddoktapay_store_id'],
        'tran_id' => $invoice_id
    ];
    
    return uddoktapay_make_request('/api/verify-payment', $verify_data, 'POST');
}

function uddoktapay_make_request($endpoint, $data, $method = 'POST')
{
    global $config;
    
    $api_url = rtrim($config['uddoktapay_api_url'], '/') . $endpoint;
    
    $headers = [
        'Content-Type: application/json',
        'RT-UDDOKTAPAY-API-KEY: ' . $config['uddoktapay_api_key']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $http_code >= 400) {
        _log('UddoktaPay API Error: HTTP ' . $http_code . ' - ' . $response, 'System');
        return false;
    }
    
    return json_decode($response, true);
}