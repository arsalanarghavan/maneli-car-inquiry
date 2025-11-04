<?php
/**
 * Status Migration Handler
 * Converts old inquiry statuses to new status structure
 * 
 * @package Maneli_Car_Inquiry/Includes/Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Status_Migration {

    /**
     * Valid tracking statuses (new structure)
     */
    private static $valid_tracking_statuses = [
        'new',
        'referred',
        'in_progress',
        'follow_up_scheduled',
        'meeting_scheduled',
        'awaiting_documents',
        'approved',
        'rejected',
        'completed'
    ];

    /**
     * Valid cash inquiry statuses (new structure)
     */
    private static $valid_cash_statuses = [
        'new',
        'referred',
        'in_progress',
        'follow_up_scheduled',
        'meeting_scheduled',
        'awaiting_downpayment',
        'downpayment_received',
        'awaiting_documents',
        'approved',
        'rejected',
        'completed'
    ];

    /**
     * Status mapping for old to new conversion
     */
    private static $status_mapping = [
        'cancelled' => 'rejected', // cancelled -> rejected
    ];

    /**
     * Run migration for all inquiries
     * 
     * @return array Migration results
     */
    public static function migrate_all_statuses() {
        $results = [
            'installment' => [
                'total' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
                'details' => []
            ],
            'cash' => [
                'total' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
                'details' => []
            ]
        ];

        // Migrate installment inquiries
        $installment_results = self::migrate_installment_statuses();
        $results['installment'] = $installment_results;

        // Migrate cash inquiries
        $cash_results = self::migrate_cash_statuses();
        $results['cash'] = $cash_results;

        return $results;
    }

    /**
     * Migrate installment inquiry statuses
     * 
     * @return array Migration results
     */
    public static function migrate_installment_statuses() {
        $results = [
            'total' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        // Get all installment inquiries
        $args = [
            'post_type' => 'inquiry',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];

        $inquiry_ids = get_posts($args);
        $results['total'] = count($inquiry_ids);

        foreach ($inquiry_ids as $inquiry_id) {
            $tracking_status = get_post_meta($inquiry_id, 'tracking_status', true);
            
            // If no tracking_status, set to 'new'
            if (empty($tracking_status)) {
                update_post_meta($inquiry_id, 'tracking_status', 'new');
                $results['updated']++;
                $results['details'][] = [
                    'id' => $inquiry_id,
                    'old_status' => '(empty)',
                    'new_status' => 'new',
                    'action' => 'set_default'
                ];
                continue;
            }

            // Check if status is valid
            if (in_array($tracking_status, self::$valid_tracking_statuses, true)) {
                $results['skipped']++;
                continue;
            }

            // Try to map old status to new one
            if (isset(self::$status_mapping[$tracking_status])) {
                $new_status = self::$status_mapping[$tracking_status];
                update_post_meta($inquiry_id, 'tracking_status', $new_status);
                $results['updated']++;
                $results['details'][] = [
                    'id' => $inquiry_id,
                    'old_status' => $tracking_status,
                    'new_status' => $new_status,
                    'action' => 'mapped'
                ];
            } else {
                // Unknown status - set to 'new' as default
                update_post_meta($inquiry_id, 'tracking_status', 'new');
                $results['updated']++;
                $results['details'][] = [
                    'id' => $inquiry_id,
                    'old_status' => $tracking_status,
                    'new_status' => 'new',
                    'action' => 'defaulted'
                ];
            }
        }

        return $results;
    }

    /**
     * Migrate cash inquiry statuses
     * 
     * @return array Migration results
     */
    public static function migrate_cash_statuses() {
        $results = [
            'total' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];

        // Get all cash inquiries
        $args = [
            'post_type' => 'cash_inquiry',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];

        $inquiry_ids = get_posts($args);
        $results['total'] = count($inquiry_ids);

        foreach ($inquiry_ids as $inquiry_id) {
            $cash_status = get_post_meta($inquiry_id, 'cash_inquiry_status', true);
            
            // If no cash_inquiry_status, set to 'new'
            if (empty($cash_status)) {
                update_post_meta($inquiry_id, 'cash_inquiry_status', 'new');
                $results['updated']++;
                $results['details'][] = [
                    'id' => $inquiry_id,
                    'old_status' => '(empty)',
                    'new_status' => 'new',
                    'action' => 'set_default'
                ];
                continue;
            }

            // Handle 'pending' status (convert to 'new')
            if ($cash_status === 'pending') {
                update_post_meta($inquiry_id, 'cash_inquiry_status', 'new');
                $results['updated']++;
                $results['details'][] = [
                    'id' => $inquiry_id,
                    'old_status' => 'pending',
                    'new_status' => 'new',
                    'action' => 'mapped'
                ];
                continue;
            }

            // Check if status is valid
            if (in_array($cash_status, self::$valid_cash_statuses, true)) {
                $results['skipped']++;
                continue;
            }

            // Try to map old status to new one
            if (isset(self::$status_mapping[$cash_status])) {
                $new_status = self::$status_mapping[$cash_status];
                update_post_meta($inquiry_id, 'cash_inquiry_status', $new_status);
                $results['updated']++;
                $results['details'][] = [
                    'id' => $inquiry_id,
                    'old_status' => $cash_status,
                    'new_status' => $new_status,
                    'action' => 'mapped'
                ];
            } else {
                // Unknown status - set to 'new' as default
                update_post_meta($inquiry_id, 'cash_inquiry_status', 'new');
                $results['updated']++;
                $results['details'][] = [
                    'id' => $inquiry_id,
                    'old_status' => $cash_status,
                    'new_status' => 'new',
                    'action' => 'defaulted'
                ];
            }
        }

        return $results;
    }

    /**
     * Get migration statistics (without running migration)
     * 
     * @return array Statistics
     */
    public static function get_migration_stats() {
        $stats = [
            'installment' => [
                'total' => 0,
                'invalid' => 0,
                'empty' => 0,
                'cancelled' => 0,
                'other_invalid' => []
            ],
            'cash' => [
                'total' => 0,
                'invalid' => 0,
                'empty' => 0,
                'pending' => 0,
                'other_invalid' => []
            ]
        ];

        // Check installment inquiries
        $installment_args = [
            'post_type' => 'inquiry',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];
        $installment_ids = get_posts($installment_args);
        $stats['installment']['total'] = count($installment_ids);

        foreach ($installment_ids as $id) {
            $status = get_post_meta($id, 'tracking_status', true);
            if (empty($status)) {
                $stats['installment']['empty']++;
            } elseif (!in_array($status, self::$valid_tracking_statuses, true)) {
                $stats['installment']['invalid']++;
                if ($status === 'cancelled') {
                    $stats['installment']['cancelled']++;
                } else {
                    if (!isset($stats['installment']['other_invalid'][$status])) {
                        $stats['installment']['other_invalid'][$status] = 0;
                    }
                    $stats['installment']['other_invalid'][$status]++;
                }
            }
        }

        // Check cash inquiries
        $cash_args = [
            'post_type' => 'cash_inquiry',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];
        $cash_ids = get_posts($cash_args);
        $stats['cash']['total'] = count($cash_ids);

        foreach ($cash_ids as $id) {
            $status = get_post_meta($id, 'cash_inquiry_status', true);
            if (empty($status)) {
                $stats['cash']['empty']++;
            } elseif (!in_array($status, self::$valid_cash_statuses, true)) {
                $stats['cash']['invalid']++;
                if ($status === 'pending') {
                    $stats['cash']['pending']++;
                } else {
                    if (!isset($stats['cash']['other_invalid'][$status])) {
                        $stats['cash']['other_invalid'][$status] = 0;
                    }
                    $stats['cash']['other_invalid'][$status]++;
                }
            }
        }

        return $stats;
    }
}
