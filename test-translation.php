<?php
/**
 * Test translation loading
 * 
 * Upload this file to the plugin root and access it via:
 * yoursite.com/wp-content/plugins/maneli-car-inquiry/test-translation.php
 */

// Load WordPress
require_once '../../../wp-load.php';

echo "<h1>Translation Debug Info</h1>";
echo "<hr>";

echo "<h2>WordPress Settings</h2>";
echo "WordPress Locale: <strong>" . get_locale() . "</strong><br>";
echo "Site Language: <strong>" . get_bloginfo('language') . "</strong><br>";
echo "User Locale: <strong>" . get_user_locale() . "</strong><br>";
echo "<hr>";

echo "<h2>Plugin Info</h2>";
echo "Plugin Path: " . MANELI_INQUIRY_PLUGIN_PATH . "<br>";
echo "Languages Path: " . MANELI_INQUIRY_PLUGIN_PATH . 'languages/' . "<br>";
echo "<hr>";

echo "<h2>Translation Files</h2>";
$lang_dir = MANELI_INQUIRY_PLUGIN_PATH . 'languages/';
$files = scandir($lang_dir);
echo "<ul>";
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $size = filesize($lang_dir . $file);
        echo "<li><strong>$file</strong> (" . number_format($size) . " bytes)</li>";
    }
}
echo "</ul>";
echo "<hr>";

echo "<h2>Expected Translation File Names</h2>";
$locale = get_locale();
$domain = 'maneli-car-inquiry';
echo "Domain: <strong>$domain</strong><br>";
echo "Locale: <strong>$locale</strong><br>";
echo "Expected MO file: <strong>{$domain}-{$locale}.mo</strong><br>";
echo "Alternative MO file: <strong>{$locale}.mo</strong><br>";
echo "<hr>";

echo "<h2>File Existence Check</h2>";
$expected_mo = $lang_dir . "{$domain}-{$locale}.mo";
$alternative_mo = $lang_dir . "{$locale}.mo";
echo "Looking for: <strong>{$domain}-{$locale}.mo</strong> - ";
echo file_exists($expected_mo) ? "<span style='color:green'>✓ EXISTS</span>" : "<span style='color:red'>✗ NOT FOUND</span>";
echo "<br>";
echo "Looking for: <strong>{$locale}.mo</strong> - ";
echo file_exists($alternative_mo) ? "<span style='color:green'>✓ EXISTS</span>" : "<span style='color:red'>✗ NOT FOUND</span>";
echo "<hr>";

echo "<h2>Test Translation</h2>";
$test_string = 'Customer';
$translated = __($test_string, 'maneli-car-inquiry');
echo "Original: <strong>$test_string</strong><br>";
echo "Translated: <strong>$translated</strong><br>";
echo "Status: ";
if ($test_string === $translated) {
    echo "<span style='color:red'>✗ NOT TRANSLATED (Still English)</span>";
} else {
    echo "<span style='color:green'>✓ TRANSLATED to: $translated</span>";
}
echo "<hr>";

echo "<h2>Loaded Text Domains</h2>";
global $l10n;
if (isset($l10n['maneli-car-inquiry'])) {
    echo "<span style='color:green'>✓ maneli-car-inquiry domain is LOADED</span><br>";
    $mo_file = $l10n['maneli-car-inquiry'];
    echo "MO Object Type: " . get_class($mo_file) . "<br>";
} else {
    echo "<span style='color:red'>✗ maneli-car-inquiry domain is NOT loaded</span><br>";
}

echo "<hr>";
echo "<h2>All Loaded Domains</h2>";
echo "<ul>";
if (!empty($l10n)) {
    foreach (array_keys($l10n) as $domain_name) {
        echo "<li>$domain_name</li>";
    }
} else {
    echo "<li>No domains loaded</li>";
}
echo "</ul>";

echo "<hr>";
echo "<h2>Recommendations</h2>";
echo "<ol>";

if (!file_exists($expected_mo)) {
    echo "<li style='color:red'>Create file: <code>{$domain}-{$locale}.mo</code> in the languages folder</li>";
}

if ($locale !== 'fa_IR') {
    echo "<li style='color:orange'>WordPress locale is '<strong>$locale</strong>' but translation files are for 'fa_IR'. ";
    echo "Either change WordPress language to Persian or rename your translation files.</li>";
}

echo "</ol>";

