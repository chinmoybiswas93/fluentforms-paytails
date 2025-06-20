<?php
namespace FluentformsPaytails\Http\Controllers;

use FluentformsPaytails\Models\FFPayForms;
class FFPayFormsController
{
    public function __construct()
    {
        add_action('wp_ajax_ffpay_get_payment_forms', [$this, 'getPaymentForms']);
    }
    public function getPaymentForms()
    {
        check_ajax_referer('ffpay_creator_nonce', 'nonce');

        try {
            $model = new FFPayForms();
            $forms = $model->getFormsWithPayments();
            wp_send_json_success($forms);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => 'Failed to retrieve forms: ' . $e->getMessage()
            ], 500);
        }
    }
}
