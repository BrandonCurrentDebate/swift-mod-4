<?php

class Swift3_REST {
      public function __construct(){
            add_action('wp', function(){
                  $path = parse_url(trailingslashit(site_url()), PHP_URL_PATH);

                  if (isset($_SERVER['REQUEST_URI']) && preg_match('~^' . $path . 'swift-ai_rest/(.*)~', $_SERVER['REQUEST_URI'], $rest_route)){
                        Swift3_REST::route($rest_route[1]);
                  }
            }, -PHP_INT_MAX);
      }
      public static function route($path){
            if (has_action('swift3_rest_action_' . $path)){
                  status_header(200);
                  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                  do_action('swift3_rest_action_' . $path);
            }
            die;
      }
      public static function get_url($endpoint = ''){
            return site_url('/swift-ai_rest/' . $endpoint);
      }
}

?>