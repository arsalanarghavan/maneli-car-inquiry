<?php
/**
 * Visitor Statistics Class
 * مدیریت آمار بازدیدکنندگان و ردیابی بازدیدها
 * 
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Visitor_Statistics {
    
    /**
     * Cached lookup tables for country metadata.
     *
     * @var array|null
     */
    private static $country_lookup_tables = null;
    
    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Detect if user agent is a bot
     */
    private static function is_bot($user_agent) {
        if (empty($user_agent)) {
            return true;
        }
        
        $bots = [
            'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
            'yandexbot', 'sogou', 'exabot', 'facebot', 'ia_archiver',
            'applebot', 'twitterbot', 'rogerbot', 'linkedinbot', 'embedly',
            'quora', 'pinterest', 'slackbot', 'redditbot', 'applebot',
            'flipboard', 'tumblr', 'bitlybot', 'skypeuripreview', 'nuzzel',
            'qwantbot', 'pinterestbot', 'bitrix', 'xing-contenttabreceiver',
            'chrome-lighthouse', 'semrushbot', 'dotbot', 'megaindex', 'ahrefsbot'
        ];
        
        $user_agent_lower = strtolower($user_agent);
        foreach ($bots as $bot) {
            if (strpos($user_agent_lower, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Parse user agent to get browser, OS, and device info
     */
    private static function parse_user_agent($user_agent) {
        $result = [
            'browser' => 'Unknown',
            'browser_version' => '',
            'os' => 'Unknown',
            'os_version' => '',
            'device_type' => 'desktop',
            'device_model' => ''
        ];
        $result['normalized_device_model'] = '';
        
        if (empty($user_agent)) {
            return $result;
        }
        
        // Detect OS
        if (preg_match('/windows nt 10/i', $user_agent)) {
            $result['os'] = 'Windows';
            $result['os_version'] = '10';
            $result['normalized_device_model'] = 'Desktop';
        } elseif (preg_match('/windows nt 6\.3/i', $user_agent)) {
            $result['os'] = 'Windows';
            $result['os_version'] = '8.1';
            $result['normalized_device_model'] = 'Desktop';
        } elseif (preg_match('/windows nt 6\.2/i', $user_agent)) {
            $result['os'] = 'Windows';
            $result['os_version'] = '8';
            $result['normalized_device_model'] = 'Desktop';
        } elseif (preg_match('/windows nt 6\.1/i', $user_agent)) {
            $result['os'] = 'Windows';
            $result['os_version'] = '7';
            $result['normalized_device_model'] = 'Desktop';
        } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
            $result['os'] = 'macOS';
            if (preg_match('/mac os x (\d+)[._](\d+)/i', $user_agent, $matches)) {
                $result['os_version'] = $matches[1] . '.' . $matches[2];
            }
            $result['normalized_device_model'] = 'Desktop';
        } elseif (preg_match('/linux/i', $user_agent)) {
            $result['os'] = 'Linux';
            $result['normalized_device_model'] = 'Desktop';
        } elseif (preg_match('/android/i', $user_agent)) {
            $result['os'] = 'Android';
            $result['device_type'] = 'mobile';
            if (preg_match('/android ([\d.]+)/i', $user_agent, $matches)) {
                $result['os_version'] = $matches[1];
            }
            // Detect device model
            if (preg_match('/(samsung|huawei|xiaomi|oneplus|oppo|vivo|realme|motorola|lg|sony|htc|nokia|asus|lenovo|zte|honor|google pixel|iphone)/i', $user_agent, $matches)) {
                $result['device_model'] = ucfirst(strtolower($matches[1]));
                $result['normalized_device_model'] = $result['device_model'];
            } else {
                $result['normalized_device_model'] = 'Android Device';
            }
        } elseif (preg_match('/iphone|ipod/i', $user_agent)) {
            $result['os'] = 'iOS';
            $result['device_type'] = 'mobile';
            $result['device_model'] = 'iPhone';
            $result['normalized_device_model'] = 'iPhone';
            if (preg_match('/os ([\d_]+)/i', $user_agent, $matches)) {
                $result['os_version'] = str_replace('_', '.', $matches[1]);
            }
        } elseif (preg_match('/ipad/i', $user_agent)) {
            $result['os'] = 'iOS';
            $result['device_type'] = 'tablet';
            $result['device_model'] = 'iPad';
            $result['normalized_device_model'] = 'iPad';
            if (preg_match('/os ([\d_]+)/i', $user_agent, $matches)) {
                $result['os_version'] = str_replace('_', '.', $matches[1]);
            }
        }
        
        // Detect Browser
        if (preg_match('/edg\/([\d.]+)/i', $user_agent, $matches)) {
            $result['browser'] = 'Edge';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/chrome\/([\d.]+)/i', $user_agent, $matches)) {
            $result['browser'] = 'Chrome';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/safari\/([\d.]+)/i', $user_agent, $matches) && !preg_match('/chrome/i', $user_agent)) {
            $result['browser'] = 'Safari';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/firefox\/([\d.]+)/i', $user_agent, $matches)) {
            $result['browser'] = 'Firefox';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/msie|trident/i', $user_agent)) {
            $result['browser'] = 'IE';
            if (preg_match('/msie ([\d.]+)/i', $user_agent, $matches)) {
                $result['browser_version'] = $matches[1];
            }
        } elseif (preg_match('/opera|opr\//i', $user_agent, $matches)) {
            $result['browser'] = 'Opera';
            if (preg_match('/(?:opera|opr)\/([\d.]+)/i', $user_agent, $matches)) {
                $result['browser_version'] = $matches[1];
            }
        }
        
        // Fallback OS detection
        if ($result['os'] === 'Unknown') {
            if (stripos($user_agent, 'windows') !== false) {
                $result['os'] = 'Windows';
                $result['normalized_device_model'] = 'Desktop';
            } elseif (stripos($user_agent, 'macintosh') !== false || stripos($user_agent, 'mac os') !== false) {
                $result['os'] = 'macOS';
                $result['normalized_device_model'] = 'Desktop';
            } elseif (stripos($user_agent, 'linux') !== false || stripos($user_agent, 'x11') !== false) {
                $result['os'] = 'Linux';
                $result['normalized_device_model'] = 'Desktop';
            } elseif (stripos($user_agent, 'android') !== false) {
                $result['os'] = 'Android';
                $result['device_type'] = 'mobile';
                $result['normalized_device_model'] = 'Android Device';
            } elseif (stripos($user_agent, 'iphone') !== false || stripos($user_agent, 'ipod') !== false) {
                $result['os'] = 'iOS';
                $result['device_type'] = 'mobile';
                $result['normalized_device_model'] = 'iPhone';
            } elseif (stripos($user_agent, 'ipad') !== false) {
                $result['os'] = 'iPadOS';
                $result['device_type'] = 'tablet';
                $result['normalized_device_model'] = 'iPad';
            }
        }

        // Fallback browser detection
        if ($result['browser'] === 'Unknown') {
            if (stripos($user_agent, 'edg') !== false || stripos($user_agent, 'edge') !== false) {
                $result['browser'] = 'Edge';
            } elseif (stripos($user_agent, 'opr') !== false || stripos($user_agent, 'opera') !== false) {
                $result['browser'] = 'Opera';
            } elseif (stripos($user_agent, 'chrome') !== false && stripos($user_agent, 'chromium') === false) {
                $result['browser'] = 'Chrome';
            } elseif (stripos($user_agent, 'safari') !== false && stripos($user_agent, 'chrome') === false) {
                $result['browser'] = 'Safari';
            } elseif (stripos($user_agent, 'firefox') !== false) {
                $result['browser'] = 'Firefox';
            } elseif (stripos($user_agent, 'chromium') !== false) {
                $result['browser'] = 'Chromium';
            } elseif (stripos($user_agent, 'msie') !== false || stripos($user_agent, 'trident') !== false) {
                $result['browser'] = 'IE';
            }
        }

        // Fallback device type detection
        if ($result['device_type'] === 'desktop') {
            $ua_lower = strtolower($user_agent);
            if (strpos($ua_lower, 'mobile') !== false || strpos($ua_lower, 'iphone') !== false || strpos($ua_lower, 'android') !== false || strpos($ua_lower, 'blackberry') !== false) {
                $result['device_type'] = 'mobile';
            } elseif (strpos($ua_lower, 'tablet') !== false || strpos($ua_lower, 'ipad') !== false || strpos($ua_lower, 'kindle') !== false) {
                $result['device_type'] = 'tablet';
            }
        }

        if (empty($result['device_model'])) {
            if (!empty($result['normalized_device_model'])) {
                $result['device_model'] = $result['normalized_device_model'];
            } else {
                if ($result['device_type'] === 'desktop') {
                    $result['device_model'] = 'Desktop';
                } elseif ($result['device_type'] === 'mobile') {
                    $result['device_model'] = ($result['os'] === 'Android') ? 'Android Device' : 'Mobile Device';
                } elseif ($result['device_type'] === 'tablet') {
                    $result['device_model'] = 'Tablet Device';
                }
            }
        }

        unset($result['normalized_device_model']);

        return $result;
    }
    
    /**
     * Get country from IP (simple detection - can be enhanced with GeoIP service)
     */
    private static function get_country_from_ip($ip) {
        $default_location = [
            'country' => 'Unknown',
            'country_code' => ''
        ];

        // Allow site owners to disable GeoIP lookup if desired.
        $geoip_enabled = apply_filters('maneli_enable_geoip_lookup', true, $ip);
        if (!$geoip_enabled) {
            return $default_location;
        }

        if (self::is_private_ip($ip)) {
            return ['country' => 'Local', 'country_code' => 'LOC'];
        }

        $transient_key = 'maneli_geoip_' . md5($ip);
        $cached_value = get_transient($transient_key);
        if ($cached_value !== false && is_array($cached_value)) {
            return $cached_value;
        }

        $geoip_endpoint = apply_filters(
            'maneli_geoip_endpoint',
            sprintf('https://ipwho.is/%s?output=json', rawurlencode($ip)),
            $ip
        );

        $request_args = apply_filters(
            'maneli_geoip_request_args',
            [
                'timeout' => 5,
                'redirection' => 2,
            ],
            $ip
        );

        $country_data = $default_location;
        $response = wp_remote_get($geoip_endpoint, $request_args);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (is_array($data)) {
                // ipwho.is format
                if (isset($data['success']) && $data['success'] === true) {
                    $country_name = isset($data['country']) ? sanitize_text_field($data['country']) : '';
                    $country_code = isset($data['country_code']) ? sanitize_text_field($data['country_code']) : '';

                    if (!empty($country_name)) {
                        $country_data = [
                            'country' => $country_name,
                            'country_code' => strtoupper($country_code)
                        ];
                    }
                }

                // ipapi.co format fallback
                if (isset($data['country_name']) && !empty($data['country_name'])) {
                    $country_data = [
                        'country' => sanitize_text_field($data['country_name']),
                        'country_code' => isset($data['country']) ? strtoupper(sanitize_text_field($data['country'])) : ''
                    ];
                }

                // ip-api.com fallback
                if (isset($data['status']) && $data['status'] === 'success' && isset($data['country'])) {
                    $country_data = [
                        'country' => sanitize_text_field($data['country']),
                        'country_code' => isset($data['countryCode']) ? strtoupper(sanitize_text_field($data['countryCode'])) : ''
                    ];
                }
            }
        }

        // Cache result (including Unknown) for 12 hours to avoid rate limits.
        set_transient($transient_key, $country_data, HOUR_IN_SECONDS * 12);

        return $country_data;
    }

    /**
     * Determine if IP is private or local
     */
    private static function is_private_ip($ip) {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        // Filter out private ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }
    
    /**
     * Extract search engine and keyword from referrer
     */
    private static function parse_search_engine($referrer) {
        if (empty($referrer)) {
            return ['engine' => null, 'keyword' => null];
        }
        
        $parsed = parse_url($referrer);
        if (!isset($parsed['host'])) {
            return ['engine' => null, 'keyword' => null];
        }
        
        $host = strtolower($parsed['host']);
        $engine = null;
        $keyword = null;
        
        // Google
        if (strpos($host, 'google.') !== false) {
            $engine = 'google';
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $query);
                $keyword = isset($query['q']) ? $query['q'] : null;
            }
        }
        // Bing
        elseif (strpos($host, 'bing.') !== false) {
            $engine = 'bing';
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $query);
                $keyword = isset($query['q']) ? $query['q'] : null;
            }
        }
        // Yahoo
        elseif (strpos($host, 'yahoo.') !== false || strpos($host, 'search.yahoo') !== false) {
            $engine = 'yahoo';
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $query);
                $keyword = isset($query['p']) ? $query['p'] : (isset($query['q']) ? $query['q'] : null);
            }
        }
        // DuckDuckGo
        elseif (strpos($host, 'duckduckgo.') !== false) {
            $engine = 'duckduckgo';
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $query);
                $keyword = isset($query['q']) ? $query['q'] : null;
            }
        }
        // Yandex
        elseif (strpos($host, 'yandex.') !== false) {
            $engine = 'yandex';
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $query);
                $keyword = isset($query['text']) ? $query['text'] : null;
            }
        }
        
        return ['engine' => $engine, 'keyword' => $keyword];
    }
    
    /**
     * Track a visit
     */
    public static function track_visit($page_url = null, $page_title = null, $referrer = null, $product_id = null) {
        global $wpdb;
        
        // Get visitor info
        $ip = self::get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Check if bot
        if (self::is_bot($user_agent)) {
            return false;
        }
        
        // Skip admin IPs (optional)
        $admin_ips = apply_filters('maneli_skip_tracking_ips', ['127.0.0.1', '::1']);
        if (in_array($ip, $admin_ips)) {
            return false;
        }
        
        // Get current page if not provided
        if ($page_url === null) {
            $page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
        
        // Parse user agent
        $ua_info = self::parse_user_agent($user_agent);
        
        // Get country
        $country_info = self::get_country_from_ip($ip);
        
        // Parse search engine
        $search_info = self::parse_search_engine($referrer);
        
        // Get or create visitor
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        $visitor = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $visitors_table WHERE ip_address = %s AND user_agent = %s LIMIT 1",
            $ip,
            $user_agent
        ));
        
        if ($visitor) {
            // Update visitor
            $wpdb->update(
                $visitors_table,
                [
                    'last_visit' => current_time('mysql'),
                    'visit_count' => $visitor->visit_count + 1,
                    'country' => $country_info['country'],
                    'country_code' => $country_info['country_code'],
                    'browser' => $ua_info['browser'],
                    'browser_version' => $ua_info['browser_version'],
                    'os' => $ua_info['os'],
                    'os_version' => $ua_info['os_version'],
                    'device_type' => $ua_info['device_type'],
                    'device_model' => $ua_info['device_model']
                ],
                ['id' => $visitor->id],
                ['%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );
            $visitor_id = $visitor->id;
        } else {
            // Create new visitor
            $wpdb->insert(
                $visitors_table,
                [
                    'ip_address' => $ip,
                    'user_agent' => $user_agent,
                    'country' => $country_info['country'],
                    'country_code' => $country_info['country_code'],
                    'browser' => $ua_info['browser'],
                    'browser_version' => $ua_info['browser_version'],
                    'os' => $ua_info['os'],
                    'os_version' => $ua_info['os_version'],
                    'device_type' => $ua_info['device_type'],
                    'device_model' => $ua_info['device_model'],
                    'first_visit' => current_time('mysql'),
                    'last_visit' => current_time('mysql'),
                    'visit_count' => 1,
                    'is_bot' => 0
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d']
            );
            $visitor_id = $wpdb->insert_id;
        }
        
        if (!$visitor_id) {
            return false;
        }
        
        // Generate session ID
        $session_id = self::get_session_id();
        
        // Extract referrer domain
        $referrer_domain = null;
        if ($referrer) {
            $parsed = parse_url($referrer);
            $referrer_domain = isset($parsed['host']) ? $parsed['host'] : null;
        }
        
        // Record visit
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $wpdb->insert(
            $visits_table,
            [
                'visitor_id' => $visitor_id,
                'page_url' => $page_url,
                'page_title' => $page_title,
                'referrer' => $referrer,
                'referrer_domain' => $referrer_domain,
                'search_engine' => $search_info['engine'],
                'search_keyword' => $search_info['keyword'],
                'visit_date' => current_time('mysql'),
                'session_id' => $session_id,
                'product_id' => $product_id
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d']
        );
        
        // Update page statistics
        self::update_page_stats($page_url, $page_title);
        
        // Update search engine statistics
        if ($search_info['engine']) {
            self::update_search_engine_stats($search_info['engine'], $search_info['keyword']);
        }
        
        // Update referrer statistics
        if ($referrer_domain) {
            self::update_referrer_stats($referrer, $referrer_domain);
        }
        
        return true;
    }
    
    /**
     * Get session ID
     */
    private static function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        if (!isset($_SESSION['maneli_session_id'])) {
            $_SESSION['maneli_session_id'] = wp_generate_password(32, false);
        }
        return $_SESSION['maneli_session_id'];
    }
    
    /**
     * Update page statistics
     */
    private static function update_page_stats($page_url, $page_title) {
        global $wpdb;
        $pages_table = $wpdb->prefix . 'maneli_pages';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $pages_table WHERE page_url = %s LIMIT 1",
            $page_url
        ));
        
        if ($existing) {
            $wpdb->update(
                $pages_table,
                [
                    'visit_count' => $existing->visit_count + 1,
                    'last_visit' => current_time('mysql'),
                    'page_title' => $page_title ?: $existing->page_title
                ],
                ['id' => $existing->id],
                ['%d', '%s', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $pages_table,
                [
                    'page_url' => $page_url,
                    'page_title' => $page_title,
                    'visit_count' => 1,
                    'unique_visitors' => 1,
                    'last_visit' => current_time('mysql')
                ],
                ['%s', '%s', '%d', '%d', '%s']
            );
        }
    }
    
    /**
     * Update search engine statistics
     */
    private static function update_search_engine_stats($engine_name, $keyword) {
        global $wpdb;
        $search_table = $wpdb->prefix . 'maneli_search_engines';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $search_table WHERE engine_name = %s AND keyword = %s LIMIT 1",
            $engine_name,
            $keyword
        ));
        
        if ($existing) {
            $wpdb->update(
                $search_table,
                [
                    'visit_count' => $existing->visit_count + 1,
                    'last_visit' => current_time('mysql')
                ],
                ['id' => $existing->id],
                ['%d', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $search_table,
                [
                    'engine_name' => $engine_name,
                    'keyword' => $keyword,
                    'visit_count' => 1,
                    'last_visit' => current_time('mysql')
                ],
                ['%s', '%s', '%d', '%s']
            );
        }
    }
    
    /**
     * Update referrer statistics
     */
    private static function update_referrer_stats($referrer_url, $referrer_domain) {
        global $wpdb;
        $referrers_table = $wpdb->prefix . 'maneli_referrers';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $referrers_table WHERE referrer_domain = %s LIMIT 1",
            $referrer_domain
        ));
        
        if ($existing) {
            $wpdb->update(
                $referrers_table,
                [
                    'visit_count' => $existing->visit_count + 1,
                    'last_visit' => current_time('mysql'),
                    'referrer_url' => $referrer_url
                ],
                ['id' => $existing->id],
                ['%d', '%s', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $referrers_table,
                [
                    'referrer_url' => $referrer_url,
                    'referrer_domain' => $referrer_domain,
                    'visit_count' => 1,
                    'unique_visitors' => 1,
                    'last_visit' => current_time('mysql')
                ],
                ['%s', '%s', '%d', '%d', '%s']
            );
        }
    }
    
    /**
     * Get overall statistics
     */
    public static function get_overall_stats($start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT v.id) as total_visits,
                COUNT(DISTINCT v.visitor_id) as unique_visitors,
                COUNT(DISTINCT v.page_url) as total_pages
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0",
            $start_date,
            $end_date
        ));
        
        return [
            'total_visits' => (int)($stats->total_visits ?? 0),
            'unique_visitors' => (int)($stats->unique_visitors ?? 0),
            'total_pages' => (int)($stats->total_pages ?? 0)
        ];
    }
    
    /**
     * Get daily visits
     */
    public static function get_daily_visits($start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(v.visit_date) as date,
                COUNT(DISTINCT v.id) as visits,
                COUNT(DISTINCT v.visitor_id) as unique_visitors
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            GROUP BY DATE(v.visit_date)
            ORDER BY date ASC",
            $start_date,
            $end_date
        ));
        
        return $results;
    }
    
    /**
     * Get top pages
     */
    public static function get_top_pages($limit = 10, $start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                v.page_url,
                v.page_title,
                COUNT(DISTINCT v.id) as visit_count,
                COUNT(DISTINCT v.visitor_id) as unique_visitors
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            GROUP BY v.page_url, v.page_title
            ORDER BY visit_count DESC
            LIMIT %d",
            $start_date,
            $end_date,
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get top products (cars)
     */
    public static function get_top_products($limit = 10, $start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                v.product_id,
                COUNT(DISTINCT v.id) as visit_count,
                COUNT(DISTINCT v.visitor_id) as unique_visitors,
                p.post_title as product_name
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            LEFT JOIN {$wpdb->posts} p ON v.product_id = p.ID
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            AND v.product_id IS NOT NULL
            GROUP BY v.product_id
            ORDER BY visit_count DESC
            LIMIT %d",
            $start_date,
            $end_date,
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get browser statistics
     */
    public static function get_browser_stats($start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $raw_results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                vis.browser,
                COUNT(DISTINCT v.id) as visit_count,
                COUNT(DISTINCT v.visitor_id) as unique_visitors
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            AND vis.browser IS NOT NULL
            GROUP BY vis.browser
            ORDER BY visit_count DESC",
            $start_date,
            $end_date
        ), ARRAY_A);
        
        $results = [];
        foreach ($raw_results as $row) {
            $browser_raw = isset($row['browser']) ? trim((string) $row['browser']) : '';
            $browser_key = strtolower($browser_raw);
            $results[] = [
                'browser' => $browser_raw,
                'browser_label' => self::translate_browser_name($browser_raw),
                'browser_key' => $browser_key,
                'visit_count' => isset($row['visit_count']) ? (int) $row['visit_count'] : 0,
                'unique_visitors' => isset($row['unique_visitors']) ? (int) $row['unique_visitors'] : 0,
            ];
        }
        
        return $results;
    }
    
    /**
     * Get OS statistics
     */
    public static function get_os_stats($start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $raw_results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                vis.os,
                COUNT(DISTINCT v.id) as visit_count,
                COUNT(DISTINCT v.visitor_id) as unique_visitors
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            AND vis.os IS NOT NULL
            GROUP BY vis.os
            ORDER BY visit_count DESC",
            $start_date,
            $end_date
        ), ARRAY_A);
        
        $results = [];
        foreach ($raw_results as $row) {
            $os_raw = isset($row['os']) ? trim((string) $row['os']) : '';
            $os_key = strtolower($os_raw);
            $results[] = [
                'os' => $os_raw,
                'os_label' => self::translate_os_name($os_raw),
                'os_key' => $os_key,
                'visit_count' => isset($row['visit_count']) ? (int) $row['visit_count'] : 0,
                'unique_visitors' => isset($row['unique_visitors']) ? (int) $row['unique_visitors'] : 0,
            ];
        }
        
        return $results;
    }
    
    /**
     * Get device type statistics
     */
    public static function get_device_stats($start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $raw_results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                vis.device_type,
                COUNT(DISTINCT v.id) as visit_count,
                COUNT(DISTINCT v.visitor_id) as unique_visitors
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            AND vis.device_type IS NOT NULL
            GROUP BY vis.device_type
            ORDER BY visit_count DESC",
            $start_date,
            $end_date
        ), ARRAY_A);
        
        $results = [];
        foreach ($raw_results as $row) {
            $device_type_raw = isset($row['device_type']) ? strtolower(trim((string) $row['device_type'])) : '';
            if ($device_type_raw === '') {
                $device_type_raw = 'unknown';
            }
            $results[] = [
                'device_type' => $device_type_raw,
                'device_label' => self::translate_device_type($device_type_raw),
                'visit_count' => isset($row['visit_count']) ? (int) $row['visit_count'] : 0,
                'unique_visitors' => isset($row['unique_visitors']) ? (int) $row['unique_visitors'] : 0,
            ];
        }
        
        return $results;
    }
    
    /**
     * Get device model statistics
     */
    public static function get_device_model_stats($limit = 10, $start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                vis.device_model,
                COUNT(DISTINCT v.id) as visit_count,
                COUNT(DISTINCT v.visitor_id) as unique_visitors
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            AND vis.device_model IS NOT NULL
            AND vis.device_model != ''
            GROUP BY vis.device_model
            ORDER BY visit_count DESC
            LIMIT %d",
            $start_date,
            $end_date,
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get country statistics
     */
    public static function get_country_stats($start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                vis.country,
                vis.country_code,
                COUNT(DISTINCT v.id) as visit_count,
                COUNT(DISTINCT v.visitor_id) as unique_visitors
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            AND vis.country IS NOT NULL
            GROUP BY vis.country, vis.country_code
            ORDER BY visit_count DESC",
            $start_date,
            $end_date
        ));
        
        return $results;
    }
    
    /**
     * Get search engine statistics
     */
    public static function get_search_engine_stats($start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                v.search_engine,
                COUNT(DISTINCT v.id) as visit_count,
                COUNT(DISTINCT v.visitor_id) as unique_visitors
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            AND v.search_engine IS NOT NULL
            GROUP BY v.search_engine
            ORDER BY visit_count DESC",
            $start_date,
            $end_date
        ));
        
        return $results;
    }
    
    /**
     * Get referrer statistics
     */
    public static function get_referrer_stats($limit = 10, $start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $referrers_table = $wpdb->prefix . 'maneli_referrers';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                referrer_domain,
                referrer_url,
                visit_count,
                unique_visitors,
                last_visit
            FROM $referrers_table
            WHERE DATE(last_visit) >= %s 
            AND DATE(last_visit) <= %s
            ORDER BY visit_count DESC
            LIMIT %d",
            $start_date,
            $end_date,
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get recent visitors
     */
    public static function get_recent_visitors($limit = 50) {
        global $wpdb;
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                v.*,
                vis.ip_address,
                vis.country,
                vis.country_code,
                vis.browser,
                vis.os,
                vis.device_type,
                vis.device_model
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE vis.is_bot = 0
            ORDER BY v.visit_date DESC
            LIMIT %d",
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get online visitors (active in last 15 minutes)
     */
    public static function get_online_visitors() {
        global $wpdb;
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $results = $wpdb->get_results(
            "SELECT 
                vis.*,
                v.page_url,
                v.page_title,
                v.visit_date
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE vis.is_bot = 0
            AND v.visit_date >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            GROUP BY vis.id
            ORDER BY v.visit_date DESC"
        );
        
        if (!empty($results)) {
            $now = current_time('timestamp');
            foreach ($results as &$visitor) {
                $visitor->country = self::translate_country_name($visitor->country_code, $visitor->country);
                $visitor->browser = self::translate_browser_name($visitor->browser);
                $visitor->os      = self::translate_os_name($visitor->os);
                $visitor->device_type_label = self::translate_device_type($visitor->device_type);
                $visitor->time_ago = self::format_time_ago($visitor->visit_date, $now);
                $flag_icon = self::get_country_flag_icon($visitor->country_code, $visitor->country);
                $visitor->country_flag_icon = $flag_icon;
                $visitor->country_flag = $flag_icon;
            }
            unset($visitor);
        }
        
        return $results;
    }
    
    /**
     * Get most active visitors
     */
    public static function get_most_active_visitors($limit = 10, $start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'maneli_visits';
        $visitors_table = $wpdb->prefix . 'maneli_visitors';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                vis.*,
                COUNT(DISTINCT v.id) as visit_count
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            GROUP BY vis.id
            ORDER BY visit_count DESC
            LIMIT %d",
            $start_date,
            $end_date,
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Translate device type to localized label
     */
    public static function translate_device_type($device_type) {
        $device_type = strtolower((string) $device_type);
        $map = self::get_device_type_translation_map();
        return $map[$device_type] ?? $map['unknown'];
    }
    
    /**
     * Get device type translation map
     */
    public static function get_device_type_translation_map() {
        return [
            'desktop' => esc_html__('Desktop', 'maneli-car-inquiry'),
            'mobile' => esc_html__('Mobile', 'maneli-car-inquiry'),
            'tablet' => esc_html__('Tablet', 'maneli-car-inquiry'),
            'unknown' => esc_html__('Unknown', 'maneli-car-inquiry'),
        ];
    }

    /**
     * Translate device model label
     */
    public static function translate_device_model($device_model) {
        $device_model = trim((string) $device_model);
        if ($device_model === '') {
            return esc_html__('Unknown', 'maneli-car-inquiry');
        }
        
        $lookup = strtolower($device_model);
        $map = [
            'desktop' => esc_html__('Desktop', 'maneli-car-inquiry'),
            'android device' => esc_html__('Android Device', 'maneli-car-inquiry'),
            'mobile device' => esc_html__('Mobile Device', 'maneli-car-inquiry'),
            'tablet device' => esc_html__('Tablet Device', 'maneli-car-inquiry'),
            'iphone' => esc_html__('iPhone', 'maneli-car-inquiry'),
            'ipad' => esc_html__('iPad', 'maneli-car-inquiry'),
        ];
        
        return $map[$lookup] ?? $device_model;
    }
    
    /**
     * Translate browser name
     */
    public static function translate_browser_name($browser) {
        $browser = trim((string) $browser);
        if ($browser === '') {
            return esc_html__('Unknown', 'maneli-car-inquiry');
        }
        $lookup = strtolower($browser);
        $map = self::get_browser_translation_map();
        return $map[$lookup] ?? $browser;
    }
    
    /**
     * Browser translation map
     */
    public static function get_browser_translation_map() {
        return [
            'chrome' => esc_html__('Chrome', 'maneli-car-inquiry'),
            'firefox' => esc_html__('Firefox', 'maneli-car-inquiry'),
            'safari' => esc_html__('Safari', 'maneli-car-inquiry'),
            'edge' => esc_html__('Edge', 'maneli-car-inquiry'),
            'ie' => esc_html__('Internet Explorer', 'maneli-car-inquiry'),
            'opera' => esc_html__('Opera', 'maneli-car-inquiry'),
            'chromium' => esc_html__('Chromium', 'maneli-car-inquiry'),
            'unknown' => esc_html__('Unknown', 'maneli-car-inquiry'),
        ];
    }
    
    /**
     * Translate OS name
     */
    public static function translate_os_name($os) {
        $os = trim((string) $os);
        if ($os === '') {
            return esc_html__('Unknown', 'maneli-car-inquiry');
        }
        $lookup = strtolower($os);
        $map = self::get_os_translation_map();
        return $map[$lookup] ?? $os;
    }
    
    /**
     * OS translation map
     */
    public static function get_os_translation_map() {
        return [
            'windows' => esc_html__('Windows', 'maneli-car-inquiry'),
            'macos' => esc_html__('macOS', 'maneli-car-inquiry'),
            'linux' => esc_html__('Linux', 'maneli-car-inquiry'),
            'android' => esc_html__('Android', 'maneli-car-inquiry'),
            'ios' => esc_html__('iOS', 'maneli-car-inquiry'),
            'ipados' => esc_html__('iPadOS', 'maneli-car-inquiry'),
            'unknown' => esc_html__('Unknown', 'maneli-car-inquiry'),
        ];
    }
    
    /**
     * Translate country name using WooCommerce or fallback list
     */
    public static function translate_country_name($country_code, $fallback = '') {
        $entry = self::resolve_country_entry($country_code, $fallback);
        if ($entry !== null) {
            return self::localize_country_label($entry['names']);
        }

        $fallback_label = $fallback !== '' ? $fallback : esc_html__('Unknown', 'maneli-car-inquiry');
        $use_persian_digits = function_exists('maneli_should_use_persian_digits') ? maneli_should_use_persian_digits() : true;
        if (!$use_persian_digits && function_exists('maneli_convert_to_english_digits')) {
            $fallback_label = maneli_convert_to_english_digits($fallback_label);
        } elseif ($use_persian_digits && function_exists('persian_numbers_no_separator')) {
            $fallback_label = persian_numbers_no_separator($fallback_label);
        }

        return esc_html($fallback_label);
    }
    
    /**
     * Get map of ISO country code => translated country name
     */
    public static function get_country_translation_map() {
        static $translated = null;
        if ($translated !== null) {
            return $translated;
        }
        
        $translated = [];
        $use_persian_digits = function_exists('maneli_should_use_persian_digits') ? maneli_should_use_persian_digits() : true;

        if (function_exists('WC') && WC()->countries) {
            $countries = WC()->countries->get_countries();
            if (!empty($countries)) {
                foreach ($countries as $code => $name) {
                    $upper_code = strtoupper($code);
                    $entry = self::resolve_country_entry($upper_code, $name);
                    if ($entry) {
                        $translated[$upper_code] = self::localize_country_label($entry['names']);
                    } else {
                        $base_label = $use_persian_digits ? $name : _x($name, 'country name', 'maneli-car-inquiry');
                        if (!$use_persian_digits && function_exists('maneli_convert_to_english_digits')) {
                            $base_label = maneli_convert_to_english_digits($base_label);
                        } elseif ($use_persian_digits && function_exists('persian_numbers_no_separator')) {
                            $base_label = persian_numbers_no_separator($base_label);
                        }
                        $translated[$upper_code] = esc_html($base_label);
                    }
                }
            }
        }
        
        $lookup = self::build_country_lookup_tables();
        foreach ($lookup['by_code'] as $code => $names) {
            if (!isset($translated[$code])) {
                $translated[$code] = self::localize_country_label($names);
            }
        }
        
        $translated['UNKNOWN'] = self::localize_country_label($lookup['by_code']['UNKNOWN']);
        
        return $translated;
    }
    
    /**
     * Map of ISO country code => flag CSS class
     */
    public static function get_country_flag_map() {
        $flags = [];
        $lookup = self::build_country_lookup_tables();
        foreach ($lookup['by_code'] as $code => $label) {
            $flags[$code] = self::get_country_flag_icon($code);
        }
        $flags['UNKNOWN'] = self::get_country_flag_icon('unknown');
        $flags['unknown'] = $flags['UNKNOWN'];
        return $flags;
    }
    
    /**
     * Build and cache country lookup tables (by code/name).
     */
    private static function build_country_lookup_tables() {
        if (self::$country_lookup_tables !== null) {
            return self::$country_lookup_tables;
        }
        
        $definitions = self::get_country_definitions();
        $by_code = [];
        $by_name = [];
        
        foreach ($definitions as $code => $names) {
            $upper_code = strtoupper($code);
            $by_code[$upper_code] = $names;
            
            $variants = self::generate_country_name_variants($names['en']);
            $variants[] = strtolower($upper_code);
            $variants[] = strtolower($names['en'] . ' (' . $upper_code . ')');
            $variants[] = strtolower($names['en'] . ' (' . strtolower($upper_code) . ')');
            
            foreach ($variants as $variant) {
                if ($variant === '') {
                    continue;
                }
                $by_name[$variant] = [
                    'code' => $upper_code,
                    'names'=> $names,
                ];
            }
        }
        
        $unknown_names = [
            'en' => esc_html__('Unknown', 'maneli-car-inquiry'),
            'fa' => esc_html__('نامشخص', 'maneli-car-inquiry'),
        ];
        $by_code['UNKNOWN'] = $unknown_names;
        $by_name['unknown'] = [
            'code' => '',
            'names'=> $unknown_names,
        ];
        
        self::$country_lookup_tables = [
            'definitions' => $definitions,
            'by_code'     => $by_code,
            'by_name'     => $by_name,
        ];
        
        return self::$country_lookup_tables;
    }
    
    /**
     * Generate lookup variants for a country name.
     */
    private static function generate_country_name_variants($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }
        
        $variants = [];
        $lower = strtolower($value);
        $variants[] = $lower;
        $variants[] = preg_replace('/^the\s+/', '', $lower);
        $variants[] = preg_replace('/\s+/', ' ', preg_replace('/\s*\((.*?)\)\s*/', ' ', $lower));
        $variants[] = str_replace(['-', '_'], ' ', $lower);
        
        if (preg_match_all('/\((.*?)\)/', $value, $matches)) {
            foreach ($matches[1] as $match) {
                $match = strtolower(trim($match));
                if ($match !== '') {
                    $variants[] = $match;
                }
            }
        }
        
        // Remove any duplicate or empty variants.
        $variants = array_unique(array_filter(array_map(function ($item) {
            return trim(preg_replace('/\s+/', ' ', $item));
        }, $variants)));
        
        return $variants;
    }
    
    /**
     * Resolve country entry (code + translated label) using code/name
     */
    private static function resolve_country_entry($country_code, $fallback = '') {
        $lookup = self::build_country_lookup_tables();
        
        $code = strtoupper(trim((string) $country_code));
        if ($code !== '' && isset($lookup['by_code'][$code])) {
            return [
                'code'  => $code,
                'names' => $lookup['by_code'][$code],
            ];
        }
        
        $candidates = array_merge(
            self::generate_country_name_variants($country_code),
            self::generate_country_name_variants($fallback)
        );
        
        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }
            
            if (isset($lookup['by_name'][$candidate])) {
                return $lookup['by_name'][$candidate];
            }
            
            $candidate_code = strtoupper($candidate);
            if ($candidate_code !== '' && isset($lookup['by_code'][$candidate_code])) {
                return [
                    'code'  => $candidate_code,
                    'names' => $lookup['by_code'][$candidate_code],
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Country fallback names keyed by ISO or lowercase name
     */
    private static function get_country_name_fallback_map() {
        static $map = null;
        if ($map !== null) {
            return $map;
        }
        
        $map = [];
        $definitions = self::get_country_definitions();
        foreach ($definitions as $code => $names) {
            $upper_code = strtoupper($code);
            $localized = self::localize_country_label($names);
            $map[$upper_code] = $localized;
            $map[strtolower($upper_code)] = $localized;
            
            $variants = self::generate_country_name_variants($names['en']);
            foreach ($variants as $variant) {
                $map[$variant] = $localized;
            }
            
            $map[$names['en']] = $localized;
        }
        
        $unknown_label = self::localize_country_label([
            'en' => esc_html__('Unknown', 'maneli-car-inquiry'),
            'fa' => esc_html__('نامشخص', 'maneli-car-inquiry'),
        ]);
        $map['unknown'] = $unknown_label;
        $map['UNKNOWN'] = $unknown_label;
        
        return $map;
    }

    /**
     * Ensure country names are registered for translators.
     */
    private static function register_country_translation_strings() {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        /* translators: country name */
        _x('Local', 'country name', 'maneli-car-inquiry');
        _x('Afghanistan', 'country name', 'maneli-car-inquiry');
        _x('Åland Islands', 'country name', 'maneli-car-inquiry');
        _x('Albania', 'country name', 'maneli-car-inquiry');
        _x('Algeria', 'country name', 'maneli-car-inquiry');
        _x('American Samoa', 'country name', 'maneli-car-inquiry');
        _x('Andorra', 'country name', 'maneli-car-inquiry');
        _x('Angola', 'country name', 'maneli-car-inquiry');
        _x('Anguilla', 'country name', 'maneli-car-inquiry');
        _x('Antarctica', 'country name', 'maneli-car-inquiry');
        _x('Antigua and Barbuda', 'country name', 'maneli-car-inquiry');
        _x('Argentina', 'country name', 'maneli-car-inquiry');
        _x('Armenia', 'country name', 'maneli-car-inquiry');
        _x('Aruba', 'country name', 'maneli-car-inquiry');
        _x('Australia', 'country name', 'maneli-car-inquiry');
        _x('Austria', 'country name', 'maneli-car-inquiry');
        _x('Azerbaijan', 'country name', 'maneli-car-inquiry');
        _x('Bahamas', 'country name', 'maneli-car-inquiry');
        _x('Bahrain', 'country name', 'maneli-car-inquiry');
        _x('Bangladesh', 'country name', 'maneli-car-inquiry');
        _x('Barbados', 'country name', 'maneli-car-inquiry');
        _x('Belarus', 'country name', 'maneli-car-inquiry');
        _x('Belgium', 'country name', 'maneli-car-inquiry');
        _x('Belize', 'country name', 'maneli-car-inquiry');
        _x('Benin', 'country name', 'maneli-car-inquiry');
        _x('Bermuda', 'country name', 'maneli-car-inquiry');
        _x('Bhutan', 'country name', 'maneli-car-inquiry');
        _x('Bolivia', 'country name', 'maneli-car-inquiry');
        _x('Bonaire, Sint Eustatius and Saba', 'country name', 'maneli-car-inquiry');
        _x('Bosnia and Herzegovina', 'country name', 'maneli-car-inquiry');
        _x('Botswana', 'country name', 'maneli-car-inquiry');
        _x('Bouvet Island', 'country name', 'maneli-car-inquiry');
        _x('Brazil', 'country name', 'maneli-car-inquiry');
        _x('British Indian Ocean Territory', 'country name', 'maneli-car-inquiry');
        _x('Brunei Darussalam', 'country name', 'maneli-car-inquiry');
        _x('Bulgaria', 'country name', 'maneli-car-inquiry');
        _x('Burkina Faso', 'country name', 'maneli-car-inquiry');
        _x('Burundi', 'country name', 'maneli-car-inquiry');
        _x('Cabo Verde', 'country name', 'maneli-car-inquiry');
        _x('Cambodia', 'country name', 'maneli-car-inquiry');
        _x('Cameroon', 'country name', 'maneli-car-inquiry');
        _x('Canada', 'country name', 'maneli-car-inquiry');
        _x('Cayman Islands', 'country name', 'maneli-car-inquiry');
        _x('Central African Republic', 'country name', 'maneli-car-inquiry');
        _x('Chad', 'country name', 'maneli-car-inquiry');
        _x('Chile', 'country name', 'maneli-car-inquiry');
        _x('China', 'country name', 'maneli-car-inquiry');
        _x('Christmas Island', 'country name', 'maneli-car-inquiry');
        _x('Cocos (Keeling) Islands', 'country name', 'maneli-car-inquiry');
        _x('Colombia', 'country name', 'maneli-car-inquiry');
        _x('Comoros', 'country name', 'maneli-car-inquiry');
        _x('Congo', 'country name', 'maneli-car-inquiry');
        _x('Congo, Democratic Republic of the', 'country name', 'maneli-car-inquiry');
        _x('Cook Islands', 'country name', 'maneli-car-inquiry');
        _x('Costa Rica', 'country name', 'maneli-car-inquiry');
        _x('Croatia', 'country name', 'maneli-car-inquiry');
        _x('Cuba', 'country name', 'maneli-car-inquiry');
        _x('Curaçao', 'country name', 'maneli-car-inquiry');
        _x('Cyprus', 'country name', 'maneli-car-inquiry');
        _x('Czechia', 'country name', 'maneli-car-inquiry');
        _x('Denmark', 'country name', 'maneli-car-inquiry');
        _x('Djibouti', 'country name', 'maneli-car-inquiry');
        _x('Dominica', 'country name', 'maneli-car-inquiry');
        _x('Dominican Republic', 'country name', 'maneli-car-inquiry');
        _x('Ecuador', 'country name', 'maneli-car-inquiry');
        _x('Egypt', 'country name', 'maneli-car-inquiry');
        _x('El Salvador', 'country name', 'maneli-car-inquiry');
        _x('Equatorial Guinea', 'country name', 'maneli-car-inquiry');
        _x('Eritrea', 'country name', 'maneli-car-inquiry');
        _x('Estonia', 'country name', 'maneli-car-inquiry');
        _x('Eswatini', 'country name', 'maneli-car-inquiry');
        _x('Ethiopia', 'country name', 'maneli-car-inquiry');
        _x('Falkland Islands', 'country name', 'maneli-car-inquiry');
        _x('Faroe Islands', 'country name', 'maneli-car-inquiry');
        _x('Fiji', 'country name', 'maneli-car-inquiry');
        _x('Finland', 'country name', 'maneli-car-inquiry');
        _x('France', 'country name', 'maneli-car-inquiry');
        _x('French Guiana', 'country name', 'maneli-car-inquiry');
        _x('French Polynesia', 'country name', 'maneli-car-inquiry');
        _x('French Southern Territories', 'country name', 'maneli-car-inquiry');
        _x('Gabon', 'country name', 'maneli-car-inquiry');
        _x('Gambia', 'country name', 'maneli-car-inquiry');
        _x('Georgia', 'country name', 'maneli-car-inquiry');
        _x('Germany', 'country name', 'maneli-car-inquiry');
        _x('Ghana', 'country name', 'maneli-car-inquiry');
        _x('Gibraltar', 'country name', 'maneli-car-inquiry');
        _x('Greece', 'country name', 'maneli-car-inquiry');
        _x('Greenland', 'country name', 'maneli-car-inquiry');
        _x('Grenada', 'country name', 'maneli-car-inquiry');
        _x('Guadeloupe', 'country name', 'maneli-car-inquiry');
        _x('Guam', 'country name', 'maneli-car-inquiry');
        _x('Guatemala', 'country name', 'maneli-car-inquiry');
        _x('Guernsey', 'country name', 'maneli-car-inquiry');
        _x('Guinea', 'country name', 'maneli-car-inquiry');
        _x('Guinea-Bissau', 'country name', 'maneli-car-inquiry');
        _x('Guyana', 'country name', 'maneli-car-inquiry');
        _x('Haiti', 'country name', 'maneli-car-inquiry');
        _x('Heard Island and McDonald Islands', 'country name', 'maneli-car-inquiry');
        _x('Holy See', 'country name', 'maneli-car-inquiry');
        _x('Honduras', 'country name', 'maneli-car-inquiry');
        _x('Hong Kong', 'country name', 'maneli-car-inquiry');
        _x('Hungary', 'country name', 'maneli-car-inquiry');
        _x('Iceland', 'country name', 'maneli-car-inquiry');
        _x('India', 'country name', 'maneli-car-inquiry');
        _x('Indonesia', 'country name', 'maneli-car-inquiry');
        _x('Iran', 'country name', 'maneli-car-inquiry');
        _x('Iraq', 'country name', 'maneli-car-inquiry');
        _x('Ireland', 'country name', 'maneli-car-inquiry');
        _x('Isle of Man', 'country name', 'maneli-car-inquiry');
        _x('Israel', 'country name', 'maneli-car-inquiry');
        _x('Italy', 'country name', 'maneli-car-inquiry');
        _x('Jamaica', 'country name', 'maneli-car-inquiry');
        _x('Japan', 'country name', 'maneli-car-inquiry');
        _x('Jersey', 'country name', 'maneli-car-inquiry');
        _x('Jordan', 'country name', 'maneli-car-inquiry');
        _x('Kazakhstan', 'country name', 'maneli-car-inquiry');
        _x('Kenya', 'country name', 'maneli-car-inquiry');
        _x('Kiribati', 'country name', 'maneli-car-inquiry');
        _x('Korea (North)', 'country name', 'maneli-car-inquiry');
        _x('Korea (South)', 'country name', 'maneli-car-inquiry');
        _x('Kuwait', 'country name', 'maneli-car-inquiry');
        _x('Kyrgyzstan', 'country name', 'maneli-car-inquiry');
        _x('Lao People’s Democratic Republic', 'country name', 'maneli-car-inquiry');
        _x('Latvia', 'country name', 'maneli-car-inquiry');
        _x('Lebanon', 'country name', 'maneli-car-inquiry');
        _x('Lesotho', 'country name', 'maneli-car-inquiry');
        _x('Liberia', 'country name', 'maneli-car-inquiry');
        _x('Libya', 'country name', 'maneli-car-inquiry');
        _x('Liechtenstein', 'country name', 'maneli-car-inquiry');
        _x('Lithuania', 'country name', 'maneli-car-inquiry');
        _x('Luxembourg', 'country name', 'maneli-car-inquiry');
        _x('Macao', 'country name', 'maneli-car-inquiry');
        _x('Madagascar', 'country name', 'maneli-car-inquiry');
        _x('Malawi', 'country name', 'maneli-car-inquiry');
        _x('Malaysia', 'country name', 'maneli-car-inquiry');
        _x('Maldives', 'country name', 'maneli-car-inquiry');
        _x('Mali', 'country name', 'maneli-car-inquiry');
        _x('Malta', 'country name', 'maneli-car-inquiry');
        _x('Marshall Islands', 'country name', 'maneli-car-inquiry');
        _x('Martinique', 'country name', 'maneli-car-inquiry');
        _x('Mauritania', 'country name', 'maneli-car-inquiry');
        _x('Mauritius', 'country name', 'maneli-car-inquiry');
        _x('Mayotte', 'country name', 'maneli-car-inquiry');
        _x('Mexico', 'country name', 'maneli-car-inquiry');
        _x('Micronesia', 'country name', 'maneli-car-inquiry');
        _x('Moldova', 'country name', 'maneli-car-inquiry');
        _x('Monaco', 'country name', 'maneli-car-inquiry');
        _x('Mongolia', 'country name', 'maneli-car-inquiry');
        _x('Montenegro', 'country name', 'maneli-car-inquiry');
        _x('Montserrat', 'country name', 'maneli-car-inquiry');
        _x('Morocco', 'country name', 'maneli-car-inquiry');
        _x('Mozambique', 'country name', 'maneli-car-inquiry');
        _x('Myanmar', 'country name', 'maneli-car-inquiry');
        _x('Namibia', 'country name', 'maneli-car-inquiry');
        _x('Nauru', 'country name', 'maneli-car-inquiry');
        _x('Nepal', 'country name', 'maneli-car-inquiry');
        _x('Netherlands', 'country name', 'maneli-car-inquiry');
        _x('New Caledonia', 'country name', 'maneli-car-inquiry');
        _x('New Zealand', 'country name', 'maneli-car-inquiry');
        _x('Nicaragua', 'country name', 'maneli-car-inquiry');
        _x('Niger', 'country name', 'maneli-car-inquiry');
        _x('Nigeria', 'country name', 'maneli-car-inquiry');
        _x('Niue', 'country name', 'maneli-car-inquiry');
        _x('Norfolk Island', 'country name', 'maneli-car-inquiry');
        _x('Northern Mariana Islands', 'country name', 'maneli-car-inquiry');
        _x('Norway', 'country name', 'maneli-car-inquiry');
        _x('Oman', 'country name', 'maneli-car-inquiry');
        _x('Pakistan', 'country name', 'maneli-car-inquiry');
        _x('Palau', 'country name', 'maneli-car-inquiry');
        _x('Palestine, State of', 'country name', 'maneli-car-inquiry');
        _x('Panama', 'country name', 'maneli-car-inquiry');
        _x('Papua New Guinea', 'country name', 'maneli-car-inquiry');
        _x('Paraguay', 'country name', 'maneli-car-inquiry');
        _x('Peru', 'country name', 'maneli-car-inquiry');
        _x('Philippines', 'country name', 'maneli-car-inquiry');
        _x('Pitcairn', 'country name', 'maneli-car-inquiry');
        _x('Poland', 'country name', 'maneli-car-inquiry');
        _x('Portugal', 'country name', 'maneli-car-inquiry');
        _x('Puerto Rico', 'country name', 'maneli-car-inquiry');
        _x('Qatar', 'country name', 'maneli-car-inquiry');
        _x('Réunion', 'country name', 'maneli-car-inquiry');
        _x('Romania', 'country name', 'maneli-car-inquiry');
        _x('Russia', 'country name', 'maneli-car-inquiry');
        _x('Rwanda', 'country name', 'maneli-car-inquiry');
        _x('Saint Barthélemy', 'country name', 'maneli-car-inquiry');
        _x('Saint Helena, Ascension and Tristan da Cunha', 'country name', 'maneli-car-inquiry');
        _x('Saint Kitts and Nevis', 'country name', 'maneli-car-inquiry');
        _x('Saint Lucia', 'country name', 'maneli-car-inquiry');
        _x('Saint Martin (French part)', 'country name', 'maneli-car-inquiry');
        _x('Saint Pierre and Miquelon', 'country name', 'maneli-car-inquiry');
        _x('Saint Vincent and the Grenadines', 'country name', 'maneli-car-inquiry');
        _x('Samoa', 'country name', 'maneli-car-inquiry');
        _x('San Marino', 'country name', 'maneli-car-inquiry');
        _x('Sao Tome and Principe', 'country name', 'maneli-car-inquiry');
        _x('Saudi Arabia', 'country name', 'maneli-car-inquiry');
        _x('Senegal', 'country name', 'maneli-car-inquiry');
        _x('Serbia', 'country name', 'maneli-car-inquiry');
        _x('Seychelles', 'country name', 'maneli-car-inquiry');
        _x('Sierra Leone', 'country name', 'maneli-car-inquiry');
        _x('Singapore', 'country name', 'maneli-car-inquiry');
        _x('Sint Maarten (Dutch part)', 'country name', 'maneli-car-inquiry');
        _x('Slovakia', 'country name', 'maneli-car-inquiry');
        _x('Slovenia', 'country name', 'maneli-car-inquiry');
        _x('Solomon Islands', 'country name', 'maneli-car-inquiry');
        _x('Somalia', 'country name', 'maneli-car-inquiry');
        _x('South Africa', 'country name', 'maneli-car-inquiry');
        _x('South Georgia and the South Sandwich Islands', 'country name', 'maneli-car-inquiry');
        _x('South Sudan', 'country name', 'maneli-car-inquiry');
        _x('Spain', 'country name', 'maneli-car-inquiry');
        _x('Sri Lanka', 'country name', 'maneli-car-inquiry');
        _x('Sudan', 'country name', 'maneli-car-inquiry');
        _x('Suriname', 'country name', 'maneli-car-inquiry');
        _x('Svalbard and Jan Mayen', 'country name', 'maneli-car-inquiry');
        _x('Sweden', 'country name', 'maneli-car-inquiry');
        _x('Switzerland', 'country name', 'maneli-car-inquiry');
        _x('Syrian Arab Republic', 'country name', 'maneli-car-inquiry');
        _x('Taiwan', 'country name', 'maneli-car-inquiry');
        _x('Tajikistan', 'country name', 'maneli-car-inquiry');
        _x('Tanzania, United Republic of', 'country name', 'maneli-car-inquiry');
        _x('Thailand', 'country name', 'maneli-car-inquiry');
        _x('Timor-Leste', 'country name', 'maneli-car-inquiry');
        _x('Togo', 'country name', 'maneli-car-inquiry');
        _x('Tokelau', 'country name', 'maneli-car-inquiry');
        _x('Tonga', 'country name', 'maneli-car-inquiry');
        _x('Trinidad and Tobago', 'country name', 'maneli-car-inquiry');
        _x('Tunisia', 'country name', 'maneli-car-inquiry');
        _x('Turkey', 'country name', 'maneli-car-inquiry');
        _x('Turkmenistan', 'country name', 'maneli-car-inquiry');
        _x('Turks and Caicos Islands', 'country name', 'maneli-car-inquiry');
        _x('Tuvalu', 'country name', 'maneli-car-inquiry');
        _x('Uganda', 'country name', 'maneli-car-inquiry');
        _x('Ukraine', 'country name', 'maneli-car-inquiry');
        _x('United Arab Emirates', 'country name', 'maneli-car-inquiry');
        _x('United Kingdom', 'country name', 'maneli-car-inquiry');
        _x('United States', 'country name', 'maneli-car-inquiry');
        _x('United States Minor Outlying Islands', 'country name', 'maneli-car-inquiry');
        _x('Uruguay', 'country name', 'maneli-car-inquiry');
        _x('Uzbekistan', 'country name', 'maneli-car-inquiry');
        _x('Vanuatu', 'country name', 'maneli-car-inquiry');
        _x('Venezuela', 'country name', 'maneli-car-inquiry');
        _x('Viet Nam', 'country name', 'maneli-car-inquiry');
        _x('Virgin Islands (British)', 'country name', 'maneli-car-inquiry');
        _x('Virgin Islands (U.S.)', 'country name', 'maneli-car-inquiry');
        _x('Wallis and Futuna', 'country name', 'maneli-car-inquiry');
        _x('Western Sahara', 'country name', 'maneli-car-inquiry');
        _x('Yemen', 'country name', 'maneli-car-inquiry');
        _x('Zambia', 'country name', 'maneli-car-inquiry');
        _x('Zimbabwe', 'country name', 'maneli-car-inquiry');
        _x('Kosovo', 'country name', 'maneli-car-inquiry');
    }
    
    /**
     * Localize country label based on active locale (Persian vs English)
     */
    private static function localize_country_label(array $names) {
        $use_persian_digits = function_exists('maneli_should_use_persian_digits') ? maneli_should_use_persian_digits() : true;
        $english_base = $names['en'] ?? '';
        $persian_base = $names['fa'] ?? $english_base;
        
        if ($use_persian_digits) {
            $label = $persian_base !== '' ? $persian_base : $english_base;
            if (function_exists('persian_numbers_no_separator')) {
                $label = persian_numbers_no_separator($label);
            }
            return esc_html($label);
        }
        
        $label = $english_base !== '' ? _x($english_base, 'country name', 'maneli-car-inquiry') : esc_html__('Unknown', 'maneli-car-inquiry');
        if (function_exists('maneli_convert_to_english_digits')) {
            $label = maneli_convert_to_english_digits($label);
        }
        return esc_html($label);
    }
    
    /**
     * Country definitions (ISO => [en, fa])
     */
    private static function get_country_definitions() {
        self::register_country_translation_strings();

        return [
            'LOC' => ['en' => 'Local', 'fa' => 'محلی'],
            'AF' => ['en' => 'Afghanistan', 'fa' => 'افغانستان'],
            'AX' => ['en' => 'Åland Islands', 'fa' => 'جزایر الند'],
            'AL' => ['en' => 'Albania', 'fa' => 'آلبانی'],
            'DZ' => ['en' => 'Algeria', 'fa' => 'الجزایر'],
            'AS' => ['en' => 'American Samoa', 'fa' => 'ساموآی آمریکا'],
            'AD' => ['en' => 'Andorra', 'fa' => 'آندورا'],
            'AO' => ['en' => 'Angola', 'fa' => 'آنگولا'],
            'AI' => ['en' => 'Anguilla', 'fa' => 'آنگویلا'],
            'AQ' => ['en' => 'Antarctica', 'fa' => 'جنوبگان'],
            'AG' => ['en' => 'Antigua and Barbuda', 'fa' => 'آنتیگوا و باربودا'],
            'AR' => ['en' => 'Argentina', 'fa' => 'آرژانتین'],
            'AM' => ['en' => 'Armenia', 'fa' => 'ارمنستان'],
            'AW' => ['en' => 'Aruba', 'fa' => 'آروبا'],
            'AU' => ['en' => 'Australia', 'fa' => 'استرالیا'],
            'AT' => ['en' => 'Austria', 'fa' => 'اتریش'],
            'AZ' => ['en' => 'Azerbaijan', 'fa' => 'جمهوری آذربایجان'],
            'BS' => ['en' => 'Bahamas', 'fa' => 'باهاما'],
            'BH' => ['en' => 'Bahrain', 'fa' => 'بحرین'],
            'BD' => ['en' => 'Bangladesh', 'fa' => 'بنگلادش'],
            'BB' => ['en' => 'Barbados', 'fa' => 'باربادوس'],
            'BY' => ['en' => 'Belarus', 'fa' => 'بلاروس'],
            'BE' => ['en' => 'Belgium', 'fa' => 'بلژیک'],
            'BZ' => ['en' => 'Belize', 'fa' => 'بلیز'],
            'BJ' => ['en' => 'Benin', 'fa' => 'بنین'],
            'BM' => ['en' => 'Bermuda', 'fa' => 'برمودا'],
            'BT' => ['en' => 'Bhutan', 'fa' => 'بوتان'],
            'BO' => ['en' => 'Bolivia', 'fa' => 'بولیوی'],
            'BQ' => ['en' => 'Bonaire, Sint Eustatius and Saba', 'fa' => 'بونیر، سینت یوستیشس و سابا'],
            'BA' => ['en' => 'Bosnia and Herzegovina', 'fa' => 'بوسنی و هرزگوین'],
            'BW' => ['en' => 'Botswana', 'fa' => 'بوتسوانا'],
            'BV' => ['en' => 'Bouvet Island', 'fa' => 'جزیره بووه'],
            'BR' => ['en' => 'Brazil', 'fa' => 'برزیل'],
            'IO' => ['en' => 'British Indian Ocean Territory', 'fa' => 'قلمرو بریتانیا در اقیانوس هند'],
            'BN' => ['en' => 'Brunei Darussalam', 'fa' => 'برونئی'],
            'BG' => ['en' => 'Bulgaria', 'fa' => 'بلغارستان'],
            'BF' => ['en' => 'Burkina Faso', 'fa' => 'بورکینافاسو'],
            'BI' => ['en' => 'Burundi', 'fa' => 'بوروندی'],
            'CV' => ['en' => 'Cabo Verde', 'fa' => 'کیپ ورد'],
            'KH' => ['en' => 'Cambodia', 'fa' => 'کامبوج'],
            'CM' => ['en' => 'Cameroon', 'fa' => 'کامرون'],
            'CA' => ['en' => 'Canada', 'fa' => 'کانادا'],
            'KY' => ['en' => 'Cayman Islands', 'fa' => 'جزایر کیمن'],
            'CF' => ['en' => 'Central African Republic', 'fa' => 'جمهوری آفریقای مرکزی'],
            'TD' => ['en' => 'Chad', 'fa' => 'چاد'],
            'CL' => ['en' => 'Chile', 'fa' => 'شیلی'],
            'CN' => ['en' => 'China', 'fa' => 'چین'],
            'CX' => ['en' => 'Christmas Island', 'fa' => 'جزیره کریسمس'],
            'CC' => ['en' => 'Cocos (Keeling) Islands', 'fa' => 'جزایر کوکوس'],
            'CO' => ['en' => 'Colombia', 'fa' => 'کلمبیا'],
            'KM' => ['en' => 'Comoros', 'fa' => 'کومور'],
            'CG' => ['en' => 'Congo', 'fa' => 'کنگو'],
            'CD' => ['en' => 'Congo, Democratic Republic of the', 'fa' => 'جمهوری دموکراتیک کنگو'],
            'CK' => ['en' => 'Cook Islands', 'fa' => 'جزایر کوک'],
            'CR' => ['en' => 'Costa Rica', 'fa' => 'کاستاریکا'],
            'CI' => ['en' => "Côte d'Ivoire", 'fa' => 'ساحل عاج'],
            'HR' => ['en' => 'Croatia', 'fa' => 'کرواسی'],
            'CU' => ['en' => 'Cuba', 'fa' => 'کوبا'],
            'CW' => ['en' => 'Curaçao', 'fa' => 'کوراسائو'],
            'CY' => ['en' => 'Cyprus', 'fa' => 'قبرس'],
            'CZ' => ['en' => 'Czechia', 'fa' => 'جمهوری چک'],
            'DK' => ['en' => 'Denmark', 'fa' => 'دانمارک'],
            'DJ' => ['en' => 'Djibouti', 'fa' => 'جیبوتی'],
            'DM' => ['en' => 'Dominica', 'fa' => 'دومنیکا'],
            'DO' => ['en' => 'Dominican Republic', 'fa' => 'جمهوری دومینیکن'],
            'EC' => ['en' => 'Ecuador', 'fa' => 'اکوادور'],
            'EG' => ['en' => 'Egypt', 'fa' => 'مصر'],
            'SV' => ['en' => 'El Salvador', 'fa' => 'السالوادور'],
            'GQ' => ['en' => 'Equatorial Guinea', 'fa' => 'گینه استوایی'],
            'ER' => ['en' => 'Eritrea', 'fa' => 'اریتره'],
            'EE' => ['en' => 'Estonia', 'fa' => 'استونی'],
            'SZ' => ['en' => 'Eswatini', 'fa' => 'اسواتینی'],
            'ET' => ['en' => 'Ethiopia', 'fa' => 'اتیوپی'],
            'FK' => ['en' => 'Falkland Islands', 'fa' => 'جزایر فالکلند'],
            'FO' => ['en' => 'Faroe Islands', 'fa' => 'جزایر فارو'],
            'FJ' => ['en' => 'Fiji', 'fa' => 'فیجی'],
            'FI' => ['en' => 'Finland', 'fa' => 'فنلاند'],
            'FR' => ['en' => 'France', 'fa' => 'فرانسه'],
            'GF' => ['en' => 'French Guiana', 'fa' => 'گویان فرانسه'],
            'PF' => ['en' => 'French Polynesia', 'fa' => 'پلی‌نزی فرانسه'],
            'TF' => ['en' => 'French Southern Territories', 'fa' => 'مناطق جنوبی فرانسه'],
            'GA' => ['en' => 'Gabon', 'fa' => 'گابن'],
            'GM' => ['en' => 'Gambia', 'fa' => 'گامبیا'],
            'GE' => ['en' => 'Georgia', 'fa' => 'گرجستان'],
            'DE' => ['en' => 'Germany', 'fa' => 'آلمان'],
            'GH' => ['en' => 'Ghana', 'fa' => 'غنا'],
            'GI' => ['en' => 'Gibraltar', 'fa' => 'جبل‌الطارق'],
            'GR' => ['en' => 'Greece', 'fa' => 'یونان'],
            'GL' => ['en' => 'Greenland', 'fa' => 'گرینلند'],
            'GD' => ['en' => 'Grenada', 'fa' => 'گرنادا'],
            'GP' => ['en' => 'Guadeloupe', 'fa' => 'گوادلوپ'],
            'GU' => ['en' => 'Guam', 'fa' => 'گوام'],
            'GT' => ['en' => 'Guatemala', 'fa' => 'گواتمالا'],
            'GG' => ['en' => 'Guernsey', 'fa' => 'گرنزی'],
            'GN' => ['en' => 'Guinea', 'fa' => 'گینه'],
            'GW' => ['en' => 'Guinea-Bissau', 'fa' => 'گینه بیسائو'],
            'GY' => ['en' => 'Guyana', 'fa' => 'گویان'],
            'HT' => ['en' => 'Haiti', 'fa' => 'هائیتی'],
            'HM' => ['en' => 'Heard Island and McDonald Islands', 'fa' => 'جزایر هرد و مک‌دونالد'],
            'VA' => ['en' => 'Holy See', 'fa' => 'واتیکان'],
            'HN' => ['en' => 'Honduras', 'fa' => 'هندوراس'],
            'HK' => ['en' => 'Hong Kong', 'fa' => 'هنگ کنگ'],
            'HU' => ['en' => 'Hungary', 'fa' => 'مجارستان'],
            'IS' => ['en' => 'Iceland', 'fa' => 'ایسلند'],
            'IN' => ['en' => 'India', 'fa' => 'هند'],
            'ID' => ['en' => 'Indonesia', 'fa' => 'اندونزی'],
            'IR' => ['en' => 'Iran', 'fa' => 'ایران'],
            'IQ' => ['en' => 'Iraq', 'fa' => 'عراق'],
            'IE' => ['en' => 'Ireland', 'fa' => 'ایرلند'],
            'IM' => ['en' => 'Isle of Man', 'fa' => 'جزیره من'],
            'IL' => ['en' => 'Israel', 'fa' => 'اسرائیل'],
            'IT' => ['en' => 'Italy', 'fa' => 'ایتالیا'],
            'JM' => ['en' => 'Jamaica', 'fa' => 'جامائیکا'],
            'JP' => ['en' => 'Japan', 'fa' => 'ژاپن'],
            'JE' => ['en' => 'Jersey', 'fa' => 'جرزی'],
            'JO' => ['en' => 'Jordan', 'fa' => 'اردن'],
            'KZ' => ['en' => 'Kazakhstan', 'fa' => 'قزاقستان'],
            'KE' => ['en' => 'Kenya', 'fa' => 'کنیا'],
            'KI' => ['en' => 'Kiribati', 'fa' => 'کیریباتی'],
            'KP' => ['en' => 'Korea (North)', 'fa' => 'کره شمالی'],
            'KR' => ['en' => 'Korea (South)', 'fa' => 'کره جنوبی'],
            'KW' => ['en' => 'Kuwait', 'fa' => 'کویت'],
            'KG' => ['en' => 'Kyrgyzstan', 'fa' => 'قرقیزستان'],
            'LA' => ['en' => 'Lao People’s Democratic Republic', 'fa' => 'لائوس'],
            'LV' => ['en' => 'Latvia', 'fa' => 'لتونی'],
            'LB' => ['en' => 'Lebanon', 'fa' => 'لبنان'],
            'LS' => ['en' => 'Lesotho', 'fa' => 'لسوتو'],
            'LR' => ['en' => 'Liberia', 'fa' => 'لیبریا'],
            'LY' => ['en' => 'Libya', 'fa' => 'لیبی'],
            'LI' => ['en' => 'Liechtenstein', 'fa' => 'لیختن‌اشتاین'],
            'LT' => ['en' => 'Lithuania', 'fa' => 'لیتوانی'],
            'LU' => ['en' => 'Luxembourg', 'fa' => 'لوکزامبورگ'],
            'MO' => ['en' => 'Macao', 'fa' => 'ماکائو'],
            'MG' => ['en' => 'Madagascar', 'fa' => 'ماداگاسکار'],
            'MW' => ['en' => 'Malawi', 'fa' => 'مالاوی'],
            'MY' => ['en' => 'Malaysia', 'fa' => 'مالزی'],
            'MV' => ['en' => 'Maldives', 'fa' => 'مالدیو'],
            'ML' => ['en' => 'Mali', 'fa' => 'مالی'],
            'MT' => ['en' => 'Malta', 'fa' => 'مالتا'],
            'MH' => ['en' => 'Marshall Islands', 'fa' => 'جزایر مارشال'],
            'MQ' => ['en' => 'Martinique', 'fa' => 'مارتینیک'],
            'MR' => ['en' => 'Mauritania', 'fa' => 'موریتانی'],
            'MU' => ['en' => 'Mauritius', 'fa' => 'موریس'],
            'YT' => ['en' => 'Mayotte', 'fa' => 'مایوت'],
            'MX' => ['en' => 'Mexico', 'fa' => 'مکزیک'],
            'FM' => ['en' => 'Micronesia', 'fa' => 'میکرونزی'],
            'MD' => ['en' => 'Moldova', 'fa' => 'مولداوی'],
            'MC' => ['en' => 'Monaco', 'fa' => 'موناکو'],
            'MN' => ['en' => 'Mongolia', 'fa' => 'مغولستان'],
            'ME' => ['en' => 'Montenegro', 'fa' => 'مونته‌نگرو'],
            'MS' => ['en' => 'Montserrat', 'fa' => 'مونتسرات'],
            'MA' => ['en' => 'Morocco', 'fa' => 'مراکش'],
            'MZ' => ['en' => 'Mozambique', 'fa' => 'موزامبیک'],
            'MM' => ['en' => 'Myanmar', 'fa' => 'میانمار'],
            'NA' => ['en' => 'Namibia', 'fa' => 'نامیبیا'],
            'NR' => ['en' => 'Nauru', 'fa' => 'نائورو'],
            'NP' => ['en' => 'Nepal', 'fa' => 'نپال'],
            'NL' => ['en' => 'Netherlands', 'fa' => 'هلند'],
            'NC' => ['en' => 'New Caledonia', 'fa' => 'کالدونیای جدید'],
            'NZ' => ['en' => 'New Zealand', 'fa' => 'نیوزیلند'],
            'NI' => ['en' => 'Nicaragua', 'fa' => 'نیکاراگوئه'],
            'NE' => ['en' => 'Niger', 'fa' => 'نیجر'],
            'NG' => ['en' => 'Nigeria', 'fa' => 'نیجریه'],
            'NU' => ['en' => 'Niue', 'fa' => 'نیووی'],
            'NF' => ['en' => 'Norfolk Island', 'fa' => 'جزیره نورفک'],
            'MP' => ['en' => 'Northern Mariana Islands', 'fa' => 'جزایر ماریانای شمالی'],
            'NO' => ['en' => 'Norway', 'fa' => 'نروژ'],
            'OM' => ['en' => 'Oman', 'fa' => 'عمان'],
            'PK' => ['en' => 'Pakistan', 'fa' => 'پاکستان'],
            'PW' => ['en' => 'Palau', 'fa' => 'پالائو'],
            'PS' => ['en' => 'Palestine, State of', 'fa' => 'فلسطین'],
            'PA' => ['en' => 'Panama', 'fa' => 'پاناما'],
            'PG' => ['en' => 'Papua New Guinea', 'fa' => 'پاپوا گینه نو'],
            'PY' => ['en' => 'Paraguay', 'fa' => 'پاراگوئه'],
            'PE' => ['en' => 'Peru', 'fa' => 'پرو'],
            'PH' => ['en' => 'Philippines', 'fa' => 'فیلیپین'],
            'PN' => ['en' => 'Pitcairn', 'fa' => 'پیتکرن'],
            'PL' => ['en' => 'Poland', 'fa' => 'لهستان'],
            'PT' => ['en' => 'Portugal', 'fa' => 'پرتغال'],
            'PR' => ['en' => 'Puerto Rico', 'fa' => 'پورتوریکو'],
            'QA' => ['en' => 'Qatar', 'fa' => 'قطر'],
            'RE' => ['en' => 'Réunion', 'fa' => 'رئونیون'],
            'RO' => ['en' => 'Romania', 'fa' => 'رومانی'],
            'RU' => ['en' => 'Russia', 'fa' => 'روسیه'],
            'RW' => ['en' => 'Rwanda', 'fa' => 'رواندا'],
            'BL' => ['en' => 'Saint Barthélemy', 'fa' => 'سن بارتلمی'],
            'SH' => ['en' => 'Saint Helena, Ascension and Tristan da Cunha', 'fa' => 'سنت هلنا، اسنشن و تریستان دا کونا'],
            'KN' => ['en' => 'Saint Kitts and Nevis', 'fa' => 'سنت کیتس و نویس'],
            'LC' => ['en' => 'Saint Lucia', 'fa' => 'سنت لوشیا'],
            'MF' => ['en' => 'Saint Martin (French part)', 'fa' => 'سنت مارتن (بخش فرانسوی)'],
            'PM' => ['en' => 'Saint Pierre and Miquelon', 'fa' => 'سن پیر و میکلون'],
            'VC' => ['en' => 'Saint Vincent and the Grenadines', 'fa' => 'سنت وینسنت و گرنادین'],
            'WS' => ['en' => 'Samoa', 'fa' => 'ساموآ'],
            'SM' => ['en' => 'San Marino', 'fa' => 'سن مارینو'],
            'ST' => ['en' => 'Sao Tome and Principe', 'fa' => 'سائوتومه و پرینسیپ'],
            'SA' => ['en' => 'Saudi Arabia', 'fa' => 'عربستان سعودی'],
            'SN' => ['en' => 'Senegal', 'fa' => 'سنگال'],
            'RS' => ['en' => 'Serbia', 'fa' => 'صربستان'],
            'SC' => ['en' => 'Seychelles', 'fa' => 'سیشل'],
            'SL' => ['en' => 'Sierra Leone', 'fa' => 'سیرالئون'],
            'SG' => ['en' => 'Singapore', 'fa' => 'سنگاپور'],
            'SX' => ['en' => 'Sint Maarten (Dutch part)', 'fa' => 'سینت مارتن (بخش هلندی)'],
            'SK' => ['en' => 'Slovakia', 'fa' => 'اسلواکی'],
            'SI' => ['en' => 'Slovenia', 'fa' => 'اسلوونی'],
            'SB' => ['en' => 'Solomon Islands', 'fa' => 'جزایر سلیمان'],
            'SO' => ['en' => 'Somalia', 'fa' => 'سومالی'],
            'ZA' => ['en' => 'South Africa', 'fa' => 'آفریقای جنوبی'],
            'GS' => ['en' => 'South Georgia and the South Sandwich Islands', 'fa' => 'جزایر جورجیا جنوبی و ساندویچ جنوبی'],
            'SS' => ['en' => 'South Sudan', 'fa' => 'سودان جنوبی'],
            'ES' => ['en' => 'Spain', 'fa' => 'اسپانیا'],
            'LK' => ['en' => 'Sri Lanka', 'fa' => 'سریلانکا'],
            'SD' => ['en' => 'Sudan', 'fa' => 'سودان'],
            'SR' => ['en' => 'Suriname', 'fa' => 'سورینام'],
            'SJ' => ['en' => 'Svalbard and Jan Mayen', 'fa' => 'اسوالبارد و یان ماین'],
            'SE' => ['en' => 'Sweden', 'fa' => 'سوئد'],
            'CH' => ['en' => 'Switzerland', 'fa' => 'سوئیس'],
            'SY' => ['en' => 'Syrian Arab Republic', 'fa' => 'سوریه'],
            'TW' => ['en' => 'Taiwan', 'fa' => 'تایوان'],
            'TJ' => ['en' => 'Tajikistan', 'fa' => 'تاجیکستان'],
            'TZ' => ['en' => 'Tanzania, United Republic of', 'fa' => 'تانزانیا'],
            'TH' => ['en' => 'Thailand', 'fa' => 'تایلند'],
            'TL' => ['en' => 'Timor-Leste', 'fa' => 'تیمور شرقی'],
            'TG' => ['en' => 'Togo', 'fa' => 'توگو'],
            'TK' => ['en' => 'Tokelau', 'fa' => 'توکلائو'],
            'TO' => ['en' => 'Tonga', 'fa' => 'تونگا'],
            'TT' => ['en' => 'Trinidad and Tobago', 'fa' => 'ترینیداد و توباگو'],
            'TN' => ['en' => 'Tunisia', 'fa' => 'تونس'],
            'TR' => ['en' => 'Turkey', 'fa' => 'ترکیه'],
            'TM' => ['en' => 'Turkmenistan', 'fa' => 'ترکمنستان'],
            'TC' => ['en' => 'Turks and Caicos Islands', 'fa' => 'جزایر تورکس و کایکوس'],
            'TV' => ['en' => 'Tuvalu', 'fa' => 'تووالو'],
            'UG' => ['en' => 'Uganda', 'fa' => 'اوگاندا'],
            'UA' => ['en' => 'Ukraine', 'fa' => 'اوکراین'],
            'AE' => ['en' => 'United Arab Emirates', 'fa' => 'امارات متحده عربی'],
            'GB' => ['en' => 'United Kingdom', 'fa' => 'بریتانیا'],
            'US' => ['en' => 'United States', 'fa' => 'ایالات متحده آمریکا'],
            'UM' => ['en' => 'United States Minor Outlying Islands', 'fa' => 'جزایر دورافتاده ایالات متحده'],
            'UY' => ['en' => 'Uruguay', 'fa' => 'اروگوئه'],
            'UZ' => ['en' => 'Uzbekistan', 'fa' => 'ازبکستان'],
            'VU' => ['en' => 'Vanuatu', 'fa' => 'وانواتو'],
            'VE' => ['en' => 'Venezuela', 'fa' => 'ونزوئلا'],
            'VN' => ['en' => 'Viet Nam', 'fa' => 'ویتنام'],
            'VG' => ['en' => 'Virgin Islands (British)', 'fa' => 'جزایر ویرجین بریتانیا'],
            'VI' => ['en' => 'Virgin Islands (U.S.)', 'fa' => 'جزایر ویرجین آمریکا'],
            'WF' => ['en' => 'Wallis and Futuna', 'fa' => 'والیس و فوتونا'],
            'EH' => ['en' => 'Western Sahara', 'fa' => 'صحرای غربی'],
            'YE' => ['en' => 'Yemen', 'fa' => 'یمن'],
            'ZM' => ['en' => 'Zambia', 'fa' => 'زامبیا'],
            'ZW' => ['en' => 'Zimbabwe', 'fa' => 'زیمبابوه'],
            'XK' => ['en' => 'Kosovo', 'fa' => 'کوزوو'],
        ];
        
        foreach ($countries as $code => $names) {
            $localized = self::localize_country_label($names);
            $en = $names['en'];
            $upper_code = strtoupper($code);
            $map[$upper_code] = $localized;
            $map[strtolower($upper_code)] = $localized;
            $map[strtolower($en)] = $localized;
            $map[$en] = $localized;
        }
        
        $unknown_label = self::localize_country_label([
            'en' => esc_html__('Unknown', 'maneli-car-inquiry'),
            'fa' => esc_html__('نامشخص', 'maneli-car-inquiry'),
        ]);
        $map['unknown'] = $unknown_label;
        $map['UNKNOWN'] = $unknown_label;
        
        return $map;
    }
    
    /**
     * Return CSS class for country flag
     */
    public static function get_country_flag_icon($country_code, $fallback = '') {
        $entry = self::resolve_country_entry($country_code, $fallback);
        if ($entry && !empty($entry['code'])) {
            $code = strtoupper($entry['code']);
            if ($code === 'LOC') {
                return '🏠';
            }
            if ($code === 'UNKNOWN') {
                return '🌐';
            }
            if (strlen($code) === 2 && ctype_alpha($code)) {
                return self::convert_country_code_to_flag($code);
            }
        }
        return '🌐';
    }

    /**
     * Convert ISO country code (2 letters) to Unicode flag emoji.
     */
    private static function convert_country_code_to_flag($code) {
        $code = strtoupper($code);
        if (strlen($code) !== 2) {
            return '🌐';
        }
        $offset = 127397; // 0x1F1E6 - ord('A')
        $first = ord($code[0]);
        $second = ord($code[1]);
        if ($first < 65 || $first > 90 || $second < 65 || $second > 90) {
            return '🌐';
        }
        $flag = mb_convert_encoding('&#' . ($offset + $first) . ';', 'UTF-8', 'HTML-ENTITIES');
        $flag .= mb_convert_encoding('&#' . ($offset + $second) . ';', 'UTF-8', 'HTML-ENTITIES');
        return $flag;
    }
    
    /**
     * Format time difference into localized "ago" string
     */
    private static function format_time_ago($datetime, $current_timestamp) {
        if (empty($datetime)) {
            return '';
        }
        
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '';
        }
        
        $diff_seconds = abs($current_timestamp - $timestamp);
        if ($diff_seconds < 5) {
            return esc_html__('Moments ago', 'maneli-car-inquiry');
        }
        
        $diff_text = human_time_diff($timestamp, $current_timestamp);
        $use_persian_digits = function_exists('maneli_should_use_persian_digits') ? maneli_should_use_persian_digits() : true;

        if (!$use_persian_digits) {
            if (function_exists('maneli_convert_to_english_digits')) {
                $diff_text = maneli_convert_to_english_digits($diff_text);
            }
            return trim($diff_text . ' ' . esc_html__('ago', 'maneli-car-inquiry'));
        }
        
        $replacements = [
            ' mins'   => ' دقیقه',
            ' min'    => ' دقیقه',
            ' minutes' => ' دقیقه',
            ' minute'  => ' دقیقه',
            ' hours'  => ' ساعت',
            ' hour'   => ' ساعت',
            ' days'   => ' روز',
            ' day'    => ' روز',
            ' weeks'  => ' هفته',
            ' week'   => ' هفته',
            ' months' => ' ماه',
            ' month'  => ' ماه',
            ' years'  => ' سال',
            ' year'   => ' سال',
            ' secs'   => ' ثانیه',
            ' sec'    => ' ثانیه',
            ' seconds'=> ' ثانیه',
            ' second' => ' ثانیه',
        ];
        
        $translated = strtr(' ' . $diff_text, $replacements);
        $translated = trim($translated);
        
        if (function_exists('persian_numbers')) {
            $translated = persian_numbers($translated);
        }
        
        return trim($translated . ' ' . esc_html__('ago', 'maneli-car-inquiry'));
    }
}


