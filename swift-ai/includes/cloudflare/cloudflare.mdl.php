<?php

class Swift3_Cloudflare_Module {

      public static function load(){
            add_action('admin_init', array(__CLASS__, 'maybe_deactivate'));
            if (swift3_check_option('enable-cloudflare', 'on') || self::detected()){
                  Swift3_Config::register_integration_panel('cloudflare', __('Cloudflare', 'swift3'), array(__CLASS__, 'settings_panel'));
                  Swift3_Config::register_settings(array(
                        'enable-cloudflare' => array('', false),
                        'cloudflare-auth-mode' => array('token', false),
                        'cloudflare-token' => array('', false),
                        'cloudflare-email' => array('', false),
                        'cloudflare-key' => array('', false),
                        'cloudflare-zone' => array('', false),
                        'cloudflare-proxy-cache' => array('', false)
                  ));
            }

            if (swift3_check_option('cloudflare-proxy-cache', 'on')){
                  add_filter('swift3_apache_cache_rewrites', '__return_false');
                  add_filter('swift3_miss_header', function($header){
                        header ('Cache-control: no-cache, no-store', false);
                        return $header;
                  });
                  add_filter('swift3_cache_header', function($header){
                        header ('Cache-control: max-age=0, s-maxage=43200');
                        return 'Swift3: HIT/Proxy';
                  });
                  add_action('swift3_litespeed_cache_headers', function(){
                        header ('Cache-control: max-age=0, s-maxage=43200');
                  });
            }
            if (self::detected()){
                  Swift3_System::register_includes('cloudflare');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(11, esc_html__('Cloudflare detected', 'swift3'));
                        Swift3_Setup::add_step(11, function(){
                              $token = $email = $key = '';

                              $spcfcf = get_option('swcfpc_config');
                              if (!empty($spcfcf)){
                                    $token = (isset($spcfcf['cf_apitoken']) ? $spcfcf['cf_apitoken'] : false);
                                    $email = (isset($spcfcf['cf_email']) ? $spcfcf['cf_email'] : false);
                                    $key = (isset($spcfcf['cf_apikey']) ? $spcfcf['cf_apikey'] : false);
                                    if (!empty($token) && self::check_api()){
                                          swift3_update_option('enable-cloudflare', 'on');
                                          swift3_update_option('cloudflare-auth-mode', 'token');
                                          swift3_update_option('cloudflare-token', $token);
                                          return esc_html__('Cloudflare API token found', 'swift3');
                                    }
                                    else if (!empty($email) && !empty($key) && self::check_api()){
                                          swift3_update_option('enable-cloudflare', 'on');
                                          swift3_update_option('cloudflare-auth-mode', 'key');
                                          swift3_update_option('cloudflare-email', $email);
                                          swift3_update_option('cloudflare-key', $key);
                                          return esc_html__('Cloudflare API key found', 'swift3');
                                    }
                              }
                              $cloudflare_api_email = get_option('cloudflare_api_email');
                              $cloudflare_api_key = get_option('cloudflare_api_key');

                              if (!empty($cloudflare_api_email) && !empty($cloudflare_api_key)){
                                    if (strlen($cloudflare_api_key) === 37 && preg_match('/^[0-9a-f]+$/', $cloudflare_api_key)) {
                                          swift3_update_option('enable-cloudflare', 'on');
                                          swift3_update_option('cloudflare-auth-mode', 'key');
                                          swift3_update_option('cloudflare-email', $cloudflare_api_email);
                                          swift3_update_option('cloudflare-key', $cloudflare_api_key);
                                          return esc_html__('Cloudflare API key found', 'swift3');
                                    }
                                    else {
                                          swift3_update_option('enable-cloudflare', 'on');
                                          swift3_update_option('cloudflare-auth-mode', 'token');
                                          swift3_update_option('cloudflare-token', $cloudflare_api_key);
                                          return esc_html__('Cloudflare API token found', 'swift3');
                                    }
                              }

                              swift3_update_option('enable-cloudflare', '');
                              return esc_html__('Cloudflare API token not found. Please configure Cloudflare credentials.', 'swift3');
                        });
                  });
            }
            add_action('swift3_option_cloudflare-token_updated', array(__CLASS__, 'check_api'));
            add_action('swift3_option_cloudflare-email_updated', array(__CLASS__, 'check_api'));
            add_action('swift3_option_cloudflare-key_updated', array(__CLASS__, 'check_api'));
            add_action('swift3_option_cloudflare-proxy-cache_updated', array(__CLASS__, 'proxy_cache_updated'));
            if (swift3_check_option('enable-cloudflare', 'on')){
                  add_action('swift3_purge_cache', array(__CLASS__, 'purge'));
                  add_action('swift3_cache_done', array(__CLASS__, 'purge'));
                  add_action('swift3_development_mode_activated', array(__CLASS__, 'purge'));
            }
      }
      public static function settings_panel(){
            include 'settings.tpl.php';
      }
      public static function detected(){
            return apply_filters('swift3_cloudflare_detected', isset($_SERVER['HTTP_CF_RAY']));
      }
      public static function check_api(){
            $headers = self::get_headers();

            if (!empty($headers)){
                  Swift3_Logger::rlogs('cloudflare-api-error');

                  if (swift3_check_option('cloudflare-auth-mode', 'token')){
                        $response = wp_remote_get('https://api.cloudflare.com/client/v4/user/tokens/verify', array(
                              'headers' => $headers
                        ));
                  }
                  else {
                        $response = wp_remote_get('https://api.cloudflare.com/client/v4/user', array(
                              'headers' => $headers
                        ));
                  }
                  $decoded = self::parse_response($response);
                  if (isset($decoded['success']) && $decoded['success'] == true){
                        self::get_zone();
                  }
                  else if (isset($decoded['errors'])){
                        $error_messages = self::get_error_string($decoded);
                        Swift3_Logger::log(array('message' => sprintf(__('Cloudflare API error: %s', 'swift3'), $error_messages)), 'cloudflare-api-error', 'cloudflare-auth');
                        preg_match('~swift3_option_([\w\-]*)_updated~', current_action(), $current_action);
                        add_filter('swift3_ajax_update_' . $current_action[1] . '_option', function() use ($error_messages){
                              return array('result' => 'error', 'message' => sprintf(__('Cloudflare API error: %s', 'swift3'), $error_messages));
                        });
                  }
            }
      }

      public static function get_zone(){
            Swift3_Logger::rlog('cloudflare-connection-error');

            $response = wp_remote_get('https://api.cloudflare.com/client/v4/zones', array(
                  'headers' => self::get_headers()
            ));
            $decoded = self::parse_response($response);

            if (isset($decoded['result'])){
                  $host = parse_url(home_url(), PHP_URL_HOST);
                  foreach ($decoded['result'] as $zone){
                        if (strpos($host, $zone['name']) !== false){
                              Swift3_Logger::rlog('cloudflare-zone-not-found');
                              swift3_update_option('cloudflare-zone', $zone['id']);
                              return $zone['id'];
                        }
                  }
            }
            $api_auth = (swift3_check_option('cloudflare-auth-mode', 'token') ? __('API token', 'swift3') : __('e-mail and API key', 'swift3'));
            Swift3_Logger::log(array('message' => sprintf(__('Cloudflare zone not found. Please check Cloudflare %s and be sure that domain has been already added to Cloudflare.', 'swift3'), $api_auth)), 'cloudflare-api-error', 'cloudflare-zone-not-found');
      }
      public static function purge($url = NULL){
            $headers = self::get_headers();
            $zone = swift3_get_option('cloudflare-zone');

            Swift3_Logger::rlog('cloudflare-not-configured');
            if (empty($headers) || empty($zone)){
                  $api_auth = (swift3_check_option('cloudflare-auth-mode', 'token') ? __('API token', 'swift3') : __('e-mail and API key', 'swift3'));
                  Swift3_Logger::log(array('message' => sprintf(__('Cloudflare module is enabled, however it is not configured.', 'swift3'), $api_auth)), 'cloudflare-api-error', 'cloudflare-not-configured');
                  return;
            }

            if (empty($url)){
                  $body = '{"purge_everything":true}';
            }
            else {
                  $body = '{"files":' . json_encode((array)$url) . '}';
            }

            Swift3_Logger::rlog('cloudflare-connection-error');
            $response = wp_remote_request('https://api.cloudflare.com/client/v4/zones/' . $zone . '/purge_cache', array(
                  'method' => 'DELETE',
                  'body' => $body,
                  'headers' => $headers
            ));

            $decoded = self::parse_response($response);

            Swift3_Logger::rlog('cloudflare-purge-error');
            if (!isset($decoded['success']) || $decoded['success'] != 'true'){
                  if (!isset($decoded['success']) || $decoded['success'] != 'true'){
                        Swift3_Logger::log(array('message' => sprintf(__('Cloudflare purge failed. Error: %s', 'swift3'), self::get_error_string($decoded))), 'cloudflare-api-error', 'cloudflare-purge-error');
                  }
            }
      }

      public static function proxy_cache_updated(){
            if (swift3_check_option('cloudflare-proxy-cache', 'on')){
                  add_filter('swift3_apache_cache_rewrites', '__return_false');
            }
            else {
                  remove_filter('swift3_apache_cache_rewrites', '__return_false');
            }
            Swift3_Config::generate_rewrites();
            $headers = self::get_headers();
            $zone = swift3_get_option('cloudflare-zone');

            if (!empty($headers) && !empty($zone)){
                  Swift3_Logger::rlog('cloudflare-connection-error');
                  $response = wp_remote_request('https://api.cloudflare.com/client/v4/zones/' . $zone . '/pagerules', array(
                        'headers' => $headers
                  ));

                  $page_rules = self::parse_response($response);

                  $rule_ids = array();
                  $home = Swift3_Helper::get_home_host();
                  foreach ($page_rules['result'] as $page_rule){
                        if ($page_rule['actions'][0]['id'] == 'cache_level'){
                              foreach ($page_rule['targets'] as $target){
                                    if ($target['target'] == 'url' && $target['constraint']['operator'] == 'matches' && ($target['constraint']['value'] == '*.' . $home . '*' || $target['constraint']['value'] == '*.' . $home . 'wp-admin/*') && $page_rule['status'] == 'active'){
                                          $rule_ids[] = $page_rule['id'];
                                          break;
                                    }
                              }
                        }
                  }

                  if (swift3_check_option('cloudflare-proxy-cache', 'on')){
                        if (empty($rule_id)){
                              $response = wp_remote_request('https://api.cloudflare.com/client/v4/zones/' . $zone . '/pagerules/quota', array(
                                    'headers' => $headers
                              ));

                              $quota = self::parse_response($response);
                              Swift3_Logger::rlog('cloudflare-quota-error');
                              if (isset($quota['result']['quota']) && $quota['result']['quota'] > count($page_rules['result'])){
                                    Swift3_Logger::rlog('cloudflare-pagerule-error');
                                    foreach (array(array('url' => $home, 'cache_level' => 'cache_everything'), array('url' => $home . 'wp-admin/', 'cache_level' => 'bypass')) as $target){
                                          $response = wp_remote_request('https://api.cloudflare.com/client/v4/zones/' . $zone . '/pagerules', array(
                                                'method' => 'POST',
                                                'body' => '{"actions": [{"id": "cache_level", "value": "'.$target['cache_level'].'"}], "priority": 2, "status": "active", "targets": [{"target": "url", "constraint": {"operator": "matches", "value": "*.'.$target['url'].'*"}}]}',
                                                'headers' => $headers
                                          ));
                                          $create_page_rule = self::parse_response($response);

                                          if (!isset($create_page_rule['success']) || $create_page_rule['success'] != true){
                                                Swift3_Logger::log(array('message' => sprintf(__('Creating Cloudflare page rule failed. Error: %s', 'swift3'), self::get_error_string($create_page_rule))), 'cloudflare-api-error', 'cloudflare-pagerule-error');
                                          }
                                    }
                              }
                              else {
                                    Swift3_Logger::log(array('message' => __('Cloudflare page rule quota exceeded. Delete unused page rules, increase quota, or disable Cloudflare Proxy Caching in Swift Performance settings.', 'swift3')), 'cloudflare-api-error', 'cloudflare-quota-error');
                              }
                        }

                  }
                  else {
                        if (!empty($rule_ids)){
                              foreach ($rule_ids as $rule_id){
                                    $response = wp_remote_request('https://api.cloudflare.com/client/v4/zones/' . $zone . '/pagerules/' . $rule_id, array(
                                          'method' => 'DELETE',
                                          'headers' => $headers
                                    ));
                                    $decoded = self::parse_response($response);
                                    if (!isset($decoded['success']) || $decoded['success'] != true){
                                          Swift3_Logger::log(array('message' => sprintf(__('Removing Cloudflare page rule failed. Error: %s', 'swift3'), self::get_error_string($decoded))), 'cloudflare-api-error', 'cloudflare-pagerule-error');
                                    }
                              }
                        }
                  }
            }
      }

      public static function get_headers(){
            if (swift3_check_option('cloudflare-auth-mode', 'token') && swift3_check_option('cloudflare-token', '', '!=')){
                  return array(
                        'Authorization' => 'Bearer ' . swift3_get_option('cloudflare-token'),
                        'Content-Type' => 'application/json'
                  );
            }
            else if (swift3_check_option('cloudflare-auth-mode', 'key') && swift3_check_option('cloudflare-email', '', '!=') && swift3_check_option('cloudflare-key', '', '!=')){
                  return array(
                        'X-Auth-Email' => swift3_get_option('cloudflare-email'),
                        'X-Auth-Key' => swift3_get_option('cloudflare-key'),
                        'Content-Type' => 'application/json'
                  );
            }

            return array();
      }

      public static function parse_response($response){
            if (is_wp_error($response)){
                  Swift3_Logger::log(array('message' => sprintf(__('Cloudflare API connection failed. Error: %s', 'swift3'), $response->get_error_message())), 'cloudflare-api-error', 'cloudflare-connection-error');
                  return false;
            }

            return json_decode($response['body'], true);
      }

      public static function get_error_string($decoded){
            if (isset($decoded['errors'])){
                  $error_messages = array();
                  foreach ($decoded['errors'] as $error){
                        $error_messages[] = $error['message'] . sprintf(__(' (code: %s)', 'swift3'), $error['code']);
                  }
                  if (!empty($decoded['messages'])){
                        foreach ($decoded['messages'] as $message){
                              $error_messages[] = $message['message'];
                        }
                  }
                  return implode(',', $error_messages);
            }

            return sprintf(__('Unknown error: %s', 'swift3'), json_encode($decoded));
      }

      public static function maybe_deactivate(){
            $plugin_files = array();
            if (defined('CLOUDFLARE_PLUGIN_DIR')){
                  $plugin_files[] = Swift3_Overlap_Plugins_Module::locate(trailingslashit(CLOUDFLARE_PLUGIN_DIR) . 'cloudflare.php', 'version_compare(PHP_VERSION, CLOUDFLARE_MIN_PHP_VERSION');

                  Swift3_Logger::log(array('plugin' => __('Cloudflare Plugin', 'swift3')), 'plugin-deactivated', 'cloudflare');
            }

            if (class_exists('SW_CLOUDFLARE_PAGECACHE')){
                  $plugin_files[] = Swift3_Overlap_Plugins_Module::locate(trailingslashit(SWCFPC_PLUGIN_PATH) . 'wp-cloudflare-super-page-cache.php', 'Super Page Cache for Cloudflare');

                  Swift3_Logger::log(array('plugin' => __('Super Page Cache for Cloudflare', 'swift3')), 'plugin-deactivated', 'swcfpc');
            }

            if (!empty($plugin_files)){
                  deactivate_plugins($plugin_files);
            }
      }

}

Swift3_Cloudflare_Module::load();