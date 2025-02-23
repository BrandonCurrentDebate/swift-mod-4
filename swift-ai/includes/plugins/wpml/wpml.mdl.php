<?php

class Swift3_WPML_Module {

      public $languages = array();

      public function __construct(){
            add_action('init', array($this, 'init'), 9);
      }

      public function init(){
            if (self::detected()){
                  Swift3_System::register_includes('wpml');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('WPML detected', 'swift3'));
                  });

                  $this->languages = icl_get_languages('skip_missing=0&orderby=KEY&order=DIR&link_empty_to=str');
                  add_action('swift3_warmup_add_url', array($this, 'add_url'), 10, 3);

                  add_filter('swift3_http_request_cache_url', array(__CLASS__, 'http_request_cache_url'));
            }
      }

      public function add_url($permalink, $priority, $post_id){
            if (!empty($post_id)){
                  global $sitepress;
                  if (!empty($sitepress)){
                        foreach ($this->languages as $language){
                              $sitepress->switch_lang($language['code'], true);
                              wp_cache_flush();
                              $url = get_permalink($post_id);

                              if ($url !== $permalink){
                                    $id = Swift3_Warmup::get_id($url);
                                    $standardized_query = Swift3_Helper::standardize_query(parse_url($url, PHP_URL_QUERY));

                                    if (empty($standardized_query) && !Swift3_Exclusions::is_excluded($url) && (!isset(Swift3_Warmup::$urls[$id]) || $priority < Swift3_Warmup::$urls[$id]['priority'])){
                                          Swift3_Warmup::$urls[$id] = array(
                                                'url' => $url,
                                                'priority' => $priority
                                          );
                                    }
                              }
                        }
                  }
            }
      }
      public static function http_request_cache_url($url){
            if (preg_match('~ate\.wpml\.org~', $url)){
                  $url = preg_replace('~(token|signature)=([^&]+)~', '', $url);
            }
            return $url;
      }

      public static function detected(){
            return function_exists('icl_get_languages');
      }
}

new Swift3_WPML_Module();