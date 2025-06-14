<?php
namespace FluentformsPaytails\Models;

use FluentForm\Framework\Database\Orm\Model;
class FFPayForms{
    public function getFormsWithPayments()
    {
        if (!defined('FLUENTFORM')) {
            return [
                'forms' => [],
                'totals' => [
                    'total_forms' => 0,
                    'total_payments' => 0,
                    'total_amount' => '0.00'
                ]
            ];
        }
        try {
            $forms = wpFluent()->table('fluentform_forms')
                ->where('has_payment', 1)
                ->orderBy('created_at', 'DESC')
                ->get();

            // Get totals for all payment forms
            $totals = wpFluent()->table('fluentform_submissions')
                ->select([
                    wpFluent()->raw('COUNT(DISTINCT form_id) as total_forms'),
                    wpFluent()->raw('COUNT(*) as total_payments'),
                    wpFluent()->raw('SUM(payment_total) as total_amount')
                ])
                ->where('payment_status', 'paid')
                ->first();

            $processedForms = array_map(function($form) {
                $stats = wpFluent()->table('fluentform_submissions')
                    ->select([
                        wpFluent()->raw('COUNT(*) as total_payments'),
                        wpFluent()->raw('SUM(payment_total) as payment_total')
                    ])
                    ->where('form_id', $form->id)
                    ->where('payment_status', 'paid')
                    ->first();

                $creator = get_user_by('ID', $form->created_by);

                return [
                    'id' => $form->id,
                    'title' => $form->title,
                    'status' => ucfirst($form->status),
                    'creator' => $creator ? $creator->display_name : 'Unknown',
                    'total_paid' => $stats->payment_total ? number_format($stats->payment_total / 100, 2) : '0.00',
                    'edit_url' => admin_url("admin.php?page=fluent_forms&route=editor&form_id={$form->id}"),
                    'entries_url' => admin_url("admin.php?page=fluent_forms&route=entries&form_id={$form->id}")
                ];
            }, $forms);

            return [
                'forms' => $processedForms,
                'totals' => [
                    'total_forms' => (int)$totals->total_forms,
                    'total_payments' => (int)$totals->total_payments,
                    'total_amount' => $totals->total_amount ? number_format($totals->total_amount / 100, 2) : '0.00'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'forms' => [],
                'totals' => [
                    'total_forms' => 0,
                    'total_payments' => 0,
                    'total_amount' => '0.00'
                ]
            ];
        }
    }
}
