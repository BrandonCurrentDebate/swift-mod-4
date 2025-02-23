<?php

class Swift3_System {

      public static $includes = array();

      public static function diagnostics() {
            $defined_constants = array();
            foreach (get_defined_constants() as $key => $value){
                  if (strpos($key, 'SWIFT3_') === 0){
                        $defined_constants[$key] = $value;
                  }
            }

            // Cache status
            $status = Swift3_Warmup::get_cache_status();
            $cache = min(100, $status->cached / max(1, $status->total) * 100)*0.7;
            $optimize = min(100, ($status->optimized + ($status->queued / 3)) / max(1, $status->total) * 100)*0.3;

            $status = number_format($cache + $optimize, 0);
            $warmup = Swift3_Helper::$db->get_results("SELECT * FROM " . Swift3_Helper::$db->swift3_warmup . " ORDER BY priority ASC");

            return array(
                  'home_url' => home_url(),
                  'server_software' => Swift3_Config::server_software(),
                  'api' => swift3_get_option('api_connection'),
                  'includes' => self::$includes,
                  'version' => Swift3::get_version(),
                  'daemons' => array(
                        'cache' => get_transient('swift3_daemon_cache_lock'),
                        'image' => get_transient('swift3_daemon_image_lock'),
                        'optimize' => get_transient('swift3_daemon_optimize_lock')
                  ),
                  'constants' => $defined_constants,
                  'status' => $status,
                  'warmup' => $warmup,
            );
      }
      public static function get_label($key){
            $labels = array(
                  'home_url' => __('Home URL', 'swift3'),
                  'server_software' => __('Server Software', 'swift3'),
                  'api' => __('API connection', 'swift3'),
                  'constants' => __('Defined constants', 'swift3'),
                  'includes' => __('Includes', 'swift3'),
                  'warmup' => __('Warmup', 'swift3'),
                  'daemons' => __('Daemons', 'swift3')
            );
            return $labels[$key];
      }

      public static function register_includes($slug){
            self::$includes[] = $slug;
      }
      public static function get_subscription_info(){
            $maybe_status = get_transient('swift3_subscription_status');
            if (!empty($maybe_status)){
                  return $maybe_status;
            }

            $status = Swift3::get_module('api')->request('status');
            if (!is_wp_error($status)){
                  $decoded_status = json_decode($status['body'], true);
            }
            if (is_wp_error($status) || empty($decoded_status)) {
                  return array(
                        'status' => __('unknown', 'swift3'),
                        'expiry' => -1,
                        'subscription_id' => -1,
                        'error' => (is_wp_error($status) ? $status->get_error_message() : __('Error: ') . wp_remote_retrieve_response_code($status))
                  );
            }
            set_transient('swift3_subscription_status', $decoded_status, 3600);

            return $decoded_status;

      }

}

?>