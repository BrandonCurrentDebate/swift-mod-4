<?php

class Swift3_HTTP_Request_Cache {

      public static $exceptions = array();

      public function __construct(){
            if (swift3_check_option('http-request-cache', 'on')){
                  add_filter('http_response', array(__CLASS__, 'save_cache'), 10, 3);
                  add_filter('pre_http_request', array(__CLASS__, 'load_cache'), 10, 3);
                  self::add_exception('api.swiftperformance.io');
                  self::add_exception('wp-cron.php');
                  if (!wp_next_scheduled('swift3_http_api_cache_cleanup')) {
                        wp_schedule_event(time(), 'hourly', 'swift3_http_api_cache_cleanup');
                  }

                  add_action('swift3_http_api_cache_cleanup', array(__CLASS__, 'cleanup'));
            }
      }

      public static function save_cache($response, $args, $url){
            if (isset($args['blocking']) && $args['blocking'] == false){
                  return $response;
            }
            if (self::is_excluded($url)){
                  return $response;
            }
            if (strlen(json_encode($response)) > SWIFT3_HTTP_REQUEST_CACHE_MAX_SIZE){
                  return $response;
            }

            $cache_key  = self::get_key($url, $args);
            $expiry     = apply_filters('swift3_http_request_cache_expiry', 300, $url, $args);
            $transient  = set_transient($cache_key, $response, $expiry);
            return $response;
      }

      public static function load_cache($result, $args, $url){
            if (isset($args['blocking']) && $args['blocking'] == false){
                  return $result;
            }
            if (self::is_excluded($url)){
                  return $result;
            }

            $cache_key = self::get_key($url, $args);
            $transient = get_transient($cache_key);

            if (!empty($transient)){
                  $args['blocking'] = false;
                  wp_remote_request($url, $args);

                  return apply_filters('swift3_http_request_cache_response', $transient, $url, $args);
            }
            return $result;
      }

      public static function is_excluded($url){
            if (!empty(self::$exceptions)){
                  $exception_regex = implode('|', (array_map(function($url){
                        return preg_quote($url, '~');
                  }, array_keys(self::$exceptions))));
                  if (preg_match('~' . $exception_regex . '~', $url)){
                        return true;
                  }
            }
            return false;
      }

      public static function get_key($url, $args){
            return 'swift3_http_api_cache_' . md5(apply_filters('swift3_http_request_cache_url', $url, $args)) . '_' . hash('crc32', json_encode(apply_filters('swift3_http_request_cache_args', $args, $url)));
      }

      public static function add_exception($url){
            self::$exceptions[$url] = true;
      }

      public static function cleanup(){
            $timestamp = (current_action() == 'swift3_http_api_cache_cleanup' ? time() : PHP_INT_MAX);

            $transients = Swift3_Helper::$db->get_col(Swift3_Helper::$db->prepare("SELECT option_name FROM " . Swift3_Helper::$db->options . " WHERE option_name LIKE '_transient_timeout_swift3_http_api_cache_%' AND option_value < %d", $timestamp));
            foreach ($transients as $transient) {
                  $transient = str_replace('_transient_timeout_', '', $transient);
                  delete_transient($transient);
            }
      }

}

?>