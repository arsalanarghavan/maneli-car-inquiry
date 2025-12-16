<?php
/**
 * Template for displaying the success message after a cash inquiry submission.
 *
 * This template is included at the top of the loan calculator shortcode output when the
 * `?cash_inquiry_sent=true` query parameter is present in the URL.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Public
 * @author  Gemini
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="status-box status-approved" style="margin-bottom: 20px; text-align: center;">
    <p><?php esc_html_e('Your request has been successfully submitted. We will contact you soon.', 'autopuzzle'); ?></p>
</div>