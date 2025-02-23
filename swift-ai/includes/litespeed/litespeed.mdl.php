<?php

class Swift3_Litespeed_Module {

      public static $purge_tags = array();

      public static function load(){
            add_action('plugins_loaded', array(__CLASS__, 'maybe_deactivate'));

            if (preg_match('~litespeed~i',  $_SERVER['SERVER_SOFTWARE'])){
                  add_filter('swift3_cache_function', function(){
                        return array(__CLASS__, 'send_headers');
                  });
                  add_filter('swift3_cache_hit_function', function(){
                        return array(__CLASS__, 'cache_hit');
                  });
                  add_action('swift3_generate_rewrites_litespeed', array(__CLASS__, 'generate_rewrites'));
                  add_action('swift3_remove_rewrites_litespeed', array(__CLASS__, 'remove_rewrites'));
                  add_filter('swift3_server_software', function(){
                        return 'litespeed';
                  });
                  add_filter('swift3_miss_header', function(){
                        return 'X-LiteSpeed-Cache-Control: no-cache';
                  });
                  add_action('swift3_invalidate_object', array(__CLASS__, 'delete_cache'));
                  add_action('swift3_purge_cache', array(__CLASS__, 'delete_cache'));
                  add_action('swift3_development_mode_activated', array(__CLASS__, 'delete_cache'));
                  add_action('swift3_rest_action_litespeed/purge', array(__CLASS__, 'force_purge_url'));
            }
      }

      public static function send_headers($buffer, $type, $cache){
            do_action('swift3_litespeed_cache_headers');
            header('X-LiteSpeed-Cache-Control: public,max-age=43200');
            header('X-LiteSpeed-Tag: ' . Swift3_Warmup::get_id($cache->get_current_url()));
      }

      public static function generate_rewrites(){
            $file = Swift3_Helper::get_home_path() . '.htaccess';
            $rules = '';
            if (is_writeable($file)){
                  $htaccess = file_get_contents($file);
                  $htaccess = preg_replace("~# BEGIN SWIFT3(.*)# END SWIFT3\n\n~is", '', $htaccess);
                  if (swift3_check_option('caching', 'on')){
                        ob_start();
                        include __DIR__ . '/htaccess.tpl.php';
                        $rules = ob_get_clean();
                  }
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
      public static function remove_rewrites(){
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

      public static function delete_cache($url){
            if (headers_sent()){
                  wp_remote_post(Swift3_REST::get_url('litespeed/purge'), array(
                        'timeout'   => 30,
                        'sslverify' => false,
                        'user-agent' => Swift3_Helper::ua_string('litespeed-purge-request'),
                        'headers' => array('X-Swift3-Litespeed' => md5(AUTH_KEY)),
                        'body' => array('url' => $url)
                  ));
            }
            else{
                  if (empty($url)){
                        header('X-LiteSpeed-Purge: *');
                  }
                  else {
                        self::$purge_tags[] = 'tag=' . Swift3_Warmup::get_id($url);
                        header('X-LiteSpeed-Purge: ' . implode(', ', self::$purge_tags));
                  }
            }
      }

      public static function cache_hit($url){
            wp_remote_post(Swift3_REST::get_url('litespeed/purge'), array(
                  'timeout'   => 30,
                  'sslverify' => false,
                  'user-agent' => Swift3_Helper::ua_string('litespeed-purge-request'),
                  'headers' => array('X-Swift3-Litespeed' => md5(AUTH_KEY)),
                  'body' => array('url' => $url)
            ));
            return wp_remote_get($url, array(
                  'timeout'   => 30,
                  'sslverify' => false,
                  'user-agent' => Swift3_Helper::ua_string('cache-hit'),
                  'headers' => array('X-PREBUILD' => 1)
            ));
      }
      public static function force_purge_url(){
            if(isset($_SERVER['HTTP_X_SWIFT3_LITESPEED']) && $_SERVER['HTTP_X_SWIFT3_LITESPEED'] == md5(AUTH_KEY)){
                  if (isset($_POST['url']) && !empty($_POST['url'])){
                        header('X-LiteSpeed-Purge: tag=' . Swift3_Warmup::get_id($_POST['url']));
                  }
                  else {
                        header('X-LiteSpeed-Purge: *');
                  }
            }
      }
      public static function maybe_deactivate(){
            if (defined('LSCWP_V')){
                  add_action('admin_init', array(__CLASS__, 'deactivate'));
            }
      }

      public static function deactivate(){
            $plugin_file = Swift3_Overlap_Plugins_Module::locate('litespeed-cache/litespeed-cache.php', 'function run_litespeed_cache');
            deactivate_plugins($plugin_file);
            Swift3_Logger::log(array('plugin' => __('LiteSpeed Cache', 'swift3')), 'plugin-deactivated', 'litespeed-cache');
      }

}

if (Swift3_Helper::check_constant('DISABLE_LSCACHE')){
      return;
}

Swift3_Litespeed_Module::load();


