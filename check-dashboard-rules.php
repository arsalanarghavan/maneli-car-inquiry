<?php
/**
 * Dashboard Rewrite Rules Diagnostic
 * 
 * Run this in WordPress context to check rewrite rules
 * 
 * Usage:
 * 1. Copy this file to WordPress root
 * 2. Access via browser: http://yoursite.com/check-dashboard-rules.php
 * 3. Check output for rewrite rule status
 */

// Check if WordPress is loaded
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once('../wp-load.php');
}

echo "<!DOCTYPE html><html><head><style>";
echo "body { font-family: Arial; margin: 20px; background: #f5f5f5; }";
echo ".ok { color: green; padding: 10px; background: #e8f5e9; margin: 5px 0; border-radius: 3px; }";
echo ".error { color: red; padding: 10px; background: #ffebee; margin: 5px 0; border-radius: 3px; }";
echo ".warning { color: orange; padding: 10px; background: #fff3e0; margin: 5px 0; border-radius: 3px; }";
echo ".info { color: #1976d2; padding: 10px; background: #e3f2fd; margin: 5px 0; border-radius: 3px; }";
echo "h1 { border-bottom: 2px solid #1976d2; padding-bottom: 10px; }";
echo "h2 { margin-top: 20px; color: #333; }";
echo ".code { background: #f0f0f0; padding: 10px; border-left: 3px solid #1976d2; font-family: monospace; overflow-x: auto; }";
echo "</style></head><body>";

echo "<h1>🔍 Dashboard Rewrite Rules Diagnostic</h1>";

// Check 1: Rewrite rules exist
echo "<h2>1. Checking Rewrite Rules...</h2>";
$rewrite_rules = get_option('rewrite_rules');

if (!is_array($rewrite_rules)) {
    echo "<div class='error'>❌ Rewrite rules not initialized</div>";
    echo "<div class='warning'>⚠️ This usually means you need to flush rewrite rules</div>";
} else {
    $dashboard_rules = array();
    $found_any = false;
    
    foreach ($rewrite_rules as $pattern => $redirect) {
        if (strpos($pattern, 'dashboard') !== false || 
            strpos($pattern, 'login') !== false ||
            strpos($redirect, 'autopuzzle_dashboard') !== false) {
            $found_any = true;
            $dashboard_rules[$pattern] = $redirect;
        }
    }
    
    if ($found_any) {
        echo "<div class='ok'>✅ Dashboard rewrite rules found!</div>";
        echo "<h3>Registered Rules:</h3>";
        echo "<div class='code'>";
        foreach ($dashboard_rules as $pattern => $redirect) {
            echo htmlspecialchars($pattern) . " → " . htmlspecialchars($redirect) . "<br>";
        }
        echo "</div>";
    } else {
        echo "<div class='error'>❌ No dashboard rewrite rules found</div>";
        echo "<div class='warning'>⚠️ You need to flush rewrite rules! Go to Settings → Permalinks and click Save Changes</div>";
    }
}

// Check 2: Query vars registered
echo "<h2>2. Checking Query Variables...</h2>";
global $wp_query;
$query_vars = $wp_query->query_vars ?? array();

if (empty($query_vars)) {
    echo "<div class='warning'>⚠️ No query vars registered yet (normal, only set during query)</div>";
} else {
    if (isset($query_vars['autopuzzle_dashboard'])) {
        echo "<div class='ok'>✅ autopuzzle_dashboard query var found</div>";
    } else {
        echo "<div class='error'>❌ autopuzzle_dashboard query var not registered</div>";
    }
}

// Check 3: Plugin file status
echo "<h2>3. Checking Plugin Files...</h2>";

$files_to_check = array(
    'autopuzzle.php' => 'Main plugin file',
    'includes/class-dashboard-handler.php' => 'Dashboard Handler',
    'templates/dashboard.php' => 'Dashboard Template',
);

$plugin_path = dirname(dirname(__FILE__)) . '/maneli-car-inquiry';
if (is_dir($plugin_path)) {
    echo "<div class='ok'>✅ Plugin directory found at: " . htmlspecialchars($plugin_path) . "</div>";
    
    foreach ($files_to_check as $file => $desc) {
        $full_path = $plugin_path . '/' . $file;
        if (file_exists($full_path)) {
            echo "<div class='ok'>✅ $desc: " . htmlspecialchars($file) . "</div>";
        } else {
            echo "<div class='error'>❌ $desc: " . htmlspecialchars($file) . " NOT FOUND</div>";
        }
    }
} else {
    echo "<div class='error'>❌ Plugin directory not found</div>";
}

// Check 4: Autopuzzle class
echo "<h2>4. Checking Autopuzzle Classes...</h2>";

if (class_exists('Autopuzzle_Dashboard_Handler')) {
    echo "<div class='ok'>✅ Autopuzzle_Dashboard_Handler class exists</div>";
} else {
    echo "<div class='error'>❌ Autopuzzle_Dashboard_Handler class not found</div>";
}

if (class_exists('Autopuzzle_Car_Inquiry_Plugin')) {
    echo "<div class='ok'>✅ Autopuzzle_Car_Inquiry_Plugin class exists</div>";
} else {
    echo "<div class='error'>❌ Autopuzzle_Car_Inquiry_Plugin class not found</div>";
}

// Check 5: Permalink structure
echo "<h2>5. Checking WordPress Permalink Structure...</h2>";

$permalink_structure = get_option('permalink_structure');
if (empty($permalink_structure)) {
    echo "<div class='error'>❌ Using Plain permalink structure</div>";
    echo "<div class='warning'>⚠️ Dashboard routes require URL rewriting!</div>";
    echo "<div class='info'>ℹ️ Go to Settings → Permalinks and select a permalink structure (e.g., Post name)</div>";
} else {
    echo "<div class='ok'>✅ Using permalink structure: " . htmlspecialchars($permalink_structure) . "</div>";
}

// Check 6: Test URL
echo "<h2>6. Testing Dashboard URL...</h2>";

$dashboard_url = home_url('/dashboard');
$login_url = home_url('/login');

echo "<div class='info'>";
echo "Dashboard URL: <a href='" . htmlspecialchars($dashboard_url) . "' target='_blank'>" . htmlspecialchars($dashboard_url) . "</a><br>";
echo "Login URL: <a href='" . htmlspecialchars($login_url) . "' target='_blank'>" . htmlspecialchars($login_url) . "</a>";
echo "</div>";

// Summary
echo "<h2>Summary & Recommendations</h2>";

$issues = array();

if (empty($permalink_structure)) {
    $issues[] = "Plain permalink structure is active - change to any other structure";
}

if (!isset($dashboard_rules) || empty($dashboard_rules)) {
    $issues[] = "Dashboard rewrite rules are missing - flush rewrite rules";
}

if (empty($issues)) {
    echo "<div class='ok'>✅ All checks passed! Dashboard should be working.</div>";
    echo "<div class='info'>If dashboard still doesn't load, check:</div>";
    echo "<ul>";
    echo "<li>Are you logged in? (for /dashboard) or at login page (for /login)</li>";
    echo "<li>Check browser developer tools (F12) for errors</li>";
    echo "<li>Check WordPress debug.log for errors</li>";
    echo "</ul>";
} else {
    echo "<div class='error'>❌ Issues found:</div>";
    echo "<ol>";
    foreach ($issues as $issue) {
        echo "<li>" . htmlspecialchars($issue) . "</li>";
    }
    echo "</ol>";
    
    echo "<div class='warning'>";
    echo "<strong>QUICK FIX:</strong><br>";
    echo "1. Go to <strong>Settings → Permalinks</strong><br>";
    echo "2. Select a permalink structure (e.g., \"Post name\")<br>";
    echo "3. Click <strong>Save Changes</strong><br>";
    echo "4. Refresh this page to verify<br>";
    echo "5. Clear your browser cache<br>";
    echo "6. Try accessing /dashboard or /login";
    echo "</div>";
}

echo "</body></html>";
?>
