<?php
/**
 * Template for the 'Full Inquiry Details' meta box on the inquiry post type edit screen.
 *
 * This template renders all the saved meta data for an installment inquiry in a structured format.
 *
 * @package Maneli_Car_Inquiry/Templates/Admin
 * @author  Gemini
 * @version 1.0.1 (Refactored to use Maneli_Render_Helpers::get_meta_label for status fields)
 *
 * @var int $post_id The ID of the current inquiry post.
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_meta = get_post_meta($post_id);

/**
 * Helper function to render a table of key-value pairs from post meta.
 *
 * @param array $fields      An associative array of [meta_key => Label].
 * @param array $meta_source The source of the meta data (usually $post_meta).
 */
$render_fields_table = function($fields, $meta_source) {
    echo '<table class="form-table"><tbody>';
    // Group fields into pairs for a two-column layout
    foreach (array_chunk($fields, 2, true) as $pair) {
        echo '<tr>';
        foreach ($pair as $key => $label) {
            $value = $meta_source[$key][0] ?? '—';

            // Use the centralized helper for localization and status translation
            // This replaces the complex inline logic from the previous version.
            $display_value = Maneli_Render_Helpers::get_meta_label($key, $value);

            echo '<th scope="row" style="width: 15%;"><label>' . esc_html($label) . '</label></th>';
            echo '<td style="width: 35%;">' . esc_html($display_value) . '</td>';
        }
        // If there's only one item in the row, add empty cells to maintain table structure
        if (count($pair) < 2) {
            echo '<th></th><td></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
};

?>

<h4><?php esc_html_e('Applicant Information', 'maneli-car-inquiry'); ?></h4>
<?php
$buyer_fields = [
    'first_name' => esc_html__('First Name', 'maneli-car-inquiry'),
    'last_name' => esc_html__('Last Name', 'maneli-car-inquiry'),
    'father_name' => esc_html__('Father\'s Name', 'maneli-car-inquiry'),
    'national_code' => esc_html__('National Code', 'maneli-car-inquiry'),
    'occupation' => esc_html__('Occupation', 'maneli-car-inquiry'),
    'income_level' => esc_html__('Income Level', 'maneli-car-inquiry'),
    'mobile_number' => esc_html__('Mobile Number', 'maneli-car-inquiry'),
    'phone_number' => esc_html__('Phone Number', 'maneli-car-inquiry'),
    'residency_status' => esc_html__('Residency Status', 'maneli-car-inquiry'),
    'workplace_status' => esc_html__('Workplace Status', 'maneli-car-inquiry'),
    'address' => esc_html__('Address', 'maneli-car-inquiry'),
    'birth_date' => esc_html__('Date of Birth', 'maneli-car-inquiry'),
    'bank_name' => esc_html__('Bank Name', 'maneli-car-inquiry'),
    'account_number' => esc_html__('Account Number', 'maneli-car-inquiry'),
    'branch_code' => esc_html__('Branch Code', 'maneli-car-inquiry'),
    'branch_name' => esc_html__('Branch Name', 'maneli-car-inquiry'),
];
$render_fields_table($buyer_fields, $post_meta);
?>

<?php
$issuer_type = $post_meta['issuer_type'][0] ?? 'self';
if ($issuer_type === 'other') :
?>
    <h3 style="margin-top:20px; border-top:1px solid #ddd; padding-top:20px;"><?php esc_html_e('Cheque Issuer Information', 'maneli-car-inquiry'); ?></h3>
    <?php
    $issuer_fields = [
        'issuer_full_name' => esc_html__('Issuer Name', 'maneli-car-inquiry'),
        'issuer_national_code' => esc_html__('Issuer National Code', 'maneli-car-inquiry'),
        'issuer_bank_name' => esc_html__('Bank Name', 'maneli-car-inquiry'),
        'issuer_account_number' => esc_html__('Account Number', 'maneli-car-inquiry'),
        'issuer_branch_code' => esc_html__('Branch Code', 'maneli-car-inquiry'),
        'issuer_branch_name' => esc_html__('Branch Name', 'maneli-car-inquiry'),
        'issuer_residency_status' => esc_html__('Residency Status', 'maneli-car-inquiry'),
        'issuer_workplace_status' => esc_html__('Workplace Status', 'maneli-car-inquiry'),
        'issuer_father_name' => esc_html__('Father\'s Name', 'maneli-car-inquiry'),
        'issuer_occupation' => esc_html__('Occupation', 'maneli-car-inquiry'),
        'issuer_phone_number' => esc_html__('Phone Number', 'maneli-car-inquiry'),
        'issuer_address' => esc_html__('Address', 'maneli-car-inquiry'),
    ];
    $render_fields_table($issuer_fields, $post_meta);
    ?>
<?php endif; ?>

<h3 style="margin-top:20px; border-top:1px solid #ddd; padding-top:20px;"><?php esc_html_e('Car and Installment Details', 'maneli-car-inquiry'); ?></h3>
<?php
// Use Maneli_Render_Helpers for calculations and formatting
$loan_amount = (int)($post_meta['maneli_inquiry_total_price'][0] ?? 0) - (int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0);
$product_id = $post_meta['product_id'][0] ?? 0;
$product = $product_id ? wc_get_product($product_id) : null;
$car_model = $product ? $product->get_attribute('pa_model') : '—';

$car_fields = [
    'product_name' => esc_html__('Car Name', 'maneli-car-inquiry'),
    'car_model' => esc_html__('Car Model', 'maneli-car-inquiry'),
    'maneli_inquiry_total_price' => esc_html__('Total Price', 'maneli-car-inquiry'),
    'maneli_inquiry_down_payment' => esc_html__('Down Payment', 'maneli-car-inquiry'),
    'loan_amount' => esc_html__('Loan Amount', 'maneli-car-inquiry'),
    'maneli_inquiry_term_months' => esc_html__('Installment Term', 'maneli-car-inquiry'),
    'maneli_inquiry_installment' => esc_html__('Monthly Installment', 'maneli-car-inquiry'),
];

// We need to create a temporary meta source array with formatted values for this section
$car_meta_values = [
    'product_name' => [get_the_title($product_id)],
    'car_model' => [$car_model],
    'loan_amount' => [Maneli_Render_Helpers::format_money($loan_amount) . ' ' . esc_html__('Toman', 'maneli-car-inquiry')],
    'maneli_inquiry_total_price' => [Maneli_Render_Helpers::format_money((int)($post_meta['maneli_inquiry_total_price'][0] ?? 0)) . ' ' . esc_html__('Toman', 'maneli-car-inquiry')],
    'maneli_inquiry_down_payment' => [Maneli_Render_Helpers::format_money((int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0)) . ' ' . esc_html__('Toman', 'maneli-car-inquiry')],
    'maneli_inquiry_term_months' => [($post_meta['maneli_inquiry_term_months'][0] ?? '') . ' ' . esc_html__('Months', 'maneli-car-inquiry')],
    'maneli_inquiry_installment' => [Maneli_Render_Helpers::format_money((int)($post_meta['maneli_inquiry_installment'][0] ?? 0)) . ' ' . esc_html__('Toman', 'maneli-car-inquiry')],
];

$render_fields_table($car_fields, $car_meta_values);
?>