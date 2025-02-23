<?php

class Swift3_Config {
      public static $user_settings = array();
      public static $integrations = array();

      public static $is_development_mode = false;
      public function __construct($core){
            $core::$options = get_option('swift3_options', array());
            self::default_settings();

            add_action('admin_init', function(){
                  if (swift3_check_option('api_connection', '')){
                        $this->api_test();
                  }
                  if (swift3_check_option('generate_rewrites', false)){
                        self::generate_rewrites();
                  }
            });
            add_action('swift3_option_updated', array(__CLASS__, 'settings_updated'));
            if (isset($_GET['swift-test-page'])){
                  add_action('wp_footer', function(){
                        include_once SWIFT3_DIR . 'templates/test-page.tpl.php';
                  });
            }
            self::development_mode();
            add_filter('swift3_update_option_development-mode', function($value){
                  if ($value == 'on'){
                        return time() + 7200;
                  }
                  return $value;
            });
            if (isset($_GET['nocache']) || (is_admin() && isset($_GET['page']) && $_GET['page'] == 'swift3') || (defined('DOING_AJAX') && isset($_REQUEST['action']) && $_REQUEST['action'] == 'swift3_update_option')){
                  add_filter('swift3_code_optimizer', '__return_false');
            }
      }
      public function api_test(){
            Swift3_Logger::rlogs('license-error');
            Swift3_Logger::rlogs('api-error');
            delete_transient('swift3_subscription_status');

            $connection_test = Swift3::get_module('api')->request('check', array('url' => add_query_arg('swift-test-page','1',site_url())));
            if (is_wp_error($connection_test)){
                  Swift3_Logger::log(array('error' => $connection_test->get_error_message()), 'api-error', 'api-network-error');
                  $api_connection = 'failed';
                  do_action('swift3_config_api_test_error', $connection_test);
            }
            else {
                  $decoded = json_decode($connection_test['body']);
                  if (!empty($decoded)){
                        self::reset_settings();

                        switch ($decoded->connection) {
                              case 'simplex':
                                    $api_connection = 'simplex';
                                    break;
                              case 'duplex':
                                    $api_connection = 'duplex';
                                    break;
                              default:
                                    $api_connection = 'failed';
                                    Swift3_Logger::log(array('error' => $connection_test['body']), 'api-error', 'api-test-failed');
                                    break;
                        }
                        if (isset($decoded->image) && $decoded->image !== true){
                              swift3_update_option('optimize-images', '');
                              swift3_update_option('lazyload-images', '');
                              swift3_update_option('responsive-images', '');
                        }
                        if (isset($decoded->headers->{'content-encoding'}) && !in_array($decoded->headers->{'content-encoding'}, array('gzip','compress','deflate','br'))){
                              swift3_update_option('enable-gzip', 'on');
                        }
                  }
                  else {
                        $api_connection = 'failed';
                        Swift3_Logger::log(array('error' => $connection_test['body']), 'api-error', 'api-test-failed');
                  }
                  do_action('swift3_config_api_test', $decoded);
            }
            swift3_update_option('api_connection', $api_connection);
      }
      public static function server_software(){
            $server_software = (preg_match('~(apache|LNAMP|Shellrent|Litespeed)~i',  $_SERVER['SERVER_SOFTWARE']) ? 'apache' : 'unknown');

            return apply_filters('swift3_server_software', $server_software);
      }
      public static function generate_rewrites(){
            $server = self::server_software();
            $rules = '';

            if ($server == 'apache'){
                  $file = Swift3_Helper::get_home_path() . '.htaccess';
                  if (is_writeable($file)){
                        $htaccess = file_get_contents($file);
                        $htaccess = preg_replace("~# BEGIN SWIFT3(.*)# END SWIFT3\n\n~is", '', $htaccess);
                        $rules = Swift3_Helper::get_template('rewrites/htaccess');
                        if (strpos('# BEGIN WordPress', $htaccess) !== false){
                              $htaccess = str_replace('# BEGIN WordPress', $rules . "# BEGIN WordPress", $htaccess);
                        }
                        else {
                              $htaccess = $rules . $htaccess;
                        }
                        file_put_contents($file, $htaccess);
                        Swift3_Logger::rlogs('htaccess');
                  }
                  else {
                        Swift3_Logger::log(array('message' => __('htaccess is not writable for WordPress. Please change the permissions and try again.')), 'htaccess', 'htaccess-not-writable');
                  }
            }
            do_action('swift3_generate_rewrites_' . $server);
            swift3_update_option('generate_rewrites', true);
      }
      public static function remove_rewrites(){
            $server = self::server_software();

            if ($server == 'apache'){
                  $file = Swift3_Helper::get_home_path() . '.htaccess';
                  if (is_writeable($file)){
                        $htaccess = file_get_contents($file);
                        $htaccess = preg_replace("~# BEGIN SWIFT3(.*)# END SWIFT3\n\n~is", '', $htaccess);

                        file_put_contents($file, $htaccess);
                        Swift3_Logger::rlogs('htaccess');
                  }
                  else {
                        Swift3_Logger::log(array('message' => __('htaccess is not writable for WordPress. Please change the permissions and try again.')), 'htaccess', 'htaccess-not-writable');
                  }
            }
            do_action('swift3_remove_rewrites_' . $server);
            swift3_update_option('generate_rewrites', false);
      }
      public static function default_settings(){
            $excluded_post_types = array('attachment');
            foreach (get_post_types(array('exclude_from_search' => true)) as $post_type){
                  $excluded_post_types[] = $post_type;
            }

            self::$user_settings = array(
                  'caching' => array('on', true, array(array(__CLASS__, 'generate_rewrites'),  array(array('Swift3_Cache', 'purge_object')))),
                  'excluded-urls' => array('', false, array(array('Swift3_Cache', 'reset'), array('Swift3_Cache', 'invalidate'))),
                  'excluded-post-types' => array($excluded_post_types, false, array(array('Swift3_Cache', 'reset'), array('Swift3_Cache', 'invalidate'))),
                  'ignored-query-parameters' => array('', false),
                  'allowed-query-parameters' => array('', false, array(array('Swift3_Cache', 'reset'), array('Swift3_Cache', 'invalidate'))),
                  'bypass-cookies' => array('', false, array(array('Swift3_Exclusions', 'init'), array(__CLASS__, 'generate_rewrites'))),
                  'optimize-css' => array(2, true, array(array('Swift3_Cache', 'purge_object'))),
                  'optimize-js' => array('on', true, array(array('Swift3_Cache', 'purge_object'))),
                  'js-delivery' => array(2, true, array(array('Swift3_Cache', 'purge_object'))),
                  'optimize-images' => array('on', true, array(array('Swift3_Cache', 'purge_object'))),
                  'lazyload-images' => array('on', true, array(array('Swift3_Cache', 'purge_object'))),
                  'responsive-images' => array('on', true, array(array('Swift3_Cache', 'purge_object'))),
                  'enforce-image-size' => array('', true, array(array('Swift3_Cache', 'purge_object'))),
                  'optimize-images-on-upload' => array('', false),
                  'optimize-rendering' => array('on', true, array(array('Swift3_Cache', 'purge_object'))),
                  'optimize-cls' => array('on', true, array(array('Swift3_Cache', 'purge_object'))),
                  'optimize-iframes' => array(2, true, array(array('Swift3_Cache', 'purge_object'))),
                  'code-optimizer' => array('', false, array(array('Swift3_Code_Optimizer', 'init'))),
                  'force-ssl' => array('', true, array(array(__CLASS__, 'generate_rewrites'))),
                  'logged-in-cache' => array('', true, array(array('Swift3_Exclusions', 'init'), array(__CLASS__, 'generate_rewrites'))),
                  'enable-gzip' => array('', true, array(array(__CLASS__, 'generate_rewrites'))),
                  'browser-cache' => array('on', true, array(array(__CLASS__, 'generate_rewrites'))),
                  'keep-original-headers' => array('', false, array(array(__CLASS__, 'keep_original_headers_updated'))),
                  'remote-prebuild' =>  array('', false),
                  'onsite-navigation' => array('on', false,  array(array('Swift3_Cache', 'purge_object'))),
                  'ignored-prefetch-urls' => array('', false,  array(array('Swift3_Cache', 'invalidate'))),
                  'shortcode-fragments' => array('', false,  array(array('Swift3_Cache', 'invalidate'))),
                  'collage' => array('on', false,  array(array('Swift3_Cache', 'invalidate'))),
                  'http-request-cache' =>  array('', false, array(array('Swift3_HTTP_Request_Cache', 'cleanup'))),
                  'adminbar' =>  array('everywhere', false),
                  'development-mode' =>  array(0, false, array(array(__CLASS__, 'development_mode_updated'))),
            );

            self::backward_compatibility();
      }
      public static function backward_compatibility(){
            foreach (self::$user_settings as $key => $args){
                  if (!isset(Swift3::$options[$key])){
                        swift3_update_option($key, $args[0]);
                  }
            }
      }
      public static function register_settings($option){
            self::$user_settings = array_merge(self::$user_settings, $option);
      }
      public static function register_integration_panel($slug, $title, $callback){
            self::$integrations[$slug] = array(
                  'title' => $title,
                  'callback' => $callback
            );
      }
      public static function reset_settings($hard = false){
            foreach (self::$user_settings as $key => $value){
                  if ($hard || $value[1]){
                        swift3_update_option($key, $value[0]);
                  }
            }
      }
      public static function get_json($options){
            $config = array();
            foreach ($options as $option){
                  $value = swift3_get_option($option);
                  $config[str_replace('-','_',$option)] = (is_numeric($value) ? $value * 1 : $value);
            }
            return json_encode(apply_filters('swift3_config_get_json', $config, $options));
      }
      public static function settings_updated($key){
            if (isset(self::$user_settings[$key][2])){
                  foreach ((array)self::$user_settings[$key][2] as $callback){
                        if (is_callable($callback)){
                              call_user_func($callback);
                        }
                  }
            }
      }
      public static function keep_original_headers_updated(){
            Swift3_Cache::invalidate();
            Swift3_Helper::delete_files(Swift3::get_module('cache')->get_basedir(), '~^(headers\.json|\.htaccess)$~');
      }
      public static function development_mode(){
            $expiry = (int)swift3_get_option('development-mode');
            if ($expiry > time()){
                  self::$is_development_mode = true;
                  $cache_basedir = untrailingslashit(apply_filters('swift3_cache_basedir', WP_CONTENT_DIR . '/swift-ai/cache/'));
                  add_filter('swift3_cache_function', function(){
                        return array('Swift3_Helper', 'do_nothing');
                  });
                  add_filter('swift3_skip_optimizer', '__return_true');
                  add_filter('swift3_code_optimizer', '__return_false');

                  if(file_exists($cache_basedir)){
                        Swift3_Helper::delete_files($cache_basedir);
                  }
            }
            else if($expiry > 0) {
                  swift3_update_option('development-mode', 0);
            }
      }
      public static function development_mode_updated(){
            $current_status = (int)swift3_get_option('development-mode');
            $cache_basedir = untrailingslashit(apply_filters('swift3_cache_basedir', WP_CONTENT_DIR . '/swift-ai/cache/'));
            $cache_basedir_parts = explode('/', $cache_basedir);
            $cache_basedir_parts[count($cache_basedir_parts) -1] = '_' . $cache_basedir_parts[count($cache_basedir_parts) -1];
            $bypassed_cache_basedir = implode('/', $cache_basedir_parts);
            if (empty($current_status)){
                  // Bypass directory is exists. Restore it and invalidate cache
                  if (file_exists($bypassed_cache_basedir)){
                        Swift3_Helper::delete_files($cache_basedir);
                        rename($bypassed_cache_basedir, $cache_basedir);
                        Swift3_Cache::invalidate();
                        do_action('swift3_development_mode_deactivated');
                  }
            }
            else if(file_exists($cache_basedir)){
                  Swift3_Helper::delete_files($bypassed_cache_basedir);
                  rename($cache_basedir, $bypassed_cache_basedir);
                  do_action('swift3_development_mode_activated');
            }
      }
}

?>