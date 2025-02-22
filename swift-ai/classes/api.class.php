<?php

/**
 * API Class
 */
class Swift3_Api {

      public function __construct(){
            add_action('swift3_rest_action_rc', array(__CLASS__, 'rc'));
      }
      public function request($endpoint, $data = NULL){
            if (!in_array($endpoint, array('status', 'disconnect'))){
                  Swift3_Logger::rlogs('license-error');
            }

            $response = wp_remote_post(SWIFT3_API_URL . $endpoint, apply_filters('swift3_api_request_args', array(
                  'headers' => $this->get_request_headers(),
                  'body' => $data,
                  'timeout' => 60,
            )));
            if (!is_wp_error($response)){
                  $response_code = wp_remote_retrieve_response_code($response);
                  if ($response_code == 403 && !in_array($endpoint, array('status', 'disconnect'))){
                        Swift3_Logger::log(array('message' => sprintf(__('Your subscription is not active. Please check your license status %shere%s ', 'swift3'),'<a href="' . menu_page_url('swift3', false) . '#uth-system" class="swift3-dashboard-nav">', '</a>')), 'license-error', 'license-not-active');
                  }
            }

            return $response;
      }
      public function get_request_headers(){
            return array(
                  'x-site' => home_url(),
                  'x-site-key' => self::get_site_key(),
                  'x-license' => (swift3_check_option('license-handling', 'v2') ? 'v2' : 'v1')
            );
      }
      public static function get_site_key(){
            return md5((defined('NONCE_SALT') ? NONCE_SALT : ABSPATH) . Swift3_Helper::get_home_url());
      }
      public static function get_status(){
            return (swift3_check_option('api_connection', array('simplex', 'duplex'), 'CONTAINS') ? 'ok' : 'failed');
      }
      public static function rc(){
            if (!isset($_POST['site-key']) || $_POST['site-key'] !== self::get_site_key()){
                  return;
            }
            $poll = Swift3::get_module('api')->request('rc_poll');
            $actions = json_decode($poll['body']);

            if (!empty($actions)){
                  foreach ((array)$actions as $action => $data){
                        switch ($action) {
                              case 'clear-cache':
                                    Swift3_Cache::invalidate_object($data);
                                    break;
                              case 'purge-cache':
                                    Swift3_Cache::purge_object($data);
                                    break;
                              case 'reset-cache':
                                    Swift3_Warmup::reset();
                                    break;
                              case 'delete-images':
                                    Swift3_Image::delete_images();
                                    Swift3_Cache::purge_object();
                                    break;
                              case 'get-options':
                                    Swift3::get_module('api')->request('rc_settings', array(
                                          'settings' => json_encode(Swift3::$options)
                                    ));
                                    break;
                              case 'diagnostics':
                                    Swift3::get_module('api')->request('rc_diagnostics', array(
                                          'diagnostics' => json_encode(Swift3_System::diagnostics())
                                    ));
                                    break;
                              case 'update-settings':
                                    foreach ($data as $key => $value){
                                          swift3_update_option($key, $value);
                                    }
                                    Swift3::get_module('api')->request('rc_settings', array(
                                          'settings' => json_encode(Swift3::$options)
                                    ));
                                    break;
                        }
                  }
            }

      }

}

?>