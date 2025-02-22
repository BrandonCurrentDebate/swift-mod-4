<?php

class Swift3_Varnish_Module {

      public static function load(){
            if (swift3_check_option('enable-varnish', 'on') || self::detected()){
                  Swift3_Config::register_integration_panel('varnish', __('Varnish', 'swift3'), array(__CLASS__, 'settings_panel'));
                  Swift3_Config::register_settings(array(
                        'enable-varnish' => array('', false),
                        'varnish-host' => array('', false),
                  ));
            }
            if (self::detected()){
                  Swift3_System::register_includes('varnish');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(12, esc_html__('Varnish detected', 'swift3'));
                  });
            }
            if (swift3_check_option('enable-varnish', 'on')){
                  add_action('swift3_purge_cache', array(__CLASS__, 'purge'));
                  add_action('swift3_cache_done', array(__CLASS__, 'purge'));
                  add_action('swift3_development_mode_activated', array(__CLASS__, 'purge'));
            }
      }
      public static function settings_panel(){
            include 'settings.tpl.php';
      }
      public static function detected(){
            return apply_filters('swift3_varnish_detected', (isset($_SERVER['HTTP_X_VARNISH']) || Swift3_Helper::check_constant('VARNISH')));
      }
      public static function purge($url = NULL){
            $varnish_host = (defined('SWIFT3_VARNISH_HOST') ? SWIFT3_VARNISH_HOST : 'http://127.0.0.1');

            if (empty($url)){
                  $response = wp_remote_request(trailingslashit($varnish_host) . '.*', array( 'method' => 'PURGE', 'headers' => array( 'host' => parse_url(home_url(), PHP_URL_HOST), 'X-Purge-Method' => 'regex')));
            }
            else {
                  $response = wp_remote_request($varnish_host . parse_url($url, PHP_URL_PATH) . '.*', array( 'method' => 'PURGE', 'headers' => array( 'host' => parse_url(home_url(), PHP_URL_HOST), 'X-Purge-Method' => 'regex')));
            }

            Swift3_Logger::rlog('varnish-purge-error');
            if (is_wp_error($response)){
                  Swift3_Logger::log(array('message' => sprintf(__('Purge Varnish has been failed. Error: %s', 'swift3'), $response->get_error_message())), 'varnish', 'varnish-purge-error');
            }
            else if (apply_filters('swift3_varnish_not_detected', $response['response']['code'] !== 200 || !isset($response['headers']['x-varnish']))){
                  swift3_update_option('enable-varnish', '');
            }
      }
}

Swift3_Varnish_Module::load();