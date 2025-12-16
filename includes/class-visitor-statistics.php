<?php
/**
 * Visitor Statistics Class
 * مدیریت آمار بازدیدکنندگان و ردیابی بازدیدها
 * 
 * @package Autopuzzle_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Visitor_Statistics {
    
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
     * Parse user agent using accurate detection service
     * Falls back to local parsing if API fails
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
        
        // Try to use accurate detection service first
        $accurate_result = self::parse_user_agent_accurate($user_agent);
        if ($accurate_result && $accurate_result['browser'] !== 'Unknown') {
            return $accurate_result;
        }
        
        // Fallback to local parsing
        return self::parse_user_agent_local($user_agent);
    }
    
    /**
     * Parse user agent using accurate detection (API or library)
     */
    private static function parse_user_agent_accurate($user_agent) {
        // NOTE: UserAgentAPI.com API is unreliable and may return 404
        // Skipping external API calls for reliability and privacy
        // Using local parsing instead which is sufficiently accurate
        return null; // Force local parsing
        
        /* Disabled external API due to reliability issues
        // Use UserAgentAPI.com for accurate detection (free and accurate)
        $api_url = 'http://useragentapi.com/api/v3/json/' . urlencode($user_agent);
        
        // Try to get cached result first
        $cache_key = 'autopuzzle_ua_' . md5($user_agent);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        // Make API request with timeout
        $response = wp_remote_get($api_url, [
            'timeout' => 3,
            'sslverify' => false // HTTP not HTTPS
        ]);
        
        if (is_wp_error($response)) {
            // API failed, return null to use local parsing
            return null;
        }
        */
    }
    
    /**
     * Parse user agent locally (fallback method)
     */
    private static function parse_user_agent_local($user_agent) {
        $result = [
            'browser' => 'Unknown',
            'browser_version' => '',
            'os' => 'Unknown',
            'os_version' => '',
            'device_type' => 'desktop',
            'device_model' => ''
        ];
        $result['normalized_device_model'] = '';
        
        // Detect OS - Check mobile OS first (Android/iOS) before desktop OS
        // This prevents false detection of Linux when it's actually Android
        if (preg_match('/android/i', $user_agent)) {
            $result['os'] = 'Android';
            $result['device_type'] = 'mobile';
            if (preg_match('/android ([\d.]+)/i', $user_agent, $matches)) {
                $result['os_version'] = $matches[1];
            }
            // Try to extract specific device model from user agent
            $device_model = '';
            
            // Samsung Galaxy models
            if (preg_match('/samsung[-\s]?(?:galaxy[-\s])?([a-z0-9\s]+)/i', $user_agent, $matches)) {
                $model = trim($matches[1]);
                if (!empty($model) && strlen($model) > 2) {
                    $device_model = 'Samsung Galaxy ' . $model;
                }
            }
            // Xiaomi/Redmi models
            elseif (preg_match('/(?:xiaomi|redmi)[-\s]+([a-z0-9\s]+)/i', $user_agent, $matches)) {
                $model = trim($matches[1]);
                if (!empty($model) && strlen($model) > 2) {
                    $device_model = 'Xiaomi ' . $model;
                }
            }
            // Huawei models
            elseif (preg_match('/huawei[-\s]+([a-z0-9\s]+)/i', $user_agent, $matches)) {
                $model = trim($matches[1]);
                if (!empty($model) && strlen($model) > 2) {
                    $device_model = 'Huawei ' . $model;
                }
            }
            // OnePlus models
            elseif (preg_match('/oneplus[-\s]+([a-z0-9\s]+)/i', $user_agent, $matches)) {
                $model = trim($matches[1]);
                if (!empty($model) && strlen($model) > 2) {
                    $device_model = 'OnePlus ' . $model;
                }
            }
            // OPPO models
            elseif (preg_match('/oppo[-\s]+([a-z0-9\s]+)/i', $user_agent, $matches)) {
                $model = trim($matches[1]);
                if (!empty($model) && strlen($model) > 2) {
                    $device_model = 'OPPO ' . $model;
                }
            }
            // Vivo models
            elseif (preg_match('/vivo[-\s]+([a-z0-9\s]+)/i', $user_agent, $matches)) {
                $model = trim($matches[1]);
                if (!empty($model) && strlen($model) > 2) {
                    $device_model = 'Vivo ' . $model;
                }
            }
            // Realme models
            elseif (preg_match('/realme[-\s]+([a-z0-9\s]+)/i', $user_agent, $matches)) {
                $model = trim($matches[1]);
                if (!empty($model) && strlen($model) > 2) {
                    $device_model = 'Realme ' . $model;
                }
            }
            // Google Pixel models
            elseif (preg_match('/pixel[-\s]*([a-z0-9\s]+)/i', $user_agent, $matches)) {
                $model = trim($matches[1]);
                if (!empty($model) && strlen($model) > 1) {
                    $device_model = 'Google Pixel ' . $model;
                } else {
                    $device_model = 'Google Pixel';
                }
            }
            // Generic brand detection (fallback - but we'll filter this out in display)
            elseif (preg_match('/(samsung|huawei|xiaomi|oneplus|oppo|vivo|realme|motorola|lg|sony|htc|nokia|asus|lenovo|zte|honor)/i', $user_agent, $matches)) {
                $brand = ucfirst(strtolower($matches[1]));
                $device_model = $brand; // At least we have the brand
            }
            
            if (!empty($device_model)) {
                $result['device_model'] = $device_model;
                $result['normalized_device_model'] = $device_model;
            } else {
                // Don't set to "Android Device" - leave empty so it can be filtered out
                $result['normalized_device_model'] = '';
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
        // Desktop OS detection (after mobile OS)
        elseif (preg_match('/windows nt 10/i', $user_agent)) {
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
            // Only set macOS if it's not a mobile device
            // Mobile Safari on iOS devices should not be detected as macOS
            if (!preg_match('/mobile|iphone|ipad|ipod/i', $user_agent)) {
                $result['os'] = 'macOS';
                if (preg_match('/mac os x (\d+)[._](\d+)/i', $user_agent, $matches)) {
                    $result['os_version'] = $matches[1] . '.' . $matches[2];
                }
                $result['normalized_device_model'] = 'Desktop';
            } else {
                // If mobile keywords found, it's likely iOS (even if macOS is mentioned)
                $result['os'] = 'iOS';
                $result['device_type'] = preg_match('/ipad/i', $user_agent) ? 'tablet' : 'mobile';
                if (preg_match('/iphone|ipod/i', $user_agent)) {
                    $result['device_model'] = 'iPhone';
                    $result['normalized_device_model'] = 'iPhone';
                } elseif (preg_match('/ipad/i', $user_agent)) {
                    $result['device_model'] = 'iPad';
                    $result['normalized_device_model'] = 'iPad';
                }
                if (preg_match('/os ([\d_]+)/i', $user_agent, $matches)) {
                    $result['os_version'] = str_replace('_', '.', $matches[1]);
                }
            }
        } elseif (preg_match('/linux/i', $user_agent)) {
            // Only set Linux if it's not a mobile device
            // Mobile browsers often include "Linux" in user agent but are actually Android
            if (!preg_match('/mobile|android|iphone|ipad|ipod/i', $user_agent)) {
                $result['os'] = 'Linux';
                $result['normalized_device_model'] = 'Desktop';
            } else {
                // If mobile keywords found, it's likely Android (even if Linux is mentioned)
                $result['os'] = 'Android';
                $result['device_type'] = 'mobile';
            }
        }
        
        // Detect Browser
        // Instagram in-app browser
        if (preg_match('/instagram/i', $user_agent)) {
            $result['browser'] = 'Instagram';
        }
        // Edge
        elseif (preg_match('/edg\/([\d.]+)/i', $user_agent, $matches)) {
            $result['browser'] = 'Edge';
            $result['browser_version'] = $matches[1];
        }
        // Chrome Mobile - can be on Android or iOS
        elseif (preg_match('/chrome\/([\d.]+)/i', $user_agent, $matches) && preg_match('/mobile/i', $user_agent)) {
            $result['browser'] = 'Chrome Mobile';
            $result['browser_version'] = $matches[1];
            // Ensure OS is correct: iOS if iPhone/iPad detected, Android otherwise
            if (!in_array($result['os'], ['Android', 'iOS'])) {
                // Check for iOS first (iPhone/iPad/iPod)
                if (preg_match('/iphone|ipad|ipod/i', $user_agent)) {
                    $result['os'] = 'iOS';
                    $result['device_type'] = preg_match('/ipad/i', $user_agent) ? 'tablet' : 'mobile';
                    if (preg_match('/iphone|ipod/i', $user_agent)) {
                        $result['device_model'] = 'iPhone';
                        $result['normalized_device_model'] = 'iPhone';
                    } elseif (preg_match('/ipad/i', $user_agent)) {
                        $result['device_model'] = 'iPad';
                        $result['normalized_device_model'] = 'iPad';
                    }
                    if (preg_match('/os ([\d_]+)/i', $user_agent, $os_matches)) {
                        $result['os_version'] = str_replace('_', '.', $os_matches[1]);
                    }
                } elseif (stripos($user_agent, 'android') !== false || (stripos($user_agent, 'linux') !== false && stripos($user_agent, 'mobile') !== false)) {
                    // Android detected
                    $result['os'] = 'Android';
                    $result['device_type'] = 'mobile';
                    if (preg_match('/android ([\d.]+)/i', $user_agent, $os_matches)) {
                        $result['os_version'] = $os_matches[1];
                    }
                }
            } else {
                // OS already detected, but make sure it's correct
                // If iOS was detected but user agent has Android, keep iOS (iOS takes priority)
                // If Android was detected but user agent has iPhone/iPad, change to iOS
                if ($result['os'] === 'Android' && preg_match('/iphone|ipad|ipod/i', $user_agent)) {
                    $result['os'] = 'iOS';
                    $result['device_type'] = preg_match('/ipad/i', $user_agent) ? 'tablet' : 'mobile';
                    if (preg_match('/iphone|ipod/i', $user_agent)) {
                        $result['device_model'] = 'iPhone';
                        $result['normalized_device_model'] = 'iPhone';
                    } elseif (preg_match('/ipad/i', $user_agent)) {
                        $result['device_model'] = 'iPad';
                        $result['normalized_device_model'] = 'iPad';
                    }
                    if (preg_match('/os ([\d_]+)/i', $user_agent, $os_matches)) {
                        $result['os_version'] = str_replace('_', '.', $os_matches[1]);
                    }
                }
            }
        }
        // Chrome
        elseif (preg_match('/chrome\/([\d.]+)/i', $user_agent, $matches)) {
            $result['browser'] = 'Chrome';
            $result['browser_version'] = $matches[1];
        }
        // Mobile Safari - must be on iOS device (iPhone/iPad/iPod)
        elseif (preg_match('/safari\/([\d.]+)/i', $user_agent, $matches) && !preg_match('/chrome/i', $user_agent) && preg_match('/iphone|ipod|ipad/i', $user_agent)) {
            $result['browser'] = 'Mobile Safari';
            $result['browser_version'] = $matches[1];
            // Ensure OS is iOS if Mobile Safari is detected
            if (!in_array($result['os'], ['iOS'])) {
                $result['os'] = 'iOS';
                $result['device_type'] = preg_match('/ipad/i', $user_agent) ? 'tablet' : 'mobile';
                if (preg_match('/iphone|ipod/i', $user_agent)) {
                    $result['device_model'] = 'iPhone';
                    $result['normalized_device_model'] = 'iPhone';
                } elseif (preg_match('/ipad/i', $user_agent)) {
                    $result['device_model'] = 'iPad';
                    $result['normalized_device_model'] = 'iPad';
                }
                if (preg_match('/os ([\d_]+)/i', $user_agent, $matches)) {
                    $result['os_version'] = str_replace('_', '.', $matches[1]);
                }
            }
        }
        // Safari
        elseif (preg_match('/safari\/([\d.]+)/i', $user_agent, $matches) && !preg_match('/chrome/i', $user_agent)) {
            $result['browser'] = 'Safari';
            $result['browser_version'] = $matches[1];
        }
        // Firefox
        elseif (preg_match('/firefox\/([\d.]+)/i', $user_agent, $matches)) {
            $result['browser'] = 'Firefox';
            $result['browser_version'] = $matches[1];
        }
        // IE
        elseif (preg_match('/msie|trident/i', $user_agent)) {
            $result['browser'] = 'IE';
            if (preg_match('/msie ([\d.]+)/i', $user_agent, $matches)) {
                $result['browser_version'] = $matches[1];
            }
        }
        // Opera
        elseif (preg_match('/opera|opr\//i', $user_agent, $matches)) {
            $result['browser'] = 'Opera';
            if (preg_match('/(?:opera|opr)\/([\d.]+)/i', $user_agent, $matches)) {
                $result['browser_version'] = $matches[1];
            }
        }
        
        // Fallback OS detection - Check mobile first
        if ($result['os'] === 'Unknown') {
            if (stripos($user_agent, 'android') !== false) {
                $result['os'] = 'Android';
                $result['device_type'] = 'mobile';
                $result['normalized_device_model'] = '';
            } elseif (stripos($user_agent, 'iphone') !== false || stripos($user_agent, 'ipod') !== false) {
                $result['os'] = 'iOS';
                $result['device_type'] = 'mobile';
                $result['normalized_device_model'] = 'iPhone';
            } elseif (stripos($user_agent, 'ipad') !== false) {
                $result['os'] = 'iOS';
                $result['device_type'] = 'tablet';
                $result['normalized_device_model'] = 'iPad';
            } elseif (stripos($user_agent, 'windows') !== false) {
                $result['os'] = 'Windows';
                $result['normalized_device_model'] = 'Desktop';
            } elseif (stripos($user_agent, 'macintosh') !== false || stripos($user_agent, 'mac os') !== false) {
                $result['os'] = 'macOS';
                $result['normalized_device_model'] = 'Desktop';
            } elseif (stripos($user_agent, 'linux') !== false || stripos($user_agent, 'x11') !== false) {
                // Only set Linux if no mobile indicators
                if (stripos($user_agent, 'mobile') === false && stripos($user_agent, 'android') === false) {
                    $result['os'] = 'Linux';
                    $result['normalized_device_model'] = 'Desktop';
                } else {
                    // Likely Android
                    $result['os'] = 'Android';
                    $result['device_type'] = 'mobile';
                }
            }
        }
        
        // Post-process: If browser is mobile but OS is desktop, correct it
        if (in_array($result['browser'], ['Chrome Mobile', 'Mobile Safari', 'Instagram']) && 
            in_array($result['os'], ['Linux', 'Windows', 'macOS'])) {
            // Mobile browser detected but desktop OS - likely Android or iOS
            // Check for iOS first (takes priority)
            if (stripos($user_agent, 'iphone') !== false || stripos($user_agent, 'ipad') !== false || stripos($user_agent, 'ipod') !== false) {
                $result['os'] = 'iOS';
                $result['device_type'] = stripos($user_agent, 'ipad') !== false ? 'tablet' : 'mobile';
                if (stripos($user_agent, 'iphone') !== false || stripos($user_agent, 'ipod') !== false) {
                    $result['device_model'] = 'iPhone';
                    $result['normalized_device_model'] = 'iPhone';
                } elseif (stripos($user_agent, 'ipad') !== false) {
                    $result['device_model'] = 'iPad';
                    $result['normalized_device_model'] = 'iPad';
                }
                if (preg_match('/os ([\d_]+)/i', $user_agent, $os_matches)) {
                    $result['os_version'] = str_replace('_', '.', $os_matches[1]);
                }
            } elseif (stripos($user_agent, 'android') !== false || (stripos($user_agent, 'linux') !== false && stripos($user_agent, 'mobile') !== false)) {
                $result['os'] = 'Android';
                $result['device_type'] = 'mobile';
                if (preg_match('/android ([\d.]+)/i', $user_agent, $os_matches)) {
                    $result['os_version'] = $os_matches[1];
                }
            }
        }

        // Fallback browser detection
        if ($result['browser'] === 'Unknown') {
            if (stripos($user_agent, 'instagram') !== false) {
                $result['browser'] = 'Instagram';
            } elseif (stripos($user_agent, 'edg') !== false || stripos($user_agent, 'edge') !== false) {
                $result['browser'] = 'Edge';
            } elseif (stripos($user_agent, 'opr') !== false || stripos($user_agent, 'opera') !== false) {
                $result['browser'] = 'Opera';
            } elseif ((stripos($user_agent, 'chrome') !== false || stripos($user_agent, 'crios') !== false) && stripos($user_agent, 'chromium') === false) {
                if (stripos($user_agent, 'mobile') !== false || stripos($user_agent, 'android') !== false) {
                    $result['browser'] = 'Chrome Mobile';
                } else {
                    $result['browser'] = 'Chrome';
                }
            } elseif (stripos($user_agent, 'safari') !== false && stripos($user_agent, 'chrome') === false) {
                if (stripos($user_agent, 'mobile') !== false || stripos($user_agent, 'iphone') !== false || stripos($user_agent, 'ipod') !== false || stripos($user_agent, 'ipad') !== false) {
                    $result['browser'] = 'Mobile Safari';
                } else {
                    $result['browser'] = 'Safari';
                }
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
        $geoip_enabled = apply_filters('autopuzzle_enable_geoip_lookup', true, $ip);
        if (!$geoip_enabled) {
            return $default_location;
        }

        if (self::is_private_ip($ip)) {
            return ['country' => 'Local', 'country_code' => 'LOC'];
        }

        $transient_key = 'autopuzzle_geoip_' . md5($ip);
        $cached_value = get_transient($transient_key);
        if ($cached_value !== false && is_array($cached_value)) {
            return $cached_value;
        }

        $geoip_endpoint = apply_filters(
            'autopuzzle_geoip_endpoint',
            sprintf('https://ipwho.is/%s?output=json', rawurlencode($ip)),
            $ip
        );

        $request_args = apply_filters(
            'autopuzzle_geoip_request_args',
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
        
        // Get current page if not provided
        if ($page_url === null) {
            $page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
        
        // Skip dashboard and admin pages
        if (strpos($page_url, '/dashboard/') !== false || 
            strpos($page_url, '/wp-admin/') !== false || 
            strpos($page_url, '/wp-login.php') !== false ||
            strpos($page_url, '/admin/') !== false) {
            return false;
        }
        
        // Skip static files (JS, CSS, images, fonts, maps, etc.)
        $static_extensions = ['.js', '.css', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot', '.map', '.json', '.xml', '.pdf', '.zip', '.rar'];
        $page_url_lower = strtolower($page_url);
        foreach ($static_extensions as $ext) {
            if (strpos($page_url_lower, $ext) !== false) {
                return false;
            }
        }
        
        // Skip WordPress content directories
        if (strpos($page_url, '/wp-content/') !== false || 
            strpos($page_url, '/wp-includes/') !== false) {
            return false;
        }
        
        // Skip 404 pages
        if (strpos($page_url, '404') !== false || 
            strpos($page_title, '404') !== false ||
            strpos($page_title, 'برگه پیدا نشد') !== false ||
            strpos($page_title, 'صفحه پیدا نشد') !== false) {
            return false;
        }
        
        // Skip internal/dashboard pages by title
        if ($page_title && (
            strpos($page_title, 'داشبورد') !== false ||
            strpos($page_title, 'Dashboard') !== false ||
            strpos($page_title, 'مدیریتی') !== false
        )) {
            return false;
        }
        
        // Get visitor info
        $ip = self::get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Check if bot
        if (self::is_bot($user_agent)) {
            return false;
        }
        
        // Skip admin IPs (optional)
        $admin_ips = apply_filters('autopuzzle_skip_tracking_ips', ['127.0.0.1', '::1']);
        if (in_array($ip, $admin_ips)) {
            return false;
        }
        
        // Rate limiting: Check if this page was already tracked in this session (within last 30 seconds)
        $session_id = self::get_session_id();
        $rate_limit_key = 'autopuzzle_track_' . md5($session_id . $page_url);
        if (get_transient($rate_limit_key)) {
            return false; // Already tracked this page in this session recently
        }
        set_transient($rate_limit_key, true, 30); // 30 seconds rate limit per page per session
        
        // Parse user agent
        $ua_info = self::parse_user_agent($user_agent);
        
        // Get country
        $country_info = self::get_country_from_ip($ip);
        
        // Parse search engine
        $search_info = self::parse_search_engine($referrer);
        
        // Get or create visitor
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
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
        
        // Check if this exact visit was already recorded in the last 30 seconds (duplicate prevention)
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $recent_visit = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $visits_table 
            WHERE visitor_id = %d 
            AND page_url = %s 
            AND session_id = %s 
            AND visit_date > DATE_SUB(NOW(), INTERVAL 30 SECOND)
            LIMIT 1",
            $visitor_id,
            $page_url,
            $session_id
        ));
        
        if ($recent_visit) {
            return false; // Duplicate visit within 30 seconds - skip
        }
        
        // Record visit
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
        if (!isset($_SESSION['autopuzzle_session_id'])) {
            $_SESSION['autopuzzle_session_id'] = wp_generate_password(32, false);
        }
        return $_SESSION['autopuzzle_session_id'];
    }
    
    /**
     * Update page statistics
     */
    private static function update_page_stats($page_url, $page_title) {
        global $wpdb;
        $pages_table = $wpdb->prefix . 'autopuzzle_pages';
        
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
        $search_table = $wpdb->prefix . 'autopuzzle_search_engines';
        
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
        $referrers_table = $wpdb->prefix . 'autopuzzle_referrers';
        
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
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
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
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
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
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
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
            AND v.page_url NOT LIKE %s
            AND v.page_url NOT LIKE %s
            AND v.page_url NOT LIKE %s
            AND v.page_url NOT LIKE %s
            GROUP BY v.page_url, v.page_title
            ORDER BY visit_count DESC
            LIMIT %d",
            $start_date,
            $end_date,
            '%/dashboard/%',
            '%/wp-admin/%',
            '%/wp-login.php%',
            '%/admin/%',
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
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
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
            AND v.product_id > 0
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
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
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
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
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
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
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
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
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
     * Get combined device models and operating systems statistics
     * Returns both device models and OS stats combined
     */
    public static function get_combined_device_and_os_stats($limit = 20, $start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
        // Get device models
        $device_models = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                vis.device_model as model_name,
                'device' as type,
                COUNT(DISTINCT v.id) as visit_count,
                COUNT(DISTINCT v.visitor_id) as unique_visitors
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            AND vis.device_model IS NOT NULL
            AND vis.device_model != ''
            GROUP BY vis.device_model",
            $start_date,
            $end_date
        ));
        
        // Get operating systems
        $operating_systems = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                vis.os as model_name,
                'os' as type,
                COUNT(DISTINCT v.id) as visit_count,
                COUNT(DISTINCT v.visitor_id) as unique_visitors
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            AND vis.os IS NOT NULL
            AND vis.os != ''
            GROUP BY vis.os",
            $start_date,
            $end_date
        ));
        
        // Combine results
        $combined = [];
        
        // Add device models
        foreach ($device_models as $model) {
            $combined[] = (object) [
                'model_name' => $model->model_name,
                'type' => 'device',
                'visit_count' => (int) $model->visit_count,
                'unique_visitors' => (int) $model->unique_visitors,
            ];
        }
        
        // Add operating systems
        foreach ($operating_systems as $os) {
            $combined[] = (object) [
                'model_name' => $os->model_name,
                'type' => 'os',
                'visit_count' => (int) $os->visit_count,
                'unique_visitors' => (int) $os->unique_visitors,
            ];
        }
        
        // Sort by visit_count descending
        usort($combined, function($a, $b) {
            return $b->visit_count - $a->visit_count;
        });
        
        // Limit results
        return array_slice($combined, 0, $limit);
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
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
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
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
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
        
        $referrers_table = $wpdb->prefix . 'autopuzzle_referrers';
        
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
     * Get recent visitors with complete information
     */
    public static function get_recent_visitors($limit = 50, $start_date = null, $end_date = null) {
        global $wpdb;
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
        $where_clause = "WHERE vis.is_bot = 0";
        $params = [];
        
        // Filter out static files, dashboard, admin, and 404 pages
        $where_clause .= " AND v.page_url NOT LIKE %s"; // Dashboard
        $params[] = '%/dashboard/%';
        $where_clause .= " AND v.page_url NOT LIKE %s"; // WP Admin
        $params[] = '%/wp-admin/%';
        $where_clause .= " AND v.page_url NOT LIKE %s"; // WP Login
        $params[] = '%/wp-login.php%';
        $where_clause .= " AND v.page_url NOT LIKE %s"; // Admin
        $params[] = '%/admin/%';
        $where_clause .= " AND v.page_url NOT LIKE %s"; // WP Content
        $params[] = '%/wp-content/%';
        $where_clause .= " AND v.page_url NOT LIKE %s"; // WP Includes
        $params[] = '%/wp-includes/%';
        $where_clause .= " AND v.page_url NOT LIKE %s"; // Static files
        $params[] = '%.js%';
        $where_clause .= " AND v.page_url NOT LIKE %s";
        $params[] = '%.css%';
        $where_clause .= " AND v.page_url NOT LIKE %s";
        $params[] = '%.map%';
        $where_clause .= " AND v.page_url NOT LIKE %s";
        $params[] = '%.jpg%';
        $where_clause .= " AND v.page_url NOT LIKE %s";
        $params[] = '%.png%';
        $where_clause .= " AND v.page_url NOT LIKE %s";
        $params[] = '%.gif%';
        $where_clause .= " AND v.page_url NOT LIKE %s";
        $params[] = '%.svg%';
        $where_clause .= " AND v.page_url NOT LIKE %s";
        $params[] = '%.woff%';
        $where_clause .= " AND v.page_url NOT LIKE %s";
        $params[] = '%.ttf%';
        $where_clause .= " AND (v.page_title IS NULL OR (v.page_title NOT LIKE %s AND v.page_title NOT LIKE %s AND v.page_title NOT LIKE %s))";
        $params[] = '%404%';
        $params[] = '%برگه پیدا نشد%';
        $params[] = '%صفحه پیدا نشد%';
        $where_clause .= " AND (v.page_title IS NULL OR (v.page_title NOT LIKE %s AND v.page_title NOT LIKE %s AND v.page_title NOT LIKE %s))";
        $params[] = '%داشبورد%';
        $params[] = '%Dashboard%';
        $params[] = '%مدیریتی%';
        
        if ($start_date && $end_date) {
            $where_clause .= " AND DATE(v.visit_date) >= %s AND DATE(v.visit_date) <= %s";
            $params[] = $start_date;
            $params[] = $end_date;
        }
        
        $params[] = $limit;
        
        // Get unique visitors - only the most recent visit per visitor
        // Use a simpler approach: get all visits, then filter and deduplicate in PHP
        $query = "SELECT 
                v.*,
                vis.ip_address,
                vis.country,
                vis.country_code,
                vis.browser,
                vis.os,
                vis.device_type,
                vis.device_model,
                (SELECT COUNT(DISTINCT v2.id)
                 FROM $visits_table v2
                 WHERE v2.visitor_id = vis.id" . 
                 ($start_date && $end_date ? " AND DATE(v2.visit_date) >= %s AND DATE(v2.visit_date) <= %s" : "") . 
                 " AND v2.page_url NOT LIKE '%/dashboard/%'
                 AND v2.page_url NOT LIKE '%/wp-admin/%'
                 AND v2.page_url NOT LIKE '%/wp-content/%'
                 AND v2.page_url NOT LIKE '%.js%'
                 AND v2.page_url NOT LIKE '%.css%'
                 AND v2.page_url NOT LIKE '%.map%'
                 AND (v2.page_title IS NULL OR (v2.page_title NOT LIKE '%404%' AND v2.page_title NOT LIKE '%برگه پیدا نشد%' AND v2.page_title NOT LIKE '%داشبورد%'))
                 ) as total_visits,
                (SELECT v3.referrer
                 FROM $visits_table v3
                 WHERE v3.visitor_id = vis.id
                 AND v3.referrer IS NOT NULL
                 AND v3.referrer != ''" . 
                 ($start_date && $end_date ? " AND DATE(v3.visit_date) >= %s AND DATE(v3.visit_date) <= %s" : "") . 
                 ($start_date && $end_date ? " AND v3.referrer NOT LIKE %s" : "") . 
                 " ORDER BY v3.visit_date DESC
                 LIMIT 1) as referrer_url,
                (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(v4.referrer, '://', -1), '/', 1)
                 FROM $visits_table v4
                 WHERE v4.visitor_id = vis.id
                 AND v4.referrer IS NOT NULL
                 AND v4.referrer != ''" . 
                 ($start_date && $end_date ? " AND DATE(v4.visit_date) >= %s AND DATE(v4.visit_date) <= %s" : "") . 
                 ($start_date && $end_date ? " AND v4.referrer NOT LIKE %s" : "") . 
                 " ORDER BY v4.visit_date DESC
                 LIMIT 1) as referrer_domain
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            $where_clause
            ORDER BY v.visit_date DESC
            LIMIT %d";
        
        // Get site domain for filtering self-referrals
        $site_domain = parse_url(home_url(), PHP_URL_HOST);
        $site_domain_clean = preg_replace('/^www\./', '', $site_domain);
        
        // Add date parameters for subqueries
        $subquery_params = [];
        if ($start_date && $end_date) {
            // For total_visits subquery
            $subquery_params[] = $start_date;
            $subquery_params[] = $end_date;
            // For referrer_url subquery - filter self-referrals
            $subquery_params[] = $start_date;
            $subquery_params[] = $end_date;
            $subquery_params[] = '%' . $wpdb->esc_like($site_domain_clean) . '%';
            // For referrer_domain subquery - filter self-referrals
            $subquery_params[] = $start_date;
            $subquery_params[] = $end_date;
            $subquery_params[] = '%' . $wpdb->esc_like($site_domain_clean) . '%';
        } else {
            // Even without date filter, filter self-referrals
            $subquery_params[] = '%' . $wpdb->esc_like($site_domain_clean) . '%';
            $subquery_params[] = '%' . $wpdb->esc_like($site_domain_clean) . '%';
        }
        
        // Merge subquery params, then main params
        $all_params = array_merge($subquery_params, $params);
        
        $results = $wpdb->get_results($wpdb->prepare($query, $all_params));
        
        // Filter results in PHP - be less strict, only filter obvious invalid entries
        $filtered_results = [];
        $seen_visitors = []; // Track unique visitors
        
        foreach ($results as $result) {
            // Skip if we've already seen this visitor (ensure uniqueness)
            $visitor_key = $result->visitor_id ?? '';
            if (isset($seen_visitors[$visitor_key])) {
                continue;
            }
            $seen_visitors[$visitor_key] = true;
            
            // Only skip obvious invalid entries
            $page_url_lower = strtolower($result->page_url ?? '');
            $page_title_lower = strtolower($result->page_title ?? '');
            
            $skip = false;
            
            // Only check for obvious static files (not all extensions)
            if ((strpos($page_url_lower, '.js') !== false && strpos($page_url_lower, '.min.js') !== false) ||
                (strpos($page_url_lower, '.css') !== false && strpos($page_url_lower, '.min.css') !== false) ||
                strpos($page_url_lower, '.map') !== false) {
                $skip = true;
            }
            
            // Only check for obvious internal paths
            if (!$skip && (
                strpos($page_url_lower, '/wp-admin/') !== false ||
                strpos($page_url_lower, '/wp-content/plugins/') !== false ||
                strpos($page_url_lower, '/wp-content/themes/') !== false ||
                strpos($page_url_lower, '/wp-includes/') !== false
            )) {
                $skip = true;
            }
            
            // Only check for obvious 404 pages
            if (!$skip && (
                strpos($page_title_lower, '404') !== false && strpos($page_title_lower, 'error') !== false ||
                strpos($page_title_lower, 'برگه پیدا نشد') !== false
            )) {
                $skip = true;
            }
            
            // Clear self-referrals
            if (!empty($result->referrer_domain)) {
                $referrer_domain_lower = strtolower($result->referrer_domain);
                if ($referrer_domain_lower === $site_domain_clean || 
                    strpos($referrer_domain_lower, $site_domain_clean) !== false) {
                    $result->referrer_domain = '';
                    $result->referrer_url = '';
                }
            }
            
            if (!$skip) {
                $filtered_results[] = $result;
            }
        }
        
        return array_slice($filtered_results, 0, $limit);
    }
    
    /**
     * Get online visitors (active in last 15 minutes)
     */
    public static function get_online_visitors() {
        global $wpdb;
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
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
     * Get most active visitors with complete information
     */
    public static function get_most_active_visitors($limit = 10, $start_date = null, $end_date = null) {
        global $wpdb;
        
        if (!$start_date) {
            $start_date = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$end_date) {
            $end_date = date('Y-m-d');
        }
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
        // Get site domain for filtering self-referrals
        $site_domain = parse_url(home_url(), PHP_URL_HOST);
        $site_domain_clean = preg_replace('/^www\./', '', $site_domain);
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                vis.*,
                COUNT(DISTINCT v.id) as visit_count,
                MAX(v.visit_date) as last_visit_date,
                (SELECT v2.page_url 
                 FROM $visits_table v2 
                 WHERE v2.visitor_id = vis.id 
                 AND DATE(v2.visit_date) >= %s 
                 AND DATE(v2.visit_date) <= %s
                 ORDER BY v2.visit_date ASC 
                 LIMIT 1) as entry_page_url,
                (SELECT v2.page_title 
                 FROM $visits_table v2 
                 WHERE v2.visitor_id = vis.id 
                 AND DATE(v2.visit_date) >= %s 
                 AND DATE(v2.visit_date) <= %s
                 ORDER BY v2.visit_date ASC 
                 LIMIT 1) as entry_page_title,
                (SELECT v3.page_url 
                 FROM $visits_table v3 
                 WHERE v3.visitor_id = vis.id 
                 AND DATE(v3.visit_date) >= %s 
                 AND DATE(v3.visit_date) <= %s
                 ORDER BY v3.visit_date DESC 
                 LIMIT 1) as exit_page_url,
                (SELECT v3.page_title 
                 FROM $visits_table v3 
                 WHERE v3.visitor_id = vis.id 
                 AND DATE(v3.visit_date) >= %s 
                 AND DATE(v3.visit_date) <= %s
                 ORDER BY v3.visit_date DESC 
                 LIMIT 1) as exit_page_title,
                (SELECT v4.referrer 
                 FROM $visits_table v4 
                 WHERE v4.visitor_id = vis.id 
                 AND DATE(v4.visit_date) >= %s 
                 AND DATE(v4.visit_date) <= %s
                 AND v4.referrer IS NOT NULL 
                 AND v4.referrer != ''
                 AND v4.referrer NOT LIKE %s
                 ORDER BY v4.visit_date ASC 
                 LIMIT 1) as referrer_url,
                (SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(v5.referrer, '://', -1), '/', 1)
                 FROM $visits_table v5 
                 WHERE v5.visitor_id = vis.id 
                 AND DATE(v5.visit_date) >= %s 
                 AND DATE(v5.visit_date) <= %s
                 AND v5.referrer IS NOT NULL 
                 AND v5.referrer != ''
                 AND v5.referrer NOT LIKE %s
                 ORDER BY v5.visit_date ASC 
                 LIMIT 1) as referrer_domain
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE DATE(v.visit_date) >= %s 
            AND DATE(v.visit_date) <= %s
            AND vis.is_bot = 0
            AND v.page_url NOT LIKE %s
            AND v.page_url NOT LIKE %s
            AND v.page_url NOT LIKE %s
            GROUP BY vis.id
            HAVING visit_count > 0
            ORDER BY visit_count DESC
            LIMIT %d",
            // Entry page URL params
            $start_date, $end_date,
            // Entry page title params
            $start_date, $end_date,
            // Exit page URL params
            $start_date, $end_date,
            // Exit page title params
            $start_date, $end_date,
            // Referrer URL params
            $start_date, $end_date, '%' . $wpdb->esc_like($site_domain_clean) . '%',
            // Referrer domain params
            $start_date, $end_date, '%' . $wpdb->esc_like($site_domain_clean) . '%',
            // Main query params - only filter obvious admin/static files
            $start_date, $end_date, '%/wp-admin/%', '%/wp-content/plugins/%', '%/wp-content/themes/%',
            $limit
        ));
        
        // Filter results in PHP - accept ALL results, only set defaults for empty values
        $filtered_results = [];
        foreach ($results as $result) {
            // Skip only if visit_count is 0 or null
            if (empty($result->visit_count) || $result->visit_count == 0) {
                continue;
            }
            
            // If both entry and exit pages are empty/null, set default values
            if (empty($result->entry_page_url) && empty($result->exit_page_url)) {
                $result->entry_page_url = home_url('/');
                $result->entry_page_title = esc_html__('Home Page', 'autopuzzle');
                $result->exit_page_url = home_url('/');
                $result->exit_page_title = esc_html__('Home Page', 'autopuzzle');
            } elseif (empty($result->entry_page_url)) {
                $result->entry_page_url = $result->exit_page_url ?? home_url('/');
                $result->entry_page_title = $result->exit_page_title ?? esc_html__('Home Page', 'autopuzzle');
            } elseif (empty($result->exit_page_url)) {
                $result->exit_page_url = $result->entry_page_url ?? home_url('/');
                $result->exit_page_title = $result->entry_page_title ?? esc_html__('Home Page', 'autopuzzle');
            }
            
            // Clear self-referrals
            if (!empty($result->referrer_domain)) {
                $referrer_domain_lower = strtolower($result->referrer_domain);
                if ($referrer_domain_lower === $site_domain_clean || 
                    strpos($referrer_domain_lower, $site_domain_clean) !== false) {
                    $result->referrer_domain = '';
                    $result->referrer_url = '';
                }
            }
            
            // Accept ALL results - don't filter anything else
            $filtered_results[] = $result;
        }
        
        return $filtered_results;
    }
    
    /**
     * Get referrer type label (Direct, Search, Social)
     */
    public static function get_referrer_type_label($referrer_url, $referrer_domain) {
        if (empty($referrer_url) && empty($referrer_domain)) {
            return esc_html__('Direct Traffic', 'autopuzzle');
        }
        
        $domain = strtolower($referrer_domain ?? '');
        $url = strtolower($referrer_url ?? '');
        
        // Check for search engines
        $search_engines = ['google', 'bing', 'yahoo', 'yandex', 'duckduckgo', 'baidu'];
        foreach ($search_engines as $engine) {
            if (strpos($domain, $engine) !== false || strpos($url, $engine) !== false) {
                return esc_html__('Organic Search', 'autopuzzle');
            }
        }
        
        // Check for social networks
        $social_networks = ['instagram', 'facebook', 'twitter', 'linkedin', 'telegram', 'whatsapp', 'youtube', 'tiktok', 'pinterest', 'snapchat'];
        foreach ($social_networks as $social) {
            if (strpos($domain, $social) !== false || strpos($url, $social) !== false) {
                return esc_html__('Organic Social Networks', 'autopuzzle');
            }
        }
        
        // If referrer exists but not search or social, show domain
        if (!empty($referrer_domain)) {
            return $referrer_domain;
        }
        
        return esc_html__('Direct Traffic', 'autopuzzle');
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
            'desktop' => esc_html__('Desktop', 'autopuzzle'),
            'mobile' => esc_html__('Smartphone', 'autopuzzle'),
            'smartphone' => esc_html__('Smartphone', 'autopuzzle'),
            'phablet' => esc_html__('Phablet', 'autopuzzle'),
            'tablet' => esc_html__('Tablet', 'autopuzzle'),
            'unknown' => esc_html__('Unknown', 'autopuzzle'),
        ];
    }

    /**
     * Translate device model label
     */
    public static function translate_device_model($device_model) {
        $device_model = trim((string) $device_model);
        if ($device_model === '' || strtolower($device_model) === 'desktop') {
            return esc_html__('(Not Set)', 'autopuzzle');
        }
        
        $lookup = strtolower($device_model);
        
        // Check for iPhone (must be first to catch iPhone models)
        if (strpos($lookup, 'iphone') !== false) {
            // Extract iPhone model if available (e.g., "iPhone 13", "iPhone 14 Pro")
            if (preg_match('/iphone\s*(\d+|xr|xs|se|pro|max|plus)/i', $device_model, $matches)) {
                $model_num = ucwords($matches[1]);
                return esc_html__('Apple iPhone', 'autopuzzle') . ' ' . $model_num;
            }
            return esc_html__('Apple iPhone', 'autopuzzle');
        }
        
        // Check for iPad
        if (strpos($lookup, 'ipad') !== false) {
            return esc_html__('iPad', 'autopuzzle');
        }
        
        // Check for Samsung
        if (strpos($lookup, 'samsung') !== false) {
            // Try to extract Galaxy model
            if (preg_match('/galaxy\s*([a-z0-9\s]+)/i', $device_model, $matches)) {
                $model = trim($matches[1]);
                if (!empty($model)) {
                    return esc_html__('Samsung Galaxy', 'autopuzzle') . ' ' . ucwords($model);
                }
            }
            return esc_html__('Samsung Galaxy', 'autopuzzle');
        }
        
        // Check for Xiaomi/Redmi
        if (strpos($lookup, 'xiaomi') !== false || strpos($lookup, 'redmi') !== false) {
            // Try to extract Redmi model
            if (preg_match('/redmi\s*([a-z0-9\s]+)/i', $device_model, $matches)) {
                $model = trim($matches[1]);
                if (!empty($model)) {
                    return esc_html__('Xiaomi Redmi', 'autopuzzle') . ' ' . ucwords($model);
                }
            }
            return esc_html__('Xiaomi Redmi', 'autopuzzle');
        }
        
        // Check for other common brands
        if (strpos($lookup, 'huawei') !== false || strpos($lookup, 'honor') !== false) {
            return esc_html__('Huawei', 'autopuzzle');
        }
        
        if (strpos($lookup, 'oppo') !== false) {
            return esc_html__('OPPO', 'autopuzzle');
        }
        
        if (strpos($lookup, 'vivo') !== false) {
            return esc_html__('Vivo', 'autopuzzle');
        }
        
        if (strpos($lookup, 'oneplus') !== false) {
            return esc_html__('OnePlus', 'autopuzzle');
        }
        
        if (strpos($lookup, 'realme') !== false) {
            return esc_html__('Realme', 'autopuzzle');
        }
        
        // Generic device types - keep them as is, don't convert to "(Not Set)"
        // This way we can see all device models, even generic ones
        $generic_map = [
            'desktop' => esc_html__('Desktop', 'autopuzzle'),
            'android device' => esc_html__('Android Device', 'autopuzzle'),
            'mobile device' => esc_html__('Mobile Device', 'autopuzzle'),
            'tablet device' => esc_html__('Tablet Device', 'autopuzzle'),
            'unknown' => esc_html__('Unknown Device', 'autopuzzle'),
        ];
        
        if (isset($generic_map[$lookup])) {
            return $generic_map[$lookup];
        }
        
        // Return original if it looks like a valid model name
        // This preserves the original model name from database
        return $device_model;
    }
    
    /**
     * Translate browser name
     */
    public static function translate_browser_name($browser) {
        $browser = trim((string) $browser);
        if ($browser === '') {
            return esc_html__('Unknown', 'autopuzzle');
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
            'chrome' => esc_html__('Chrome', 'autopuzzle'),
            'chrome mobile' => esc_html__('Chrome Mobile', 'autopuzzle'),
            'firefox' => esc_html__('Firefox', 'autopuzzle'),
            'safari' => esc_html__('Safari', 'autopuzzle'),
            'mobile safari' => esc_html__('Mobile Safari', 'autopuzzle'),
            'edge' => esc_html__('Edge', 'autopuzzle'),
            'ie' => esc_html__('Internet Explorer', 'autopuzzle'),
            'opera' => esc_html__('Opera', 'autopuzzle'),
            'chromium' => esc_html__('Chromium', 'autopuzzle'),
            'instagram' => esc_html__('Instagram', 'autopuzzle'),
            'unknown' => esc_html__('Unknown', 'autopuzzle'),
        ];
    }
    
    /**
     * Get browser icon HTML
     */
    public static function get_browser_icon($browser) {
        $browser_lower = strtolower(trim((string) $browser));
        
        // Map browsers to icons
        $icon_map = [
            'chrome' => '<i class="ri-chrome-line fs-18 text-primary"></i>',
            'chrome mobile' => '<i class="ri-chrome-line fs-18 text-primary"></i>',
            'firefox' => '<i class="ri-firefox-line fs-18 text-warning"></i>',
            'safari' => '<i class="ri-safari-line fs-18 text-info"></i>',
            'mobile safari' => '<i class="ri-safari-line fs-18 text-info"></i>',
            'edge' => '<i class="ri-edge-line fs-18 text-primary"></i>',
            'ie' => '<i class="ri-internet-explorer-line fs-18 text-info"></i>',
            'opera' => '<i class="ri-opera-line fs-18 text-danger"></i>',
            'chromium' => '<i class="ri-chrome-line fs-18 text-primary"></i>',
            'instagram' => '<i class="ri-instagram-line fs-18 text-danger"></i>',
        ];
        
        // Check for exact match first
        if (isset($icon_map[$browser_lower])) {
            return $icon_map[$browser_lower];
        }
        
        // Check for partial matches
        foreach ($icon_map as $key => $icon) {
            if (strpos($browser_lower, $key) !== false || strpos($key, $browser_lower) !== false) {
                return $icon;
            }
        }
        
        // Check for specific patterns
        if (strpos($browser_lower, 'instagram') !== false) {
            return $icon_map['instagram'];
        }
        if (strpos($browser_lower, 'chrome') !== false && strpos($browser_lower, 'mobile') !== false) {
            return $icon_map['chrome mobile'];
        }
        if (strpos($browser_lower, 'chrome') !== false) {
            return $icon_map['chrome'];
        }
        if (strpos($browser_lower, 'safari') !== false && strpos($browser_lower, 'mobile') !== false) {
            return $icon_map['mobile safari'];
        }
        if (strpos($browser_lower, 'safari') !== false) {
            return $icon_map['safari'];
        }
        if (strpos($browser_lower, 'firefox') !== false) {
            return $icon_map['firefox'];
        }
        if (strpos($browser_lower, 'edge') !== false) {
            return $icon_map['edge'];
        }
        if (strpos($browser_lower, 'opera') !== false) {
            return $icon_map['opera'];
        }
        
        // Default icon
        return '<i class="ri-global-line fs-18 text-secondary"></i>';
    }
    
    /**
     * Translate OS name
     */
    public static function translate_os_name($os) {
        $os = trim((string) $os);
        if ($os === '') {
            return esc_html__('Unknown', 'autopuzzle');
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
            'windows' => esc_html__('Windows', 'autopuzzle'),
            'macos' => esc_html__('macOS', 'autopuzzle'),
            'linux' => esc_html__('GNU/Linux', 'autopuzzle'),
            'android' => esc_html__('Android', 'autopuzzle'),
            'ios' => esc_html__('iOS', 'autopuzzle'),
            'ipados' => esc_html__('iPadOS', 'autopuzzle'),
            'unknown' => esc_html__('Unknown', 'autopuzzle'),
        ];
    }
    
    /**
     * Get OS icon HTML
     */
    public static function get_os_icon($os) {
        $os_lower = strtolower(trim((string) $os));
        
        // Map OS to icons
        $icon_map = [
            'android' => '<i class="ri-android-line fs-18 text-success"></i>',
            'ios' => '<i class="ri-apple-line fs-18 text-secondary"></i>',
            'ipados' => '<i class="ri-apple-line fs-18 text-secondary"></i>',
            'windows' => '<i class="ri-windows-line fs-18 text-primary"></i>',
            'macos' => '<i class="ri-apple-line fs-18 text-secondary"></i>',
            'linux' => '<i class="ri-ubuntu-line fs-18 text-warning"></i>',
        ];
        
        // Check for exact match first
        if (isset($icon_map[$os_lower])) {
            return $icon_map[$os_lower];
        }
        
        // Check for partial matches
        if (strpos($os_lower, 'android') !== false) {
            return $icon_map['android'];
        }
        if (strpos($os_lower, 'ios') !== false || strpos($os_lower, 'iphone') !== false || strpos($os_lower, 'ipad') !== false) {
            return $icon_map['ios'];
        }
        if (strpos($os_lower, 'windows') !== false) {
            return $icon_map['windows'];
        }
        if (strpos($os_lower, 'mac') !== false || strpos($os_lower, 'darwin') !== false) {
            return $icon_map['macos'];
        }
        if (strpos($os_lower, 'linux') !== false || strpos($os_lower, 'ubuntu') !== false || strpos($os_lower, 'debian') !== false) {
            return $icon_map['linux'];
        }
        
        // Default icon
        return '<i class="ri-computer-line fs-18 text-secondary"></i>';
    }
    
    /**
     * Get device type icon HTML
     */
    public static function get_device_type_icon($device_type) {
        $device_type_lower = strtolower(trim((string) $device_type));
        
        // Map device types to icons
        $icon_map = [
            'mobile' => '<i class="ri-smartphone-line fs-18 text-primary"></i>',
            'smartphone' => '<i class="ri-smartphone-line fs-18 text-primary"></i>',
            'phablet' => '<i class="ri-smartphone-2-line fs-18 text-info"></i>',
            'tablet' => '<i class="ri-tablet-line fs-18 text-warning"></i>',
            'desktop' => '<i class="ri-computer-line fs-18 text-success"></i>',
        ];
        
        // Check for exact match first
        if (isset($icon_map[$device_type_lower])) {
            return $icon_map[$device_type_lower];
        }
        
        // Check for partial matches
        if (strpos($device_type_lower, 'mobile') !== false || strpos($device_type_lower, 'smartphone') !== false) {
            return $icon_map['smartphone'];
        }
        if (strpos($device_type_lower, 'phablet') !== false) {
            return $icon_map['phablet'];
        }
        if (strpos($device_type_lower, 'tablet') !== false || strpos($device_type_lower, 'ipad') !== false) {
            return $icon_map['tablet'];
        }
        if (strpos($device_type_lower, 'desktop') !== false) {
            return $icon_map['desktop'];
        }
        
        // Default icon
        return '<i class="ri-device-line fs-18 text-secondary"></i>';
    }
    
    /**
     * Get search engine icon HTML
     */
    public static function get_search_engine_icon($search_engine) {
        $engine_lower = strtolower(trim((string) $search_engine));
        
        // Map search engines to icons
        $icon_map = [
            'google' => '<i class="ri-google-line fs-18" style="color: #4285F4;"></i>',
            'bing' => '<i class="ri-search-line fs-18" style="color: #008373;"></i>',
            'yahoo' => '<i class="ri-search-line fs-18" style="color: #6001D2;"></i>',
            'yandex' => '<i class="ri-search-line fs-18" style="color: #FC3F1D;"></i>',
            'duckduckgo' => '<i class="ri-search-line fs-18" style="color: #DE5833;"></i>',
            'baidu' => '<i class="ri-search-line fs-18" style="color: #2932E1;"></i>',
        ];
        
        // Check for exact match first
        if (isset($icon_map[$engine_lower])) {
            return $icon_map[$engine_lower];
        }
        
        // Check for partial matches
        foreach ($icon_map as $key => $icon) {
            if (strpos($engine_lower, $key) !== false || strpos($key, $engine_lower) !== false) {
                return $icon;
            }
        }
        
        // Default icon
        return '<i class="ri-search-line fs-18 text-secondary"></i>';
    }
    
    /**
     * Get referrer icon HTML based on domain
     */
    public static function get_referrer_icon($referrer_domain) {
        if (empty($referrer_domain)) {
            return '<i class="ri-links-line fs-18 text-secondary"></i>';
        }
        
        $domain_lower = strtolower(trim((string) $referrer_domain));
        // Remove www. prefix for matching
        $domain_lower = preg_replace('/^www\./', '', $domain_lower);
        
        // Map referrers to icons
        $icon_map = [
            'google.com' => '<i class="ri-google-line fs-18" style="color: #4285F4;"></i>',
            'google.com.au' => '<i class="ri-google-line fs-18" style="color: #4285F4;"></i>',
            'google.co.uk' => '<i class="ri-google-line fs-18" style="color: #4285F4;"></i>',
            'bing.com' => '<i class="ri-search-line fs-18" style="color: #008373;"></i>',
            'yahoo.com' => '<i class="ri-search-line fs-18" style="color: #6001D2;"></i>',
            'yandex.com' => '<i class="ri-search-line fs-18" style="color: #FC3F1D;"></i>',
            'duckduckgo.com' => '<i class="ri-search-line fs-18" style="color: #DE5833;"></i>',
            'facebook.com' => '<i class="ri-facebook-line fs-18" style="color: #1877F2;"></i>',
            'm.facebook.com' => '<i class="ri-facebook-line fs-18" style="color: #1877F2;"></i>',
            'instagram.com' => '<i class="ri-instagram-line fs-18" style="color: #E4405F;"></i>',
            'l.instagram.com' => '<i class="ri-instagram-line fs-18" style="color: #E4405F;"></i>',
            'twitter.com' => '<i class="ri-twitter-x-line fs-18" style="color: #1DA1F2;"></i>',
            'x.com' => '<i class="ri-twitter-x-line fs-18" style="color: #000000;"></i>',
            'linkedin.com' => '<i class="ri-linkedin-box-line fs-18" style="color: #0A66C2;"></i>',
            'reddit.com' => '<i class="ri-reddit-line fs-18" style="color: #FF4500;"></i>',
            'youtube.com' => '<i class="ri-youtube-line fs-18" style="color: #FF0000;"></i>',
            'tiktok.com' => '<i class="ri-tiktok-line fs-18" style="color: #000000;"></i>',
            'telegram.org' => '<i class="ri-telegram-line fs-18" style="color: #0088CC;"></i>',
            'whatsapp.com' => '<i class="ri-whatsapp-line fs-18" style="color: #25D366;"></i>',
        ];
        
        // Check for exact match first
        if (isset($icon_map[$domain_lower])) {
            return $icon_map[$domain_lower];
        }
        
        // Check for partial matches (domain contains key)
        foreach ($icon_map as $key => $icon) {
            if (strpos($domain_lower, $key) !== false) {
                return $icon;
            }
        }
        
        // Check for common patterns
        if (strpos($domain_lower, 'google') !== false) {
            return $icon_map['google.com'];
        }
        if (strpos($domain_lower, 'facebook') !== false) {
            return $icon_map['facebook.com'];
        }
        if (strpos($domain_lower, 'instagram') !== false) {
            return $icon_map['instagram.com'];
        }
        if (strpos($domain_lower, 'twitter') !== false || strpos($domain_lower, 'x.com') !== false) {
            return $icon_map['twitter.com'];
        }
        if (strpos($domain_lower, 'reddit') !== false) {
            return $icon_map['reddit.com'];
        }
        if (strpos($domain_lower, 'youtube') !== false) {
            return $icon_map['youtube.com'];
        }
        if (strpos($domain_lower, 'linkedin') !== false) {
            return $icon_map['linkedin.com'];
        }
        if (strpos($domain_lower, 'telegram') !== false) {
            return $icon_map['telegram.org'];
        }
        if (strpos($domain_lower, 'whatsapp') !== false) {
            return $icon_map['whatsapp.com'];
        }
        
        // Check if it's the same site (self-referral)
        $site_domain = strtolower(parse_url(home_url(), PHP_URL_HOST));
        $site_domain_clean = preg_replace('/^www\./', '', $site_domain);
        if ($domain_lower === $site_domain_clean || strpos($domain_lower, $site_domain_clean) !== false) {
            return '<i class="ri-home-line fs-18 text-primary"></i>';
        }
        
        // Default icon for unknown referrers
        return '<i class="ri-links-line fs-18 text-secondary"></i>';
    }
    
    /**
     * Translate country name using WooCommerce or fallback list
     */
    public static function translate_country_name($country_code, $fallback = '') {
        $entry = self::resolve_country_entry($country_code, $fallback);
        if ($entry !== null) {
            return self::localize_country_label($entry['names']);
        }

        $fallback_label = $fallback !== '' ? $fallback : esc_html__('Unknown', 'autopuzzle');
        $use_persian_digits = function_exists('autopuzzle_should_use_persian_digits') ? autopuzzle_should_use_persian_digits() : true;
        if (!$use_persian_digits && function_exists('autopuzzle_convert_to_english_digits')) {
            $fallback_label = autopuzzle_convert_to_english_digits($fallback_label);
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
        $use_persian_digits = function_exists('autopuzzle_should_use_persian_digits') ? autopuzzle_should_use_persian_digits() : true;

        if (function_exists('WC') && WC()->countries) {
            $countries = WC()->countries->get_countries();
            if (!empty($countries)) {
                foreach ($countries as $code => $name) {
                    $upper_code = strtoupper($code);
                    $entry = self::resolve_country_entry($upper_code, $name);
                    if ($entry) {
                        $translated[$upper_code] = self::localize_country_label($entry['names']);
                    } else {
                        $base_label = $use_persian_digits ? $name : _x($name, 'country name', 'autopuzzle');
                        if (!$use_persian_digits && function_exists('autopuzzle_convert_to_english_digits')) {
                            $base_label = autopuzzle_convert_to_english_digits($base_label);
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
            'en' => esc_html__('Unknown', 'autopuzzle'),
            'fa' => esc_html__('نامشخص', 'autopuzzle'),
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
            'en' => esc_html__('Unknown', 'autopuzzle'),
            'fa' => esc_html__('نامشخص', 'autopuzzle'),
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
        _x('Local', 'country name', 'autopuzzle');
        _x('Afghanistan', 'country name', 'autopuzzle');
        _x('Åland Islands', 'country name', 'autopuzzle');
        _x('Albania', 'country name', 'autopuzzle');
        _x('Algeria', 'country name', 'autopuzzle');
        _x('American Samoa', 'country name', 'autopuzzle');
        _x('Andorra', 'country name', 'autopuzzle');
        _x('Angola', 'country name', 'autopuzzle');
        _x('Anguilla', 'country name', 'autopuzzle');
        _x('Antarctica', 'country name', 'autopuzzle');
        _x('Antigua and Barbuda', 'country name', 'autopuzzle');
        _x('Argentina', 'country name', 'autopuzzle');
        _x('Armenia', 'country name', 'autopuzzle');
        _x('Aruba', 'country name', 'autopuzzle');
        _x('Australia', 'country name', 'autopuzzle');
        _x('Austria', 'country name', 'autopuzzle');
        _x('Azerbaijan', 'country name', 'autopuzzle');
        _x('Bahamas', 'country name', 'autopuzzle');
        _x('Bahrain', 'country name', 'autopuzzle');
        _x('Bangladesh', 'country name', 'autopuzzle');
        _x('Barbados', 'country name', 'autopuzzle');
        _x('Belarus', 'country name', 'autopuzzle');
        _x('Belgium', 'country name', 'autopuzzle');
        _x('Belize', 'country name', 'autopuzzle');
        _x('Benin', 'country name', 'autopuzzle');
        _x('Bermuda', 'country name', 'autopuzzle');
        _x('Bhutan', 'country name', 'autopuzzle');
        _x('Bolivia', 'country name', 'autopuzzle');
        _x('Bonaire, Sint Eustatius and Saba', 'country name', 'autopuzzle');
        _x('Bosnia and Herzegovina', 'country name', 'autopuzzle');
        _x('Botswana', 'country name', 'autopuzzle');
        _x('Bouvet Island', 'country name', 'autopuzzle');
        _x('Brazil', 'country name', 'autopuzzle');
        _x('British Indian Ocean Territory', 'country name', 'autopuzzle');
        _x('Brunei Darussalam', 'country name', 'autopuzzle');
        _x('Bulgaria', 'country name', 'autopuzzle');
        _x('Burkina Faso', 'country name', 'autopuzzle');
        _x('Burundi', 'country name', 'autopuzzle');
        _x('Cabo Verde', 'country name', 'autopuzzle');
        _x('Cambodia', 'country name', 'autopuzzle');
        _x('Cameroon', 'country name', 'autopuzzle');
        _x('Canada', 'country name', 'autopuzzle');
        _x('Cayman Islands', 'country name', 'autopuzzle');
        _x('Central African Republic', 'country name', 'autopuzzle');
        _x('Chad', 'country name', 'autopuzzle');
        _x('Chile', 'country name', 'autopuzzle');
        _x('China', 'country name', 'autopuzzle');
        _x('Christmas Island', 'country name', 'autopuzzle');
        _x('Cocos (Keeling) Islands', 'country name', 'autopuzzle');
        _x('Colombia', 'country name', 'autopuzzle');
        _x('Comoros', 'country name', 'autopuzzle');
        _x('Congo', 'country name', 'autopuzzle');
        _x('Congo, Democratic Republic of the', 'country name', 'autopuzzle');
        _x('Cook Islands', 'country name', 'autopuzzle');
        _x('Costa Rica', 'country name', 'autopuzzle');
        _x('Croatia', 'country name', 'autopuzzle');
        _x('Cuba', 'country name', 'autopuzzle');
        _x('Curaçao', 'country name', 'autopuzzle');
        _x('Cyprus', 'country name', 'autopuzzle');
        _x('Czechia', 'country name', 'autopuzzle');
        _x('Denmark', 'country name', 'autopuzzle');
        _x('Djibouti', 'country name', 'autopuzzle');
        _x('Dominica', 'country name', 'autopuzzle');
        _x('Dominican Republic', 'country name', 'autopuzzle');
        _x('Ecuador', 'country name', 'autopuzzle');
        _x('Egypt', 'country name', 'autopuzzle');
        _x('El Salvador', 'country name', 'autopuzzle');
        _x('Equatorial Guinea', 'country name', 'autopuzzle');
        _x('Eritrea', 'country name', 'autopuzzle');
        _x('Estonia', 'country name', 'autopuzzle');
        _x('Eswatini', 'country name', 'autopuzzle');
        _x('Ethiopia', 'country name', 'autopuzzle');
        _x('Falkland Islands', 'country name', 'autopuzzle');
        _x('Faroe Islands', 'country name', 'autopuzzle');
        _x('Fiji', 'country name', 'autopuzzle');
        _x('Finland', 'country name', 'autopuzzle');
        _x('France', 'country name', 'autopuzzle');
        _x('French Guiana', 'country name', 'autopuzzle');
        _x('French Polynesia', 'country name', 'autopuzzle');
        _x('French Southern Territories', 'country name', 'autopuzzle');
        _x('Gabon', 'country name', 'autopuzzle');
        _x('Gambia', 'country name', 'autopuzzle');
        _x('Georgia', 'country name', 'autopuzzle');
        _x('Germany', 'country name', 'autopuzzle');
        _x('Ghana', 'country name', 'autopuzzle');
        _x('Gibraltar', 'country name', 'autopuzzle');
        _x('Greece', 'country name', 'autopuzzle');
        _x('Greenland', 'country name', 'autopuzzle');
        _x('Grenada', 'country name', 'autopuzzle');
        _x('Guadeloupe', 'country name', 'autopuzzle');
        _x('Guam', 'country name', 'autopuzzle');
        _x('Guatemala', 'country name', 'autopuzzle');
        _x('Guernsey', 'country name', 'autopuzzle');
        _x('Guinea', 'country name', 'autopuzzle');
        _x('Guinea-Bissau', 'country name', 'autopuzzle');
        _x('Guyana', 'country name', 'autopuzzle');
        _x('Haiti', 'country name', 'autopuzzle');
        _x('Heard Island and McDonald Islands', 'country name', 'autopuzzle');
        _x('Holy See', 'country name', 'autopuzzle');
        _x('Honduras', 'country name', 'autopuzzle');
        _x('Hong Kong', 'country name', 'autopuzzle');
        _x('Hungary', 'country name', 'autopuzzle');
        _x('Iceland', 'country name', 'autopuzzle');
        _x('India', 'country name', 'autopuzzle');
        _x('Indonesia', 'country name', 'autopuzzle');
        _x('Iran', 'country name', 'autopuzzle');
        _x('Iraq', 'country name', 'autopuzzle');
        _x('Ireland', 'country name', 'autopuzzle');
        _x('Isle of Man', 'country name', 'autopuzzle');
        _x('Israel', 'country name', 'autopuzzle');
        _x('Italy', 'country name', 'autopuzzle');
        _x('Jamaica', 'country name', 'autopuzzle');
        _x('Japan', 'country name', 'autopuzzle');
        _x('Jersey', 'country name', 'autopuzzle');
        _x('Jordan', 'country name', 'autopuzzle');
        _x('Kazakhstan', 'country name', 'autopuzzle');
        _x('Kenya', 'country name', 'autopuzzle');
        _x('Kiribati', 'country name', 'autopuzzle');
        _x('Korea (North)', 'country name', 'autopuzzle');
        _x('Korea (South)', 'country name', 'autopuzzle');
        _x('Kuwait', 'country name', 'autopuzzle');
        _x('Kyrgyzstan', 'country name', 'autopuzzle');
        _x('Lao People’s Democratic Republic', 'country name', 'autopuzzle');
        _x('Latvia', 'country name', 'autopuzzle');
        _x('Lebanon', 'country name', 'autopuzzle');
        _x('Lesotho', 'country name', 'autopuzzle');
        _x('Liberia', 'country name', 'autopuzzle');
        _x('Libya', 'country name', 'autopuzzle');
        _x('Liechtenstein', 'country name', 'autopuzzle');
        _x('Lithuania', 'country name', 'autopuzzle');
        _x('Luxembourg', 'country name', 'autopuzzle');
        _x('Macao', 'country name', 'autopuzzle');
        _x('Madagascar', 'country name', 'autopuzzle');
        _x('Malawi', 'country name', 'autopuzzle');
        _x('Malaysia', 'country name', 'autopuzzle');
        _x('Maldives', 'country name', 'autopuzzle');
        _x('Mali', 'country name', 'autopuzzle');
        _x('Malta', 'country name', 'autopuzzle');
        _x('Marshall Islands', 'country name', 'autopuzzle');
        _x('Martinique', 'country name', 'autopuzzle');
        _x('Mauritania', 'country name', 'autopuzzle');
        _x('Mauritius', 'country name', 'autopuzzle');
        _x('Mayotte', 'country name', 'autopuzzle');
        _x('Mexico', 'country name', 'autopuzzle');
        _x('Micronesia', 'country name', 'autopuzzle');
        _x('Moldova', 'country name', 'autopuzzle');
        _x('Monaco', 'country name', 'autopuzzle');
        _x('Mongolia', 'country name', 'autopuzzle');
        _x('Montenegro', 'country name', 'autopuzzle');
        _x('Montserrat', 'country name', 'autopuzzle');
        _x('Morocco', 'country name', 'autopuzzle');
        _x('Mozambique', 'country name', 'autopuzzle');
        _x('Myanmar', 'country name', 'autopuzzle');
        _x('Namibia', 'country name', 'autopuzzle');
        _x('Nauru', 'country name', 'autopuzzle');
        _x('Nepal', 'country name', 'autopuzzle');
        _x('Netherlands', 'country name', 'autopuzzle');
        _x('New Caledonia', 'country name', 'autopuzzle');
        _x('New Zealand', 'country name', 'autopuzzle');
        _x('Nicaragua', 'country name', 'autopuzzle');
        _x('Niger', 'country name', 'autopuzzle');
        _x('Nigeria', 'country name', 'autopuzzle');
        _x('Niue', 'country name', 'autopuzzle');
        _x('Norfolk Island', 'country name', 'autopuzzle');
        _x('Northern Mariana Islands', 'country name', 'autopuzzle');
        _x('Norway', 'country name', 'autopuzzle');
        _x('Oman', 'country name', 'autopuzzle');
        _x('Pakistan', 'country name', 'autopuzzle');
        _x('Palau', 'country name', 'autopuzzle');
        _x('Palestine, State of', 'country name', 'autopuzzle');
        _x('Panama', 'country name', 'autopuzzle');
        _x('Papua New Guinea', 'country name', 'autopuzzle');
        _x('Paraguay', 'country name', 'autopuzzle');
        _x('Peru', 'country name', 'autopuzzle');
        _x('Philippines', 'country name', 'autopuzzle');
        _x('Pitcairn', 'country name', 'autopuzzle');
        _x('Poland', 'country name', 'autopuzzle');
        _x('Portugal', 'country name', 'autopuzzle');
        _x('Puerto Rico', 'country name', 'autopuzzle');
        _x('Qatar', 'country name', 'autopuzzle');
        _x('Réunion', 'country name', 'autopuzzle');
        _x('Romania', 'country name', 'autopuzzle');
        _x('Russia', 'country name', 'autopuzzle');
        _x('Rwanda', 'country name', 'autopuzzle');
        _x('Saint Barthélemy', 'country name', 'autopuzzle');
        _x('Saint Helena, Ascension and Tristan da Cunha', 'country name', 'autopuzzle');
        _x('Saint Kitts and Nevis', 'country name', 'autopuzzle');
        _x('Saint Lucia', 'country name', 'autopuzzle');
        _x('Saint Martin (French part)', 'country name', 'autopuzzle');
        _x('Saint Pierre and Miquelon', 'country name', 'autopuzzle');
        _x('Saint Vincent and the Grenadines', 'country name', 'autopuzzle');
        _x('Samoa', 'country name', 'autopuzzle');
        _x('San Marino', 'country name', 'autopuzzle');
        _x('Sao Tome and Principe', 'country name', 'autopuzzle');
        _x('Saudi Arabia', 'country name', 'autopuzzle');
        _x('Senegal', 'country name', 'autopuzzle');
        _x('Serbia', 'country name', 'autopuzzle');
        _x('Seychelles', 'country name', 'autopuzzle');
        _x('Sierra Leone', 'country name', 'autopuzzle');
        _x('Singapore', 'country name', 'autopuzzle');
        _x('Sint Maarten (Dutch part)', 'country name', 'autopuzzle');
        _x('Slovakia', 'country name', 'autopuzzle');
        _x('Slovenia', 'country name', 'autopuzzle');
        _x('Solomon Islands', 'country name', 'autopuzzle');
        _x('Somalia', 'country name', 'autopuzzle');
        _x('South Africa', 'country name', 'autopuzzle');
        _x('South Georgia and the South Sandwich Islands', 'country name', 'autopuzzle');
        _x('South Sudan', 'country name', 'autopuzzle');
        _x('Spain', 'country name', 'autopuzzle');
        _x('Sri Lanka', 'country name', 'autopuzzle');
        _x('Sudan', 'country name', 'autopuzzle');
        _x('Suriname', 'country name', 'autopuzzle');
        _x('Svalbard and Jan Mayen', 'country name', 'autopuzzle');
        _x('Sweden', 'country name', 'autopuzzle');
        _x('Switzerland', 'country name', 'autopuzzle');
        _x('Syrian Arab Republic', 'country name', 'autopuzzle');
        _x('Taiwan', 'country name', 'autopuzzle');
        _x('Tajikistan', 'country name', 'autopuzzle');
        _x('Tanzania, United Republic of', 'country name', 'autopuzzle');
        _x('Thailand', 'country name', 'autopuzzle');
        _x('Timor-Leste', 'country name', 'autopuzzle');
        _x('Togo', 'country name', 'autopuzzle');
        _x('Tokelau', 'country name', 'autopuzzle');
        _x('Tonga', 'country name', 'autopuzzle');
        _x('Trinidad and Tobago', 'country name', 'autopuzzle');
        _x('Tunisia', 'country name', 'autopuzzle');
        _x('Turkey', 'country name', 'autopuzzle');
        _x('Turkmenistan', 'country name', 'autopuzzle');
        _x('Turks and Caicos Islands', 'country name', 'autopuzzle');
        _x('Tuvalu', 'country name', 'autopuzzle');
        _x('Uganda', 'country name', 'autopuzzle');
        _x('Ukraine', 'country name', 'autopuzzle');
        _x('United Arab Emirates', 'country name', 'autopuzzle');
        _x('United Kingdom', 'country name', 'autopuzzle');
        _x('United States', 'country name', 'autopuzzle');
        _x('United States Minor Outlying Islands', 'country name', 'autopuzzle');
        _x('Uruguay', 'country name', 'autopuzzle');
        _x('Uzbekistan', 'country name', 'autopuzzle');
        _x('Vanuatu', 'country name', 'autopuzzle');
        _x('Venezuela', 'country name', 'autopuzzle');
        _x('Viet Nam', 'country name', 'autopuzzle');
        _x('Virgin Islands (British)', 'country name', 'autopuzzle');
        _x('Virgin Islands (U.S.)', 'country name', 'autopuzzle');
        _x('Wallis and Futuna', 'country name', 'autopuzzle');
        _x('Western Sahara', 'country name', 'autopuzzle');
        _x('Yemen', 'country name', 'autopuzzle');
        _x('Zambia', 'country name', 'autopuzzle');
        _x('Zimbabwe', 'country name', 'autopuzzle');
        _x('Kosovo', 'country name', 'autopuzzle');
    }
    
    /**
     * Localize country label based on active locale (Persian vs English)
     */
    private static function localize_country_label(array $names) {
        $use_persian_digits = function_exists('autopuzzle_should_use_persian_digits') ? autopuzzle_should_use_persian_digits() : true;
        $english_base = $names['en'] ?? '';
        $persian_base = $names['fa'] ?? $english_base;
        
        if ($use_persian_digits) {
            $label = $persian_base !== '' ? $persian_base : $english_base;
            if (function_exists('persian_numbers_no_separator')) {
                $label = persian_numbers_no_separator($label);
            }
            return esc_html($label);
        }
        
        $label = $english_base !== '' ? _x($english_base, 'country name', 'autopuzzle') : esc_html__('Unknown', 'autopuzzle');
        if (function_exists('autopuzzle_convert_to_english_digits')) {
            $label = autopuzzle_convert_to_english_digits($label);
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
            'en' => esc_html__('Unknown', 'autopuzzle'),
            'fa' => esc_html__('نامشخص', 'autopuzzle'),
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
            return esc_html__('Moments ago', 'autopuzzle');
        }
        
        $diff_text = human_time_diff($timestamp, $current_timestamp);
        $use_persian_digits = function_exists('autopuzzle_should_use_persian_digits') ? autopuzzle_should_use_persian_digits() : true;

        if (!$use_persian_digits) {
            if (function_exists('autopuzzle_convert_to_english_digits')) {
                $diff_text = autopuzzle_convert_to_english_digits($diff_text);
            }
            return trim($diff_text . ' ' . esc_html__('ago', 'autopuzzle'));
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
        
        return trim($translated . ' ' . esc_html__('ago', 'autopuzzle'));
    }
    
    /**
     * Get statistics for different time periods
     * Returns stats for: today, yesterday, this week, last week, this month, last month, etc.
     */
    public static function get_period_statistics() {
        global $wpdb;
        
        $visits_table = $wpdb->prefix . 'autopuzzle_visits';
        $visitors_table = $wpdb->prefix . 'autopuzzle_visitors';
        
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $this_week_start = date('Y-m-d', strtotime('monday this week'));
        $last_week_start = date('Y-m-d', strtotime('monday last week'));
        $last_week_end = date('Y-m-d', strtotime('sunday last week'));
        $this_month_start = date('Y-m-01');
        $last_month_start = date('Y-m-01', strtotime('first day of last month'));
        $last_month_end = date('Y-m-t', strtotime('last month'));
        $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        $ninety_days_ago = date('Y-m-d', strtotime('-90 days'));
        $six_months_ago = date('Y-m-d', strtotime('-6 months'));
        $this_year_start = date('Y-01-01');
        
        $periods = [];
        
        // Helper function to get stats for a date range
        $get_stats = function($start_date, $end_date) use ($wpdb, $visits_table, $visitors_table) {
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    COUNT(DISTINCT v.id) as visit_count,
                    COUNT(DISTINCT v.visitor_id) as unique_visitors
                FROM $visits_table v
                INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
                WHERE DATE(v.visit_date) >= %s 
                AND DATE(v.visit_date) <= %s
                AND vis.is_bot = 0",
                $start_date,
                $end_date
            ));
            
            return [
                'visits' => (int)($stats->visit_count ?? 0),
                'visitors' => (int)($stats->unique_visitors ?? 0)
            ];
        };
        
        // Today
        $periods['today'] = $get_stats($today, $today);
        
        // Yesterday
        $periods['yesterday'] = $get_stats($yesterday, $yesterday);
        
        // This week (Monday to today)
        $periods['this_week'] = $get_stats($this_week_start, $today);
        
        // Last week (Monday to Sunday)
        $periods['last_week'] = $get_stats($last_week_start, $last_week_end);
        
        // This month
        $periods['this_month'] = $get_stats($this_month_start, $today);
        
        // Last month
        $periods['last_month'] = $get_stats($last_month_start, $last_month_end);
        
        // Last 7 days
        $periods['last_7_days'] = $get_stats($seven_days_ago, $today);
        
        // Last 30 days
        $periods['last_30_days'] = $get_stats($thirty_days_ago, $today);
        
        // Last 90 days
        $periods['last_90_days'] = $get_stats($ninety_days_ago, $today);
        
        // Last 6 months
        $periods['last_6_months'] = $get_stats($six_months_ago, $today);
        
        // This year (January 1 to today)
        $periods['this_year'] = $get_stats($this_year_start, $today);
        
        // All time
        $periods['all_time'] = $wpdb->get_row(
            "SELECT 
                COUNT(DISTINCT v.id) as visit_count,
                COUNT(DISTINCT v.visitor_id) as unique_visitors
            FROM $visits_table v
            INNER JOIN $visitors_table vis ON v.visitor_id = vis.id
            WHERE vis.is_bot = 0"
        );
        $periods['all_time'] = [
            'visits' => (int)($periods['all_time']->visit_count ?? 0),
            'visitors' => (int)($periods['all_time']->unique_visitors ?? 0)
        ];
        
        return $periods;
    }
}



