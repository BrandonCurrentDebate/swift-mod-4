<?php

class Swift3_Braintree_Module {

      public function __construct(){
            add_filter('swift3_skip_js_optimization', function($result, $tag){
                  if (self::detected() && function_exists('is_checkout') && is_checkout()){
                        if (isset($tag->attributes['src']) && preg_match('~js\.braintreegateway\.com|jquery~', $tag->attributes['src'])){
                              $result = true;
                        }
                  }
                  return $result;
            }, 10, 2);

            add_filter('swift3_config_get_json', function($config){
                  if (self::detected() && function_exists('is_checkout') && is_checkout()){
                        $config['disable_lazy_nodes'] = true;
                  }

                  return $config;
            });
      }

      public static function detected(){
            return defined('WC_PAYPAL_BRAINTREE_FILE');
      }

}

new Swift3_Braintree_Module();