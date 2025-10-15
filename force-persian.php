<?php
/**
 * Force Load Persian Translation for Testing
 * 
 * Add this to your wp-config.php to force Persian language:
 * define('WPLANG', 'fa_IR');
 * 
 * Or run this file directly from browser to test translation loading
 */

// Load WordPress
require_once '../../../wp-load.php';

echo "<h1>Force Persian Translation Test</h1>";
echo "<hr>";

// Force load the translation
$locale = 'fa_IR';
$domain = 'maneli-car-inquiry';
$mofile = MANELI_INQUIRY_PLUGIN_PATH . "languages/{$domain}-{$locale}.mo";

echo "<h2>Attempting to load translation...</h2>";
echo "File: <code>$mofile</code><br>";
echo "Exists: " . (file_exists($mofile) ? "<span style='color:green'>Yes</span>" : "<span style='color:red'>No</span>") . "<br>";
echo "<hr>";

if (file_exists($mofile)) {
    $loaded = load_textdomain($domain, $mofile);
    echo "Load Result: " . ($loaded ? "<span style='color:green'>SUCCESS</span>" : "<span style='color:red'>FAILED</span>") . "<br>";
    echo "<hr>";
    
    echo "<h2>Translation Tests</h2>";
    
    $tests = [
        'Customer' => 'مشتری',
        'Car' => 'خودرو',
        'Status' => 'وضعیت',
        'Mobile Number' => 'شماره موبایل',
        'Car Name' => 'نام خودرو',
    ];
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>English</th><th>Expected Persian</th><th>Actual Translation</th><th>Status</th></tr>";
    
    foreach ($tests as $english => $expected_persian) {
        $actual = __($english, $domain);
        $status = ($actual !== $english) ? "<span style='color:green'>✓ OK</span>" : "<span style='color:red'>✗ FAIL</span>";
        echo "<tr>";
        echo "<td>$english</td>";
        echo "<td>$expected_persian</td>";
        echo "<td><strong>$actual</strong></td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<h2 style='color:red'>Translation file not found!</h2>";
    echo "<p>Expected location: <code>$mofile</code></p>";
    echo "<h3>Available files in languages directory:</h3>";
    $files = scandir(MANELI_INQUIRY_PLUGIN_PATH . 'languages/');
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
}

