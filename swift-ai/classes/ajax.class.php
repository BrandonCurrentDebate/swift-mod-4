<?php

class Swift3_Ajax {

      public function __construct(){
            add_action('wp_ajax_swift3_status', array($this, 'get_status'));
            add_action('wp_ajax_swift3_reload_toolbar', array($this, 'reload_toolbar'));
            add_action('wp_ajax_swift3_clear_cache', array($this, 'clear_cache'));
            add_action('wp_ajax_swift3_purge_cache', array($this, 'purge_cache'));
            add_action('wp_ajax_swift3_delete_images', array($this, 'delete_images'));
            add_action('wp_ajax_swift3_optimize', array($this, 'optimize'));
            add_action('wp_ajax_swift3_reset_cache', array($this, 'reset_cache'));
            add_action('wp_ajax_swift3_dismiss_notice', array($this, 'dismiss_notice'));
            add_action('wp_ajax_swift3_reconnect_api', array($this, 'reconnect_api'));
            add_action('wp_ajax_swift3_disconnect_license', array($this, 'disconnect_license'));
            add_action('wp_ajax_swift3_support', array($this, 'support'));
            add_action('wp_ajax_swift3_update_option', array($this, 'update_option'));
            add_action('wp_ajax_swift3_install', array($this, 'installer'));
      }
      public function get_status(){
            $this->_validate_request();

            wp_send_json(array(
                  'status' => Swift3_Dashboard::$status,
                  'notices' => '<div>' . Swift3_Logger::get_messages() . '</div>'
            ));

            die;
      }
      public function reload_toolbar(){
            $this->_validate_request();

            if (isset($_REQUEST['toolbar']) && $_REQUEST['toolbar'] == 'frontend'){
                  add_filter('swift3_toolbar_is_page_cacheable', '__return_true');
                  add_filter('swift3_get_current_url', function(){
                        return $_REQUEST['url'];
                  });

                  Swift3_Helper::print_template('toolbar');
            }
            else if (isset($_REQUEST['toolbar']) && $_REQUEST['toolbar'] == 'admin'){
                  Swift3_Helper::print_template('toolbar-admin');
            }
            die;
      }
      public function clear_cache(){
            $this->_validate_request();

            $url = (isset($_REQUEST['url']) ? $_REQUEST['url'] : NULL);

            Swift3_Cache::invalidate_object($url, -1);
            if (isset($_REQUEST['toolbar']) && $_REQUEST['toolbar'] == 'frontend'){
                  add_filter('swift3_toolbar_is_page_cacheable', '__return_true');
                  add_filter('swift3_get_current_url', function(){
                        return $_REQUEST['url'];
                  });

                  Swift3_Helper::print_template('toolbar');
            }
            else if (isset($_REQUEST['toolbar']) && $_REQUEST['toolbar'] == 'admin'){
                  Swift3_Helper::print_template('toolbar-admin');
            }
            die;
      }
      public function purge_cache(){
            $this->_validate_request();

            $url = (isset($_REQUEST['url']) ? $_REQUEST['url'] : NULL);

            Swift3_Cache::purge_object($url);
            if (isset($_REQUEST['toolbar'])){
                  add_filter('swift3_toolbar_is_page_cacheable', '__return_true');
                  add_filter('swift3_get_current_url', function(){
                        return $_REQUEST['url'];
                  });

                  Swift3_Helper::print_template('toolbar');
            }
            die;
      }
      public function delete_images(){
            $this->_validate_request();

            Swift3_Image::delete_images();
            Swift3_Cache::purge_object();
            die;
      }
      public function optimize(){
            $this->_validate_request();

            $url = (isset($_REQUEST['url']) ? $_REQUEST['url'] : NULL);
            if (!empty($url)){
                  $warmup_data = Swift3_Warmup::get_data($url);

                  if ($warmup_data->status * 1 <= 0){
                        Swift3_Daemon::cache_next($url);
                  }
                  else if ($warmup_data->status == 2) {
                        Swift3_Daemon::optimize_next($url);
                  }
            }
            if (isset($_REQUEST['toolbar'])){
                  add_filter('swift3_toolbar_is_page_cacheable', '__return_true');
                  add_filter('swift3_get_current_url', function(){
                        return $_REQUEST['url'];
                  });

                  Swift3_Helper::print_template('toolbar');
            }

            die;
      }
      public function reset_cache(){
            $this->_validate_request();

            Swift3_Warmup::reset();
            die;
      }
      public function dismiss_notice(){
            $this->_validate_request();
            list($type, $key) = explode('/', $_POST['notice']);
            switch ($type){
                  case 'id':
                        if ($_POST['try_again'] == true){
                              if(isset(Swift3_Logger::$logs[$key]['data']['url'])){
                                    Swift3_Cache::invalidate_object(Swift3_Logger::$logs[$key]['data']['url']);
                              }
                        }
                        Swift3_Logger::rlog($key, true);
                        break;
                  case 'group':
                        if ($_POST['try_again'] == true){
                              $group_entries = Swift3_Logger::get_groupped_entries();
                              foreach($group_entries[$key] as $entry){
                                    if(isset($entry['data']['url'])){
                                          Swift3_Cache::invalidate_object($entry['data']['url']);
                                    }
                              }
                        }
                        Swift3_Logger::rlogs($key);
                        break;
            }

            die;
      }
      public function reconnect_api(){
            $this->_validate_request();

            swift3_update_option('api_connection','');
            die;
      }
      public function disconnect_license(){
            $this->_validate_request();
            delete_transient('swift3_subscription_status');
            Swift3_Logger::rlogs('license-error');
            Swift3_Logger::rlogs('api-error');

            Swift3::get_module('api')->request('disconnect');
            swift3_update_option('activated', 0);
      }
      public function support(){
            $this->_validate_request();

            Swift3::get_module('api')->request('support', array(
                  'question'        => $_POST['question'],
                  'consent'         => $_POST['consent'],
                  'settings'        => json_encode(get_option('swift3_options')),
                  'diagnostics'     => json_encode(Swift3_System::diagnostics())
            ));
      }
      public function update_option(){
            $this->_validate_request();

            if (in_array($_POST['option'], array_keys(Swift3_Config::$user_settings))){
                  swift3_update_option($_POST['option'], $_POST['value']);
            }

            wp_send_json(
                  apply_filters('swift3_ajax_update_' . $_POST['option'] . '_option', array('result' => 'success'), $_POST['value'])
            );

            die;
      }
      public function installer(){
            $this->_validate_request();

            $response = Swift3_Setup::install((int)$_POST['step']);

            wp_send_json($response);
      }
      private function _validate_request(){
            if (!wp_verify_nonce($_REQUEST['nonce'], 'swift3-admin') || !current_user_can('manage_options')){
                  status_header(403);
                  echo 1;
                  die;
            }
      }

}

?>