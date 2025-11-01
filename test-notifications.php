<?php
/**
 * Temporary test script to debug notification system
 * Place this file in the plugin root and access via: /wp-content/plugins/maneli-car-inquiry/test-notifications.php
 * DELETE AFTER TESTING!
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;
$table = $wpdb->prefix . 'maneli_notifications';

echo '<h2>Notification System Test</h2>';

// 1. Check if table exists
echo '<h3>1. Table Check</h3>';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
if ($table_exists) {
    echo '<p style="color: green;">✓ Table exists: ' . $table . '</p>';
    
    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE {$table}");
    echo '<h4>Table Structure:</h4><pre>';
    print_r($columns);
    echo '</pre>';
} else {
    echo '<p style="color: red;">✗ Table does NOT exist: ' . $table . '</p>';
    echo '<p>You need to deactivate and reactivate the plugin to create the table.</p>';
}

// 2. Check current user
echo '<h3>2. Current User</h3>';
$current_user_id = get_current_user_id();
$current_user = wp_get_current_user();
echo '<p>User ID: ' . $current_user_id . '</p>';
echo '<p>Username: ' . $current_user->user_login . '</p>';
echo '<p>Roles: ' . implode(', ', $current_user->roles) . '</p>';

// 3. Check session
echo '<h3>3. Session Check</h3>';
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
if (isset($_SESSION['maneli']['user_id'])) {
    echo '<p>Session user_id: ' . $_SESSION['maneli']['user_id'] . '</p>';
} elseif (isset($_SESSION['maneli_user_id'])) {
    echo '<p>Session user_id (old format): ' . $_SESSION['maneli_user_id'] . '</p>';
} else {
    echo '<p>No session user_id found</p>';
}

// 4. Test notification creation
echo '<h3>4. Test Notification Creation</h3>';
require_once(plugin_dir_path(__FILE__) . 'includes/class-notification-handler.php');

$test_notification = Maneli_Notification_Handler::create_notification(array(
    'user_id' => $current_user_id,
    'type' => 'test',
    'title' => 'Test Notification',
    'message' => 'This is a test notification created at ' . current_time('mysql'),
    'link' => home_url('/dashboard'),
    'related_id' => 0,
));

if ($test_notification) {
    echo '<p style="color: green;">✓ Test notification created successfully! ID: ' . $test_notification . '</p>';
} else {
    echo '<p style="color: red;">✗ Failed to create test notification</p>';
    if ($wpdb->last_error) {
        echo '<p style="color: red;">Database error: ' . $wpdb->last_error . '</p>';
    }
}

// 5. Get notifications for current user
echo '<h3>5. Get Notifications</h3>';
$notifications = Maneli_Notification_Handler::get_notifications(array(
    'user_id' => $current_user_id,
    'limit' => 10
));

echo '<p>Found ' . count($notifications) . ' notifications</p>';
if (!empty($notifications)) {
    echo '<h4>Latest Notifications:</h4><pre>';
    foreach (array_slice($notifications, 0, 5) as $notif) {
        echo "ID: {$notif->id}, Type: {$notif->type}, Title: {$notif->title}, Read: {$notif->is_read}, Created: {$notif->created_at}\n";
    }
    echo '</pre>';
}

// 6. Get unread count
echo '<h3>6. Unread Count</h3>';
$unread_count = Maneli_Notification_Handler::get_unread_count($current_user_id);
echo '<p>Unread notifications: ' . $unread_count . '</p>';

// 7. Check all users with notifications
echo '<h3>7. All Users with Notifications</h3>';
$all_users = $wpdb->get_results("SELECT DISTINCT user_id, COUNT(*) as count FROM {$table} GROUP BY user_id ORDER BY count DESC LIMIT 10");
if ($all_users) {
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>User ID</th><th>Username</th><th>Notification Count</th></tr>';
    foreach ($all_users as $user_data) {
        $user = get_user_by('ID', $user_data->user_id);
        echo '<tr>';
        echo '<td>' . $user_data->user_id . '</td>';
        echo '<td>' . ($user ? $user->user_login : 'User not found') . '</td>';
        echo '<td>' . $user_data->count . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

echo '<hr>';
echo '<p><strong>Remember to DELETE this file after testing!</strong></p>';

