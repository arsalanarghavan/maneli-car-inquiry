<?php
/**
 * Dashboard Base Template (PHP Version with Dynamic Branding)
 * 
 * This is the base template for the dashboard. It loads from PHP instead of HTML
 * to support dynamic branding through Autopuzzle_Branding_Helper.
 * 
 * @package AutoPuzzle/Templates
 * @since 1.2.0
 */

// Get branding information
$brand_name = autopuzzle_brand_name('fa_IR'); // Persian name for dashboard
$logo_url = autopuzzle_logo('main');
$primary_color = autopuzzle_color('primary');

// Load the original HTML as a template
get_template_part('dashboard-base');
?>
