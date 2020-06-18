<?php

class ControllerExtensionPaymentCointopayFiatIntlCC extends Controller
{
    private $error = array();

    public function index()
    {

        $this->load->language('extension/payment/cointopay_fiat_intl_cc');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->model_setting_setting->editSetting('payment_cointopay_fiat_intl_cc', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            if (isset($this->request->post['language_reload'])) {
                $this->response->redirect($this->url->link('payment/cointopay_fiat_intl_cc', 'user_token=' . $this->session->data['user_token'], true));
            } else {
                $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
            }
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_payment'] = $this->language->get('text_payment');
        $data['text_success'] = $this->language->get('text_success');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_security_code'] = $this->language->get('entry_security_code');
        $data['entry_redirect_url'] = $this->language->get('entry_redirect_url');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
        $data['entry_display_name'] = $this->language->get('entry_display_name');
        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_callback_success_order_status'] = $this->language->get('entry_callback_success_order_status');
        $data['entry_callback_failed_order_status'] = $this->language->get('entry_callback_failed_order_status');
        $data['error_permission'] = $this->language->get('error_permission');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['help_api_key_hint'] = $this->language->get('help_api_key_hint');
        $data['help_fiat_hint'] = $this->language->get('help_fiat_hint');
        $data['help_redirect_url_hint'] = $this->language->get('help_redirect_url_hint');
        $data['help_display_name_hint'] = $this->language->get('help_display_name_hint');
        $data['help_merchant_id_hint'] = $this->language->get('help_merchant_id_hint');

        $data['tab_general'] = 'General';

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['api_key'])) {
            $data['error_api_key'] = $this->error['api_key'];
        } else {
            $data['error_api_key'] = '';
        }

        if (isset($this->error['display_name'])) {
            $data['error_display_name'] = $this->error['display_name'];
        } else {
            $data['error_display_name'] = '';
        }

        if (isset($this->error['merchant_id'])) {
            $data['error_merchant_id'] = $this->error['merchant_id'];
        } else {
            $data['error_merchant_id'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/cointopay_fiat_intl_cc', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => ' :: '
        );

        $data['action'] = $this->url->link('extension/payment/cointopay_fiat_intl_cc', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $data['cancel'] = $this->url->link('extension/payment', 'user_token=' . $this->session->data['user_token'], 'SSL');

        if (isset($this->request->post['payment_cointopay_fiat_intl_cc_display_name'])) {
            $data['cointopay_fiat_intl_cc_display_name'] = $this->request->post['payment_cointopay_fiat_intl_cc_display_name'];
        } else {
            $data['cointopay_fiat_intl_cc_display_name'] = $this->config->get('payment_cointopay_fiat_intl_cc_display_name');
        }

        if (isset($this->request->post['payment_cointopay_fiat_intl_cc_security_code'])) {
            $data['cointopay_fiat_intl_cc_security_code'] = $this->request->post['payment_cointopay_fiat_intl_cc_security_code'];
        } else {
            $data['cointopay_fiat_intl_cc_security_code'] = $this->config->get('payment_cointopay_fiat_intl_cc_security_code');
        }

        if (isset($this->request->post['payment_cointopay_fiat_intl_cc_status'])) {
            $data['cointopay_fiat_intl_cc_status'] = $this->request->post['payment_cointopay_fiat_intl_cc_status'];
        } else {
            $data['cointopay_fiat_intl_cc_status'] = $this->config->get('payment_cointopay_fiat_intl_cc_status');
        }

        if (isset($this->request->post['payment_cointopay_fiat_intl_cc_order_status_id'])) {
            $data['cointopay_fiat_intl_cc_order_status_id'] = $this->request->post['payment_cointopay_fiat_intl_cc_order_status_id'];
        } else {
            $data['cointopay_fiat_intl_cc_order_status_id'] = $this->config->get('payment_cointopay_fiat_intl_cc_order_status_id');
        }

        if (isset($this->request->post['payment_cointopay_fiat_intl_cc_callback_success_order_status_id'])) {
            $data['cointopay_fiat_intl_cc_callback_success_order_status_id'] = $this->request->post['payment_cointopay_fiat_intl_cc_callback_success_order_status_id'];
        } else {
            $data['cointopay_fiat_intl_cc_callback_success_order_status_id'] = $this->config->get('payment_cointopay_fiat_intl_cc_callback_success_order_status_id');
        }

        if (isset($this->request->post['payment_cointopay_fiat_intl_cc_callback_failed_order_status_id'])) {
            $data['cointopay_fiat_intl_cc_callback_failed_order_status_id'] = $this->request->post['payment_cointopay_fiat_intl_cc_callback_failed_order_status_id'];
        } else {
            $data['cointopay_fiat_intl_cc_callback_failed_order_status_id'] = $this->config->get('payment_cointopay_fiat_intl_cc_callback_failed_order_status_id');
        }

        if (isset($this->request->post['payment_cointopay_fiat_intl_cc_merchant_id'])) {
            $data['cointopay_fiat_intl_cc_merchant_id'] = $this->request->post['payment_cointopay_fiat_intl_cc_merchant_id'];
        } else {
            $data['cointopay_fiat_intl_cc_merchant_id'] = $this->config->get('payment_cointopay_fiat_intl_cc_merchant_id');
        }

        if (isset($this->request->post['payment_cointopay_fiat_intl_cc_sort_order'])) {
            $data['cointopay_fiat_intl_cc_sort_order'] = $this->request->post['payment_cointopay_fiat_intl_cc_sort_order'];
        } else {
            $data['cointopay_fiat_intl_cc_sort_order'] = $this->config->get('payment_cointopay_fiat_intl_cc_sort_order');
        }


        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (null !== $this->config->get('payment_cointopay_fiat_intl_cc_merchant_id')) {
            $currencyOutput = $this->getInputCurrencyList($this->config->get('payment_cointopay_fiat_intl_cc_merchant_id'));
            if (in_array($this->config->get('config_currency'), $currencyOutput['currency'])) {
                $data['error_invalid_currency'] = '';
            } else {

                $data['error_invalid_currency'] = 'Your Store currency ' . $this->config->get('config_currency') . ' not supported. Please contact <a href="mailto:support@cointopay.com">support@cointopay.com</a> to resolve this issue.';
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/cointopay_fiat_intl_cc', $data));
    }

    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/cointopay_fiat_intl_cc')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_cointopay_fiat_intl_cc_security_code']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }

        if (!$this->request->post['payment_cointopay_fiat_intl_cc_display_name']) {
            $this->error['display_name'] = $this->language->get('error_display_name');
        }

        if (!$this->request->post['payment_cointopay_fiat_intl_cc_merchant_id']) {
            $this->error['merchant_id'] = $this->language->get('error_merchant_id');
        }

        return !$this->error;
    }

    function getInputCurrencyList($merchantId)
    {
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
}
