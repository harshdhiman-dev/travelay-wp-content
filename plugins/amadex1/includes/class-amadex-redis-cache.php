<?php
/**
 * Amadex Redis Cache Helper
 * 
 * Provides Redis caching with automatic fallback to WordPress transients
 * Handles connection errors gracefully and maintains backward compatibility
 *
 * @package Amadex
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Redis Cache Class
 */
class Amadex_Redis_Cache {
    
    /**
     * Redis connection instance
     *
     * @var Redis|null
     */
    private static $redis = null;
    
    /**
     * Whether Redis is available and connected
     *
     * @var bool
     */
    private static $redis_available = null;
    
    /**
     * Cache key prefix
     *
     * @var string
     */
    private static $prefix = 'amadex:';
    
    /**
     * Initialize Redis connection
     *
     * @return bool True if Redis is available, false otherwise
     */
    private static function init_redis() {
        // Check if already determined
        if (self::$redis_available !== null) {
            return self::$redis_available;
        }
        
        // Get Redis settings
        $settings = get_option('amadex_performance_settings', array());
        $redis_enabled = isset($settings['enable_redis_cache']) && $settings['enable_redis_cache'] === '1';
        
        // If Redis is disabled in settings, don't try to connect
        if (!$redis_enabled) {
            self::$redis_available = false;
            return false;
        }
        
        // Check if Redis extension is available
        if (!class_exists('Redis') && !class_exists('Predis\Client')) {
            self::$redis_available = false;
            amadex_log('Redis: Redis extension not available. Using WordPress transients fallback.');
            return false;
        }
        
        // Get Redis connection settings
        $redis_host_raw = isset($settings['redis_host']) ? trim($settings['redis_host']) : '127.0.0.1';
        $redis_port = isset($settings['redis_port']) ? intval($settings['redis_port']) : 6379;
        $redis_password = isset($settings['redis_password']) ? $settings['redis_password'] : '';
        $redis_username = isset($settings['redis_username']) ? trim($settings['redis_username']) : '';
        $redis_database = isset($settings['redis_database']) ? intval($settings['redis_database']) : 0;
        // Use TLS only if explicitly enabled. Redis Cloud Free (30MB) does NOT support TLS - forcing it causes "packet length too long"
        $redis_use_tls = isset($settings['redis_use_tls']) && $settings['redis_use_tls'] === '1';

        // Parse host:port format (e.g. redis-cloud.example.com:10000)
        $redis_host = $redis_host_raw;
        if (strpos($redis_host_raw, ':') !== false && !preg_match('/^\[.+\]$/', $redis_host_raw)) {
            $parts = explode(':', $redis_host_raw);
            $redis_host = trim($parts[0]);
            if (isset($parts[1]) && is_numeric(trim($parts[1]))) {
                $redis_port = max(1, min(65535, intval(trim($parts[1]))));
            }
        }

        $is_redis_cloud = (stripos($redis_host_raw, 'redislabs.com') !== false || stripos($redis_host_raw, 'redis.cloud') !== false);

        // Suppress SSL/connection warnings during attempts (they flood logs; we fall back to transients)
        $prev_handler = set_error_handler(function ($errno, $errstr, $file, $line) {
            if ($errno === E_WARNING && (strpos($errstr, 'SSL') !== false || strpos($errstr, 'Redis') !== false || strpos($errstr, 'stream_socket') !== false)) {
                return true; // suppress
            }
            return false; // pass to normal handler
        }, E_WARNING);

        try {
            // For Redis Cloud: try TCP first (TLS may be disabled on database; "packet length too long" = TLS to non-TLS port)
            $schemes_to_try = $redis_use_tls ? array('tcp', 'rediss', 'tls') : array('tcp');

            // For Redis Cloud: try Predis (tcp first avoids SSL "packet length too long" when TLS is off on DB)
            if ($is_redis_cloud && class_exists('Predis\Client')) {
                foreach ($schemes_to_try as $scheme) {
                    try {
                        $parameters = array(
                            'scheme' => $scheme,
                            'host' => $redis_host,
                            'port' => $redis_port,
                            'timeout' => 5.0,
                            'read_write_timeout' => 5.0,
                        );
                        if (!empty($redis_username)) {
                            $parameters['username'] = $redis_username;
                        }
                        if (!empty($redis_password)) {
                            $parameters['password'] = $redis_password;
                        }
                        if ($redis_database > 0) {
                            $parameters['database'] = $redis_database;
                        }
                        if ($scheme === 'rediss' || $scheme === 'tls') {
                            $parameters['ssl'] = array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true,
                                'capture_peer_cert' => false,
                                'crypto_type' => defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT : STREAM_CRYPTO_METHOD_TLS_CLIENT,
                            );
                        }
                        self::$redis = new Predis\Client($parameters);
                        self::$redis->ping();
                        self::$redis_available = true;
                        amadex_log('Redis: Successfully connected using Predis (' . $scheme . ')');
                        return true;
                    } catch (Throwable $e) {
                        self::$redis = null;
                        if ($scheme === 'tcp') {
                            break; // fall through to PHP Redis / non-Cloud logic
                        }
                        amadex_log('Redis: Predis ' . $scheme . ' failed, trying next - ' . $e->getMessage());
                    }
                }
            }

            // Try PHP Redis extension (non-Cloud or Predis fallback)
            if (class_exists('Redis')) {
                self::$redis = new Redis();
                $connect_host = $redis_use_tls ? ('tls://' . $redis_host) : $redis_host;
                $connected = self::$redis->connect($connect_host, $redis_port, 2.0);

                if (!$connected) {
                    throw new Exception('Failed to connect to Redis');
                }

                if (!empty($redis_password)) {
                    if (!empty($redis_username)) {
                        if (!self::$redis->auth(array('user' => $redis_username, 'pass' => $redis_password))) {
                            throw new Exception('Redis authentication failed');
                        }
                    } else {
                        if (!self::$redis->auth($redis_password)) {
                            throw new Exception('Redis authentication failed');
                        }
                    }
                }

                if ($redis_database > 0) {
                    self::$redis->select($redis_database);
                }

                $ping = self::$redis->ping();
                if ($ping !== '+PONG' && $ping !== true) {
                    throw new Exception('Redis ping failed');
                }

                self::$redis_available = true;
                amadex_log('Redis: Successfully connected to Redis server');
                return true;
            }

            if (!$is_redis_cloud && class_exists('Predis\Client')) {
                $scheme = $redis_use_tls ? 'tls' : 'tcp';
                $parameters = array(
                    'scheme' => $scheme,
                    'host' => $redis_host,
                    'port' => $redis_port,
                    'timeout' => 2.0,
                );
                if (!empty($redis_username)) {
                    $parameters['username'] = $redis_username;
                }
                if (!empty($redis_password)) {
                    $parameters['password'] = $redis_password;
                }
                if ($redis_database > 0) {
                    $parameters['database'] = $redis_database;
                }
                if ($redis_use_tls) {
                    $parameters['ssl'] = array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    );
                }
                self::$redis = new Predis\Client($parameters);
                self::$redis->ping();
                self::$redis_available = true;
                amadex_log('Redis: Successfully connected using Predis');
                return true;
            }
            
            self::$redis_available = false;
            return false;
            
        } catch (Exception $e) {
            self::$redis_available = false;
            self::$redis = null;
            amadex_log('Redis: Connection failed - ' . $e->getMessage() . '. Using WordPress transients fallback.');
            return false;
        } catch (Throwable $e) {
            self::$redis_available = false;
            self::$redis = null;
            amadex_log('Redis: Connection failed - ' . $e->getMessage() . '. Using WordPress transients fallback.');
            return false;
        } finally {
            restore_error_handler();
        }
    }
    
    /**
     * Get value from cache
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed Cached value or default
     */
    public static function get($key, $default = false) {
        // Initialize Redis connection
        if (self::init_redis() && self::$redis !== null) {
            try {
                $full_key = self::$prefix . $key;
                
                // Handle both Redis and Predis
                if (self::$redis instanceof Redis) {
                    $value = self::$redis->get($full_key);
                } else {
                    // Predis
                    $value = self::$redis->get($full_key);
                }
                
                if ($value !== false) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        amadex_log('Redis: Cache HIT for key: ' . $key);
                        return $decoded;
                    }
                }
                
                amadex_log('Redis: Cache MISS for key: ' . $key);
                
            } catch (Exception $e) {
                amadex_log('Redis: Error getting key ' . $key . ' - ' . $e->getMessage(), 'warning');
                // Fall through to transient fallback
            }
        }
        
        // Fallback to WordPress transients
        $transient_key = 'amadex_cache_' . md5($key);
        $value = get_transient($transient_key);
        
        if ($value !== false) {
            amadex_log('Redis: Transient cache HIT for key: ' . $key);
            return $value;
        }
        
        amadex_log('Redis: Transient cache MISS for key: ' . $key);
        return $default;
    }
    
    /**
     * Set value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $expiration Expiration time in seconds (default: 300 = 5 minutes)
     * @return bool True on success, false on failure
     */
    public static function set($key, $value, $expiration = 300) {
        // Initialize Redis connection
        if (self::init_redis() && self::$redis !== null) {
            try {
                $full_key = self::$prefix . $key;
                $encoded = json_encode($value);
                
                // Handle both Redis and Predis
                if (self::$redis instanceof Redis) {
                    $result = self::$redis->setex($full_key, $expiration, $encoded);
                } else {
                    // Predis
                    $result = self::$redis->setex($full_key, $expiration, $encoded);
                }
                
                if ($result) {
                    amadex_log('Redis: Successfully cached key: ' . $key . ' (TTL: ' . $expiration . 's)');
                    return true;
                }
                
            } catch (Exception $e) {
                amadex_log('Redis: Error setting key ' . $key . ' - ' . $e->getMessage(), 'warning');
                // Fall through to transient fallback
            }
        }
        
        // Fallback to WordPress transients
        $transient_key = 'amadex_cache_' . md5($key);
        $result = set_transient($transient_key, $value, $expiration);
        
        if ($result) {
            amadex_log('Redis: Successfully cached key using transient: ' . $key);
        }
        
        return $result;
    }
    
    /**
     * Delete value from cache
     *
     * @param string $key Cache key
     * @return bool True on success, false on failure
     */
    public static function delete($key) {
        // Initialize Redis connection
        if (self::init_redis() && self::$redis !== null) {
            try {
                $full_key = self::$prefix . $key;
                
                // Handle both Redis and Predis
                if (self::$redis instanceof Redis) {
                    $result = self::$redis->del($full_key);
                } else {
                    // Predis
                    $result = self::$redis->del($full_key);
                }
                
                if ($result) {
                    amadex_log('Redis: Successfully deleted key: ' . $key);
                }
                
            } catch (Exception $e) {
                amadex_log('Redis: Error deleting key ' . $key . ' - ' . $e->getMessage(), 'warning');
            }
        }
        
        // Also delete from transient fallback
        $transient_key = 'amadex_cache_' . md5($key);
        delete_transient($transient_key);
        
        return true;
    }
    
    /**
     * Generate cache key from search parameters
     *
     * @param array $params Search parameters
     * @return string Cache key
     */
    public static function generate_cache_key($params) {
        // Normalize parameters (sort keys, remove non-essential fields)
        $normalized = array(
            'origin' => strtoupper(trim($params['origin'] ?? '')),
            'destination' => strtoupper(trim($params['destination'] ?? '')),
            'departure_date' => $params['departure_date'] ?? '',
            'return_date' => $params['return_date'] ?? '',
            'adults' => intval($params['adults'] ?? 1),
            'children' => intval($params['children'] ?? 0),
            'infants' => intval($params['infants'] ?? 0),
            'travel_class' => strtoupper(trim($params['travel_class'] ?? '')),
            'currency' => strtoupper(trim($params['currency'] ?? 'USD')),
        );
        
        // Generate hash from normalized parameters
        $hash = md5(serialize($normalized));
        
        return 'flight_search:' . $hash;
    }
    
    /**
     * Check if Redis is available
     *
     * @return bool
     */
    public static function is_available() {
        self::init_redis();
        return self::$redis_available === true;
    }
    
    /**
     * Clear all Amadex cache entries (use with caution)
     *
     * @return bool
     */
    public static function clear_all() {
        // Clear Redis cache
        if (self::init_redis() && self::$redis !== null) {
            try {
                // Get all keys with prefix
                if (self::$redis instanceof Redis) {
                    $keys = self::$redis->keys(self::$prefix . '*');
                } else {
                    // Predis
                    $keys = self::$redis->keys(self::$prefix . '*');
                }
                
                if (!empty($keys)) {
                    if (self::$redis instanceof Redis) {
                        self::$redis->del($keys);
                    } else {
                        self::$redis->del($keys);
                    }
                    amadex_log('Redis: Cleared ' . count($keys) . ' cache entries');
                }
                
            } catch (Exception $e) {
                amadex_log('Redis: Error clearing cache - ' . $e->getMessage(), 'warning');
            }
        }
        
        // Clear transients (WordPress doesn't have a direct way to clear all, so we skip this)
        // Individual keys will expire naturally
        
        return true;
    }
    
    /**
     * Close Redis connection
     */
    public static function close() {
        if (self::$redis instanceof Redis) {
            try {
                self::$redis->close();
            } catch (Exception $e) {
                // Ignore close errors
            }
        }
        self::$redis = null;
        self::$redis_available = null;
    }
    
    /**
     * Cache formatted flight results
     * 
     * Stores already-formatted flight results to skip formatting on cache hit
     * 
     * @param string $cache_key Base cache key
     * @param array $formatted_results Formatted flight results
     * @param int $expiration Expiration time in seconds (default: 300 = 5 minutes)
     * @return bool True on success, false on failure
     */
    public static function cache_formatted_results($cache_key, $formatted_results, $expiration = 300) {
        $formatted_key = $cache_key . ':formatted';
        return self::set($formatted_key, $formatted_results, $expiration);
    }
    
    /**
     * Get cached formatted flight results
     * 
     * Retrieves pre-formatted flight results if available
     * 
     * @param string $cache_key Base cache key
     * @return array|false Formatted results on success, false on cache miss
     */
    public static function get_formatted_results($cache_key) {
        $formatted_key = $cache_key . ':formatted';
        return self::get($formatted_key, false);
    }
}
