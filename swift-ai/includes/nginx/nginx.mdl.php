<?php

class Swift3_Nginx_Module {

      public static function load(){
            if (preg_match('~(nginx|flywheel)~i',  $_SERVER['SERVER_SOFTWARE'])){
                  Swift3_Config::register_integration_panel('nginx', __('Nginx', 'swift3'), array(__CLASS__, 'settings_panel'));
                  add_filter('swift3_server_software', function(){
                        return 'nginx';
                  });
            }
      }
      public static function get_rewrites(){
            ob_start();
            include 'rewrites.tpl.php';
            return ob_get_clean();
      }
      public static function get_settings_hash(){
            return md5(json_encode(array(
                  swift3_get_option('enable-gzip'),
                  swift3_get_option('browser-cache'),
                  swift3_get_option('caching'),
                  Swift3_Exclusions::get_bypass_cookies(),
            )));
      }
      public static function check_rewrites(){
            $result = wp_remote_get(site_url('test-swift-rewrites'), array('sslverify' => false));
            if (is_wp_error($result) || !preg_match('~^([abcdef0-9]{32})$~', $result['body'])){
                  return 0;
            }
            else if ($result['body'] == self::get_settings_hash()){
                  return 1;
            }
            else {
                  return -1;
            }
      }
      public static function settings_panel(){
            include 'settings.tpl.php';
      }

}

Swift3_Nginx_Module::load();