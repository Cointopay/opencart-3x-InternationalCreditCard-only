<?php

class ControllerExtensionPaymentCointopayFiatIntlCC extends Controller
{
    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');

        $this->load->model('checkout/order');

        $order_info = "Session order_id is empty cannot proceed with your request";

        if (isset($this->session->data['order_id'])) {
            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        } else {
            echo $order_info;
            return;
        }
        try {

            if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
                if (empty($this->config->get('payment_cointopay_fiat_intl_cc_merchant_id')) || empty($this->config->get('payment_cointopay_fiat_intl_cc_security_code'))) {
                    echo 'CredentialsMissing';
                    exit();
                }
                $formData = $this->request->post;
                $currencyOutput = $this->getInputCurrencyList();
                if (null !== $this->config->get('payment_cointopay_fiat_intl_cc_merchant_id')) {
                    if (!in_array($this->config->get('config_currency'), $currencyOutput['currency'])) {
                        echo 'Your Store currency ' . $this->config->get('config_currency') . ' not supported. Please contact <a href="mailto:support@cointopay.com">support@cointopay.com</a> to resolve this issue.';
                        exit();
                    }
                }

                $url = trim($this->c2pCreateInvoice($this->request->post));
                if (is_string(json_decode($url))) {
                    echo json_decode($url);
                    exit();
                }
                $url_components = parse_url(json_encode($url));
                if (isset($url_components['query'])) {
                    parse_str($url_components['query'], $params);
                    if ($params['MerchantID'] == 'null') {
                        echo "Your MerchantID did not result in a correct transaction order, please update your plugin MerchantID";
                        exit();
                    }
                }
                $php_arr = json_decode($url);

                if (!isset($php_arr->TransactionID) || !isset($php_arr->QRCodeURL)) {
                    echo "Transaction not completed, please check your cointopay settings.";
                    exit();
                }

                $data1 = array();

                $this->load->language('extension/payment/cointopay_fiat_intl_cc_invoice');

                if ($php_arr->error == '' || empty($php_arr->error)) {
                    $this->model_checkout_order->addOrderHistory($php_arr->CustomerReferenceNr, $this->config->get('payment_cointopay_fiat_intl_cc_order_status_id'));
                    // Redirect to stripe
                    $amount = number_format($php_arr->Amount, 2);
                    $url = "https://surplus17.com:9443/ctp/?call=stripe&transactionID={$php_arr->TransactionID}&amount={$amount}&currency=EUR&customerReferenceNr={$this->session->data['order_id']}&confirmCode={$php_arr->Security}";
                    header("Location:{$url}");
                } else {
                    $data1['error'] = $php_arr->error;
                }
                if (isset($this->session->data['order_id'])) {
                    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$this->session->data['order_id'] . "' AND order_status_id > 0");

                    if ($query->num_rows) {
                        $this->cart->clear();
                        unset($this->session->data['shipping_method']);
                        unset($this->session->data['shipping_methods']);
                        unset($this->session->data['payment_method']);
                        unset($this->session->data['payment_methods']);
                        unset($this->session->data['guest']);
                        unset($this->session->data['comment']);
                        unset($this->session->data['order_id']);
                        unset($this->session->data['coupon']);
                        unset($this->session->data['reward']);
                        unset($this->session->data['voucher']);
                        unset($this->session->data['vouchers']);
                    }
                }
                $data1['footer'] = $this->load->controller('common/footer');
                $data1['header'] = $this->load->controller('common/header');
                if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/cointopay_fiat_intl_cc_invoice')) {
                    $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/cointopay_fiat_intl_cc_invoice', $data1));
                } else {
                    $this->response->setOutput($this->load->view('extension/payment/cointopay_fiat_intl_cc_invoice', $data1));
                }
            } else {
                $this->load->language('extension/payment/cointopay_fiat_intl_cc');

                $data['action'] = $this->url->link('extension/payment/cointopay_fiat_intl_cc', '', true);

                $data['price'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
                $data['key'] = $this->config->get('payment_cointopay_fiat_cc_api_key');
                $data['AltCoinID'] = 625;
                $data['OrderID'] = $this->session->data['order_id'];
                $data['currency'] = $order_info['currency_code'];

                $data['text_crypto_coin_label'] = $this->language->get('text_crypto_coin_label');

                if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/cointopay_fiat_intl_cc')) {
                    return $this->load->view($this->config->get('config_template') . '/template/extension/payment/cointopay_fiat_intl_cc', $data);
                } else {
                    return $this->load->view('extension/payment/cointopay_fiat_intl_cc', $data);
                }
            }

        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            return;
        }
    }

    function getInputCurrencyList()
    {
        $merchantId = $this->config->get('payment_cointopay_fiat_intl_cc_merchant_id');
        $url = 'https://cointopay.com/v2REAPI?MerchantID=' . $merchantId . '&Call=inputCurrencyList&output=json&APIKey=_';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $output = curl_exec($ch);

        curl_close($ch);

        $php_arr = json_decode($output);
        $new_php_arr = array();

        if (!empty($php_arr)) {
            foreach ($php_arr as $c) {
                if (array_key_exists('ShortName', $c)) {
                    $new_php_arr['currency'][] = $c->ShortName;
                }

            }
        }

        return $new_php_arr;
    }

    function c2pCreateInvoice($data)
    {
        $merchant_id = $this->config->get('payment_cointopay_fiat_intl_cc_merchant_id');
        $payment_cointopay_security_code = $this->config->get('payment_cointopay_fiat_intl_cc_security_code');
        return $this->c2pCurl('SecurityCode=' . $payment_cointopay_security_code . '&MerchantID=' . $merchant_id . '&Amount=' . $data['price'] . '&AltCoinID=625&inputCurrency=' . $data['currency'] . '&output=json&CustomerReferenceNr=' . $data['OrderID'] . '&returnurl=' . $this->url->link('extension/payment/cointopay_fiat_intl_cc/callback') . '&transactionconfirmurl=' . $this->url->link('extension/payment/cointopay_fiat_intl_cc/callback') . '&transactionfailurl=' . $this->url->link('extension/payment/cointopay_fiat_intl_cc/callback'), $data['key']);
    }

    public function c2pCurl($data, $apiKey, $post = false)
    {
        $params = array(
            "authentication:1",
            'cache-control: no-cache',
        );

        $ch = curl_init();
        curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://app.cointopay.com/MerchantAPI?Checkout=true',
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => $params,
                CURLOPT_USERAGENT => 1,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC
            )
        );
        $output = curl_exec($ch);
        $php_arr = json_decode($output);

        if ($output == false) {
            $response = curl_error($ch);
        } else {
            $response = $output;
        }
        curl_close($ch);
        return $response;
    }

    public function callback()
    {
        $data = array();
        $this->load->language('extension/payment/cointopay_fiat_intl_cc_invoice');
        $this->load->model('checkout/order');
        if (isset($_REQUEST['status'])) {
            $is_live = isset($_REQUEST['is_live']) ? $_REQUEST['is_live'] : true;
            $not_enough = isset($_REQUEST['not_enough']) ? intval($_REQUEST['not_enough']) : 1;
            $data = [
                'mid' => $this->config->get('payment_cointopay_fiat_intl_cc_merchant_id'),
                'TransactionID' => $_GET['transaction_id'],
                'ConfirmCode' => $_GET['confirm_code']
            ];
            $transactionData = $this->validateOrder($data);
            $order = $this->model_checkout_order->getOrder($_GET['customer_reference_nr']);
            if (200 !== $transactionData['status_code']) {
                echo $transactionData['message'];
                exit;
            } else if (!$order || $order['total'] != $transactionData['data']['OriginalAmount']) {
                echo "Fraud detected";
                exit;
            } else {
                if ($transactionData['data']['Security'] != $_GET['confirm_code']) {
                    echo "Data mismatch! ConfirmCode doesn't match";
                    exit;
                } elseif ($transactionData['data']['CustomerReferenceNr'] != $_GET['customer_reference_nr']) {
                    echo "Data mismatch! CustomerReferenceNr doesn't match";
                    exit;
                } elseif ($transactionData['data']['TransactionID'] != $_GET['transaction_id']) {
                    echo "Data mismatch! TransactionID doesn't match";
                    exit;
                } elseif ($transactionData['data']['Status'] != $_GET['status'] && $is_live != 'false') {

                    echo "Data mismatch! status doesn't match. Your order status is " . $transactionData['data']['Status'];
                    exit;

                } else {
                    $status = $_REQUEST['status'];
                    if ($is_live == 'false') {
                        $stripe_transaction_code = (!empty(filter_var($_REQUEST['stripe_transaction_id'], FILTER_SANITIZE_STRING))) ? filter_var($_REQUEST['stripe_transaction_id'], FILTER_SANITIZE_STRING) : '';
                        $url = "https://surplus17.com:9443/ctp/?call=verifyTransaction&stripeTransactionCode=" . $stripe_transaction_code;
                        $ctp_response = $this->validateWithCTP($url);
                        if ($ctp_response['statusCode'] == 200 && $ctp_response['data'] == 'fail') {
                            $status = "failed";
                        }
                    }

                    if ($status == 'paid') {

                        $this->model_checkout_order->addOrderHistory($_REQUEST['customer_reference_nr'], $this->config->get('payment_cointopay_fiat_intl_cc_callback_success_order_status_id', 'Successfully Paid'));
                        $data['text_success'] = $this->language->get('text_success');
                        $data['footer'] = $this->load->controller('common/footer');
                        $data['header'] = $this->load->controller('common/header');

                        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/cointopay_fiat_intl_cc_success')) {
                            $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/cointopay_fiat_intl_cc_success', $data));
                        } else {
                            $this->response->setOutput($this->load->view('extension/payment/cointopay_fiat_intl_cc_success', $data));
                        }
                    } elseif ($status == 'failed') {
                        $this->model_checkout_order->addOrderHistory($_REQUEST['customer_reference_nr'], $this->config->get('payment_cointopay_fiat_intl_cc_callback_failed_order_status_id', 'Transaction payment failed'));

                        $data['text_failed'] = $this->language->get('text_failed');
                        $data['footer'] = $this->load->controller('common/footer');
                        $data['header'] = $this->load->controller('common/header');

                        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/cointopay_fiat_intl_cc_failed')) {
                            $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/cointopay_fiat_intl_cc_failed', $data));
                        } else {
                            $this->response->setOutput($this->load->view('extension/payment/cointopay_fiat_intl_cc_failed', $data));
                        }
                    } elseif ($status == 'expired') {
                        $this->model_checkout_order->addOrderHistory($_REQUEST['customer_reference_nr'], $this->config->get('payment_cointopay_fiat_intl_cc_callback_failed_order_status_id', 'Transaction payment failed'));

                        $data['text_failed'] = $this->language->get('text_expired');
                        $data['footer'] = $this->load->controller('common/footer');
                        $data['header'] = $this->load->controller('common/header');

                        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/cointopay_fiat_intl_cc_failed')) {
                            $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/cointopay_fiat_intl_cc_failed', $data));
                        } else {
                            $this->response->setOutput($this->load->view('extension/payment/cointopay_fiat_intl_cc_failed', $data));
                        }
                    }

                }
            }

        }
    }

    function validateOrder($data)
    {

        $params = array(
            "authentication:1",
            'cache-control: no-cache',
        );
        $ch = curl_init();
        curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://app.cointopay.com/v2REAPI?',
                CURLOPT_POSTFIELDS => 'MerchantID=' . $data['mid'] . '&Call=Transactiondetail&APIKey=a&output=json&ConfirmCode=' . $data['ConfirmCode'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => $params,
                CURLOPT_USERAGENT => 1,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC
            )
        );
        $response = curl_exec($ch);
        return json_decode($response, true);
    }

    function validateWithCTP($url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false
            )
        );
        $response = curl_exec($ch);
        return json_decode($response, true);
    }

    public function getCoinsPaymentUrl()
    {
        $data = array();
        $this->load->language('extension/payment/cointopay_fiat_intl_cc_invoice');
        if (isset($_REQUEST['TransactionID'])) {
            $url = 'https://app.cointopay.com/CloneMasterTransaction?MerchantID=' . $this->config->get("payment_cointopay_fiat_intl_cc_merchant_id") . '&TransactionID=' . $_REQUEST["TransactionID"] . '&output=json';
            $ch = curl_init($url);


            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $output = curl_exec($ch);
            curl_close($ch);
            $decoded = json_decode($output);
            echo $output;
        }
    }
}
