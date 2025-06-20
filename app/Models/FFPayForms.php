<?php
namespace FluentformsPaytails\Models;

use FluentForm\Framework\Database\Orm\Model;
class FFPayForms
{
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
            $totalsRow = wpFluent()->table('fluentform_submissions')
                ->select([
                    wpFluent()->raw('COUNT(DISTINCT form_id) as total_forms'),
                    wpFluent()->raw('COUNT(*) as total_payments'),
                    wpFluent()->raw('SUM(payment_total) as total_amount')
                ])
                ->where('payment_status', 'paid')
                ->first();
            $totals = is_array($totalsRow) ? $totalsRow : (array) $totalsRow;

            // --- Monthly cumulative payments (for line chart) ---
            $monthlyRows = wpFluent()->table('fluentform_submissions')
                ->select([
                    wpFluent()->raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                    wpFluent()->raw('SUM(payment_total) as total')
                ])
                ->where('payment_status', 'paid')
                ->groupBy(wpFluent()->raw("DATE_FORMAT(created_at, '%Y-%m')"))
                ->orderBy('month', 'ASC')
                ->get();
            $monthly_payments = [];
            $cumulative = 0;
            foreach ($monthlyRows as $row) {
                $row = (array) $row;
                $cumulative += $row['total'];
                $monthly_payments[] = [
                    'month' => $row['month'],
                    'cumulative_total' => number_format($cumulative / 100, 2, '.', '')
                ];
            }

            // --- Top 5 forms by total payments (for bar chart) ---
            $topFormsRows = wpFluent()->table('fluentform_submissions')
                ->select([
                    'form_id',
                    wpFluent()->raw('SUM(payment_total) as total_paid')
                ])
                ->where('payment_status', 'paid')
                ->groupBy('form_id')
                ->orderBy('total_paid', 'DESC')
                ->limit(5)
                ->get();
            $formTitles = [];
            foreach ($forms as $f) {
                $f = (array) $f;
                $formTitles[$f['id']] = $f['title'];
            }
            $top_forms = array_map(function ($row) use ($formTitles) {
                $row = (array) $row;
                return [
                    'title' => $formTitles[$row['form_id']] ?? ('Form #' . $row['form_id']),
                    'total_paid' => $row['total_paid'] ? number_format($row['total_paid'] / 100, 2, '.', '') : '0.00'
                ];
            }, $topFormsRows);

            $processedForms = array_map(function ($form) {
                $form = (array) $form;
                $statsRow = wpFluent()->table('fluentform_submissions')
                    ->select([
                        wpFluent()->raw('COUNT(*) as total_payments'),
                        wpFluent()->raw('SUM(payment_total) as payment_total')
                    ])
                    ->where('form_id', $form['id'])
                    ->where('payment_status', 'paid')
                    ->first();
                $stats = is_array($statsRow) ? $statsRow : (array) $statsRow;

                $creator = get_user_by('ID', $form['created_by']);

                return [
                    'id' => $form['id'],
                    'title' => $form['title'],
                    'status' => ucfirst($form['status']),
                    'creator' => $creator ? $creator->display_name : 'Unknown',
                    'total_paid' => $stats['payment_total'] ? number_format($stats['payment_total'] / 100, 2) : '0.00',
                    'edit_url' => admin_url("admin.php?page=fluent_forms&route=editor&form_id={$form['id']}"),
                    'entries_url' => admin_url("admin.php?page=fluent_forms&route=entries&form_id={$form['id']}")
                ];
            }, $forms);

            return [
                'forms' => $processedForms,
                'totals' => [
                    'total_forms' => (int) $totals['total_forms'],
                    'total_payments' => (int) $totals['total_payments'],
                    'total_amount' => $totals['total_amount'] ? number_format($totals['total_amount'] / 100, 2) : '0.00'
                ],
                'monthly_payments' => $monthly_payments,
                'top_forms' => $top_forms
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
