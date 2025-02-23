<?php

class Swift3_Daemon {

      public $connections = 0;

      public static $cache_start;

      public const DAEMONS = array('cache', 'image', 'optimize');

      public function __construct(){
            if (Swift3_Config::$is_development_mode){
                  return;
            }

            if (is_admin()){
                  add_action('plugins_loaded', array($this, 'schedule'));
            }
            else {
                  add_action('template_redirect', array($this, 'schedule'));
            }
            add_action('swift3_rest_action_daemon/cache', function(){
                  $this->run('cache');
            });
            add_action('swift3_rest_action_daemon/image', function(){
                  $this->run('image');
            });
            add_action('swift3_rest_action_daemon/optimize', function(){
                  $this->run('optimize');
            });
      }

      public function schedule(){
            $cycle = (int)get_transient('swift3_daemon');
            $schedule = false;

            if ($cycle + 5 < time()){
                  set_transient('swift3_daemon', time(), 15);
                  $cache_status = Swift3_Warmup::get_cache_status();
                  if (!self::is_locked('cache') && ($cache_status->revisit) > 0){
                        wp_remote_post(Swift3_REST::get_url('daemon/cache'), array(
                              'timeout'   => 0.01,
                              'blocking' => false,
                              'sslverify' => false,
                              'user-agent' => Swift3_Helper::ua_string('daemon'),
                        ));

                  }
                  $image_queue = (array)get_option('swift3_image_queue');
                  if (!self::is_locked('image') && !empty($image_queue)){
                        wp_remote_post(Swift3_REST::get_url('daemon/image'), array(
                              'timeout'   => 0.01,
                              'blocking' => false,
                              'sslverify' => false,
                              'user-agent' => Swift3_Helper::ua_string('daemon'),
                        ));
                  }
                  if (!self::is_locked('optimize') && $cache_status->queued > 0){
                        wp_remote_post(Swift3_REST::get_url('daemon/optimize'), array(
                              'timeout'   => 0.01,
                              'blocking' => false,
                              'sslverify' => false,
                              'user-agent' => Swift3_Helper::ua_string('daemon'),
                        ));
                  }
            }
      }

      public function run($daemon){
            if (!empty($daemon)){
                  $this->lock($daemon);
                  switch ($daemon){
                        case 'cache':
                              self::$cache_start = time();
                              self::cache_next();
                              self::optimize_next();
                              break;
                        case 'image':
                              Swift3_Image::optimize_queue();
                              break;
                        case 'optimize':
                              $maybe_url = (isset($_REQUEST['url']) ? $_REQUEST['url'] : false);
                              self::optimize_next($maybe_url);

                              break;
                  }
                  $this->unlock($daemon);
                  die;
            }
      }
      public function lock($daemon){
            if (!in_array($daemon, self::DAEMONS)){
                  return;
            }

            $lock = (int)get_transient('swift3_daemon_' . $daemon . '_lock');
            if ($lock + 40 > time()){
                  die;
            }

            set_transient('swift3_daemon_' . $daemon . '_lock', time(), 40);
      }
      public function is_locked($daemon){
            if (!in_array($daemon, self::DAEMONS)){
                  return false;
            }

            $lock = (int)get_transient('swift3_daemon_' . $daemon . '_lock');
            if ($lock + 40 > time()){
                  return true;
            }

            return false;
      }
      public function unlock($daemon){
            if (!in_array($daemon, self::DAEMONS)){
                  return;
            }

            delete_transient('swift3_daemon_' . $daemon . '_lock');
      }
      public static function unlock_all(){
            foreach(self::DAEMONS as $daemon){
                  delete_transient('swift3_daemon_' . $daemon . '_lock');
            }
      }
      public static function get_next_url($context = 'cache'){
            switch ($context) {
                  case 'cache':
                        $expiry = time() - SWIFT3_CACHE_LIFESPAN;
                        return Swift3_Helper::$db->get_var(Swift3_Helper::$db->prepare("SELECT url FROM " . Swift3_Helper::$db->swift3_warmup . " WHERE status < 1 OR cts < %d ORDER BY ppts DESC, priority ASC LIMIT 1", $expiry));
                        break;
                  case 'optimize':
                        return Swift3_Helper::$db->get_var("SELECT url FROM " . Swift3_Helper::$db->swift3_warmup . " WHERE status = 2 ORDER BY ppts DESC, priority ASC LIMIT 1");
                        break;

            }
      }
      public static function cache_next($url = NULL){
            if (empty($url)){
                  $url = self::get_next_url('cache');
                  if (empty($url)){
                        return;
                  }
            }

            $response = call_user_func_array(apply_filters('swift3_cache_hit_function', array(__CLASS__, 'cache_hit')), array($url));

            if (is_wp_error($response)){
                  Swift3_Logger::load();
                  Swift3_Logger::log(array('error' => $response->get_error_message(), 'url' => $url), 'prebuild-failed', 'prebuild-failed_' . $url);
            }
            else if (!empty($response) && isset($response['http_response'])){
                  $response_object = $response['http_response']->get_response_object();
                  if ($response['response']['code'] == 404 || (isset($response_object->redirects) && !empty($response_object->redirects))){
                        Swift3_Warmup::delete_url($url);
                  }
                  else if ($response['response']['code'] == 403){
                        Swift3_Logger::log(array('error' => 'HTTP ' . $response['response']['code'], 'url' => $url), 'prebuild-failed', 'prebuild-failed_' . $url);
                        Swift3_Warmup::update_url($url, array('status' => 3, 'cts' => (time() + SWIFT3_CACHE_LIFESPAN - 900)));
                  }
                  else if (in_array($response['response']['code'], array(500, 502, 503, 504, 508))){
                        Swift3_Logger::log(array('error' => 'HTTP ' . $response['response']['code'], 'url' => $url), 'prebuild-failed', 'prebuild-failed_' . $url);
                        Swift3_Warmup::update_url($url, array('status' => -1, 'cts' => 0));
                        if (in_array($response['response']['code'], array(502, 503, 504))){
                              if (strpos($response['body'], 'window._cf_chl_opt') !== false){
                                    Swift3_Logger::log(array('error' => 'HTTP ' . $response['response']['code'] . ' ' . __('it seems Cloudflare challange is blocking prebuild', 'swift3'), 'url' => $url), 'prebuild-failed', 'prebuild-failed_' . $url);
                              }
                              return false;
                        }
                  }
                  else {
                        do_action('swift3_cache_done', $url);
                        Swift3_Logger::rlog('prebuild-failed_' . $url);
                  }

            }
            if (SWIFT3_PREBUILD_INTERMISSION >= 1000000){
                  sleep(round(SWIFT3_PREBUILD_INTERMISSION/1000000));
            }
            else {
                  usleep(SWIFT3_PREBUILD_INTERMISSION);
            }

            if (time() - self::$cache_start < 30){
                  self::cache_next();
            }
      }
      public static function cache_hit($url){
            if (swift3_check_option('remote-prebuild', 'on') && swift3_check_option('api_connection', 'duplex')){
                  $url = SWIFT3_API_URL . 'prebuild/' . $url;
            }

            return wp_remote_post($url, array(
                  'timeout'   => 30,
                  'sslverify' => false,
                  'user-agent' => Swift3_Helper::ua_string('cache-hit'),
                  'headers' => array('X-PREBUILD' => 1)
            ));
      }
      public static function optimize_next($url = false){
            if (empty($url)){
                  $url = self::get_next_url('optimize');
            }

            if (!empty($url)){
                  $now = time();
                  $current_data = Swift3_Warmup::get_data($url);
                  if (in_array($current_data->status, array(-3,3))){
                        self::optimize_next();
                        return;
                  }

                  $response = Swift3::get_module('api')->request('optimize', array('url' => $url, 'config' => array(
                        'optimize_css'          => (int)swift3_get_option('optimize-css'),
                        'optimize_js'           => swift3_check_option('optimize-js', 'on'),
                        'js_delivery'           => (int)swift3_get_option('js-delivery'),
                        'optimize_images'       => swift3_check_option('optimize-images', 'on'),
                        'optimize_iframes'      => (int)swift3_get_option('optimize-iframes'),
                        'lazyload_images'       => swift3_check_option('lazyload-images', 'on'),
                        'responsive_images'     => swift3_check_option('responsive-images', 'on'),
                        'optimize_rendering'    => swift3_check_option('optimize-rendering', 'on'),
                        'pingback'              => Swift3_REST::get_url('daemon/optimize')
                  )));

                  if (is_wp_error($response)){
                        Swift3_Logger::log(array('error' => $response->get_error_message()), 'api-error', 'api-network-error');
                  }
                  else {
                        $decoded = json_decode($response['body']);
                        if (!empty($decoded)){
                              if (isset($decoded->error)){
                                    Swift3_Logger::log(array('error' => $decoded->error, 'url' => $url), 'optimization-failed', 'optimization-failed_' . $url);
                                    Swift3_Warmup::update_url($url, array('status' => '3'));
                                    self::optimize_next();
                              }
                              else {
                                    Swift3_Logger::rlog('optimization-failed_' . $url);

                                    $dir = Swift3_Optimizer::get_data_dir($url);
                                    if (isset($decoded->critical_css)){
                                          $critical_fonts = array();
                                          if (isset($decoded->critical_fonts) && !empty($decoded->critical_fonts)){
                                                $decoded->data->critical_fonts = array();
                                                foreach ((array)$decoded->critical_fonts as $critical_font_url => $critical_font_data){
                                                      $filename = preg_replace('~\.(ttf|woff2?|eot|svg)(\?.*)?$~', hash('crc32', $critical_font_data) . '.woff', basename($critical_font_url));
                                                      file_put_contents($dir . $filename, base64_decode($critical_font_data));
                                                      $critical_fonts[$critical_font_url] = Swift3_Optimizer::get_data_url($dir . $filename);
                                                      $decoded->data->critical_fonts[] = Swift3_Optimizer::get_data_url($dir . $filename);
                                                }
                                          }
                                          $shrinked_fonts = array();
                                          if (isset($decoded->shrinked_fonts) && !empty($decoded->shrinked_fonts)){
                                                $decoded->data->shrinked_fonts = array();
                                                foreach ((array)$decoded->shrinked_fonts as $shrinked_font_url => $shrinked_font_data){
                                                      $filename = preg_replace('~\.(ttf|woff2?|eot|svg)(\?.*)?$~', hash('crc32', json_encode($shrinked_font_data)) . '.woff', basename($shrinked_font_url));
                                                      file_put_contents($dir . $filename, base64_decode($shrinked_font_data->data));

                                                      $decoded->data->shrinked_fonts[] = str_replace($shrinked_font_url, Swift3_Optimizer::get_data_url($dir . $filename), $shrinked_font_data->css);
                                                }
                                          }
                                          if (isset($decoded->critical_css->desktop) && !empty($decoded->critical_css->desktop)){
                                                if (!empty($critical_fonts)){
                                                      $decoded->critical_css->desktop = str_replace(array_keys($critical_fonts), $critical_fonts, $decoded->critical_css->desktop);
                                                }

                                                $decoded->critical_css->desktop .= 'html{opacity:1}';

                                                file_put_contents($dir . 'critical.css', Swift3_Helper::remove_mixed_urls($decoded->critical_css->desktop));

                                                if (isset($decoded->critical_css->mobile)){
                                                      if (!empty($critical_fonts)){
                                                            $decoded->critical_css->mobile = str_replace(array_keys($critical_fonts), $critical_fonts, $decoded->critical_css->mobile);
                                                      }

                                                      $decoded->critical_css->mobile .= 'html{opacity:1}';

                                                      file_put_contents($dir . 'critical-mobile.css', Swift3_Helper::remove_mixed_urls($decoded->critical_css->mobile));
                                                }
                                                else {
                                                      file_put_contents($dir . 'critical-mobile.css', Swift3_Helper::remove_mixed_urls($decoded->critical_css->desktop));
                                                }
                                          }

                                          if (!empty($decoded->data->collected_styles)){
                                                foreach ((array)$decoded->data->collected_styles as $key => $value){
                                                      $decoded->data->collected_styles[$key] = preg_replace('~\?ver=([0-9\.]+)$~', '', $value);
                                                }
                                          }
                                    }
                                    if (!empty($decoded->webp_css)){
                                          $webp_css = '';
                                          foreach ($decoded->webp_css as $css){
                                                $webp_css .= (strpos($css->selector, 'html ') !== 0 ? 'html ' : '') . $css->selector . $css->text;
                                          }

                                          if (!empty($decoded->responsive_webp_css)){
                                                foreach ($decoded->responsive_webp_css as $conditionText => $rules){
                                                      $webp_css .= '@media ' . $conditionText . '{';
                                                      foreach ($rules as $css){
                                                            $webp_css .= (strpos($css->selector, 'html ') !== 0 ? 'html ' : '') . $css->selector . $css->text;
                                                      }
                                                      $webp_css .= '}';
                                                }
                                          }

                                          file_put_contents($dir . 'webp.css', $webp_css);
                                    }
                                    if (isset($decoded->oversized_images) && !empty($decoded->oversized_images)){
                                          foreach ($decoded->oversized_images as $src => $devices){
                                                foreach ($devices as $device => $oversize_image){
                                                      if (isset($oversize_image->sized) && !empty($oversize_image->sized)){
                                                            // @1x images
                                                            $image_path = md5($oversize_image->sized) . '.webp';
                                                            file_put_contents($dir . $image_path, base64_decode($oversize_image->sized));
                                                            unset($decoded->oversized_images->{$src}->{$device}->sized);
                                                            $decoded->oversized_images->{$src}->{$device}->image = $image_path;

                                                            // @2x images
                                                            if (isset($oversize_image->sized2x) && !empty($oversize_image->sized2x)){
                                                                  $image_path = md5($oversize_image->sized2x) . '.webp';
                                                                  file_put_contents($dir . $image_path, base64_decode($oversize_image->sized2x));
                                                                  unset($decoded->oversized_images->{$src}->{$device}->sized2x);
                                                                  $decoded->oversized_images->{$src}->{$device}->image2x = $image_path;
                                                            }
                                                      }
                                                }
                                          }

                                          if (!empty($decoded->oversized_images)){
                                                file_put_contents($dir . 'oversized.json', json_encode($decoded->oversized_images));
                                          }
                                    }
                                    if (isset($decoded->iframe_placeholders) && !empty($decoded->iframe_placeholders)){
                                          $placeholders = array();
                                          foreach ($decoded->iframe_placeholders as $src => $devices){
                                                foreach ($devices as $device => $placeholder){
                                                      if (!empty($placeholder)){
                                                            $image_path = md5($placeholder) . '.webp';
                                                            file_put_contents($dir . $image_path, base64_decode($placeholder));

                                                            $placeholders[$src][$device] = Swift3_Optimizer::get_data_url($dir . $image_path);
                                                      }
                                                }
                                          }
                                          unset($decoded->iframe_placeholders);
                                          if (!empty($placeholders)){
                                                file_put_contents($dir . 'placeholders.json', json_encode($placeholders));
                                          }
                                    }
                                    if (isset($decoded->lazyelement_placeholders) && !empty($decoded->lazyelement_placeholders)){
                                          $placeholders = array();
                                          foreach ($decoded->lazyelement_placeholders as $id => $devices){
                                                foreach ($devices as $device => $placeholder){
                                                      if (!empty($placeholder)){
                                                            $image_path = md5($placeholder) . '.webp';
                                                            file_put_contents($dir . $image_path, base64_decode($placeholder));

                                                            $placeholders[$id][$device] = Swift3_Optimizer::get_data_url($dir . $image_path);
                                                      }
                                                }
                                          }
                                          unset($decoded->lazyelement_placeholders);
                                          if (!empty($placeholders)){
                                                file_put_contents($dir . 'lazy-elements.json', json_encode($placeholders));
                                          }
                                    }
                                    if (isset($decoded->script_data) && !empty($decoded->script_data)){
                                          $scripts = array();
                                          foreach ($decoded->script_data as $src){
                                                if (apply_filters('swift3_skip_cors', false, $src)){
                                                      continue;
                                                }

                                                $data = wp_remote_get($src, array(
                                                      'headers' => array(
                                                            'Referer' => site_url()
                                                      )
                                                ));
                                                if (!is_wp_error($data) && !empty($data['body'])){
                                                      $script_path = md5($data['body']) . '.js';
                                                      $script_source = $data['body'];

                                                      file_put_contents($dir . $script_path, $script_source);

                                                      $scripts[$src] = Swift3_Optimizer::get_data_url($dir . $script_path);
                                                }
                                          }
                                          unset($decoded->script_data);
                                          file_put_contents($dir . 'cors.json', json_encode($scripts));
                                    }
                                    if (isset($decoded->data)){
                                          $decoded->data->time = $decoded->time;
                                          $decoded->data->server = $decoded->server;
                                          file_put_contents($dir . 'data.json', json_encode($decoded->data));
                                    }


                                    if ($current_data->status == 2 && file_exists($dir . 'data.json')){
                                          Swift3_Warmup::update_url($url, array('status' => '-3'));
                                          do_action('swift3_transient_status', 2, -3, $url);
                                          self::cache_next($url);
                                          self::optimize_next();
                                    }

                              }
                        }

                  }
            }
      }

}

?>