<?php

class Swift3_Collage {

      public function __construct(){
            if (swift3_check_option('logged-in-cache', 'on')){
                  add_action('send_headers', array(__CLASS__, 'lic'));
            }
            if (Swift3_Helper::is_prebuild() && swift3_check_option('logged-in-cache', 'on')){
                  add_action('wp_footer', array(__CLASS__, 'admin_bar_wrapper'));
            }
      }

      public static function load($data){
            do_action('swift3_collage_before_' . $data[2], $data);
            switch ($data[2]){
                  case 'admin_bar':
                        $result = self::admin_bar($data);
                        break;
                  default:
                        $result = '';
            }

            return apply_filters('swift3_collage_result_' . $data[2], $result, $data);
      }

      public static function admin_bar($data){
            global $wp_the_query, $wp_query, $post_id, $post, $wp_scripts, $wp_styles;
            if (isset($_SERVER['HTTP_REFERER'])){
                  $host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
                  $_SERVER['REQUEST_URI'] = preg_replace('~https?://' .$host . '~', '', $_SERVER['HTTP_REFERER']);
            }
            else {
                  $_SERVER['REQUEST_URI'] = home_url();
            }

            $wp_query = new WP_Query(array('p' => $data[1]));
            $post_id = $data[1];
            $post = get_post($data[1]);

            $wp_query->queried_object = $post;
            $wp_query->queried_object_id = $data[1];

            $wp_the_query = $wp_query;
            ob_start();
            _wp_admin_bar_init();
            wp_admin_bar_render();

            $content = ob_get_clean();
            do_action('wp_enqueue_scripts');

            wp_enqueue_style('dashicons');

            do_action('swift3_before_collage_admin_bar_assets');
            $assets = array();
            foreach( $wp_scripts->queue as $handle){
                  if (isset($wp_scripts->registered[$handle]->src) && !empty($wp_scripts->registered[$handle]->src)){
                        $assets = self::get_deps($assets, $wp_scripts->registered[$handle]->deps, 'script');
                        $src = (isset($wp_scripts->registered[$handle]->ver) && !empty($wp_scripts->registered[$handle]->ver) ? add_query_arg('ver', $wp_scripts->registered[$handle]->ver, $wp_scripts->registered[$handle]->src) : $wp_scripts->registered[$handle]->src);
                        $asset = array('type'=>'script', 'src' => $src);

                        if (isset($wp_scripts->registered[$handle]->extra)){
                              if (isset($wp_scripts->registered[$handle]->extra['before'])){
                                    $asset['before'] = array_pop($wp_scripts->registered[$handle]->extra['before']);
                              }
                              if (isset($wp_scripts->registered[$handle]->extra['data'])){
                                    $asset['data'] = $wp_scripts->registered[$handle]->extra['data'];
                              }
                        }
                        $assets[] = $asset;
                  }
            }

            // Add styles
            foreach( $wp_styles->queue as $handle ){
                  if (isset($wp_styles->registered[$handle]->src) && !empty($wp_styles->registered[$handle]->src)){
                        $assets = self::get_deps($assets, $wp_styles->registered[$handle]->deps, 'style');
                        $src = (isset($wp_styles->registered[$handle]->ver) && !empty($wp_styles->registered[$handle]->ver) ? add_query_arg('ver', $wp_styles->registered[$handle]->ver, $wp_styles->registered[$handle]->src) : $wp_styles->registered[$handle]->src);
                        $assets[] = array('type'=>'style', 'src' => $src);
                  }
            }

            return array(
                  'html' => $content,
                  'assets' => $assets
            );
      }

      public static function get_deps($assets, $deps, $type){
            switch ($type) {
                  case 'script':
                        global $wp_scripts;
                        foreach ($deps as $handle){
                              if (isset($wp_scripts->registered[$handle])){
                                    if (!empty($wp_scripts->registered[$handle]->deps)){
                                          $assets = self::get_deps($assets, $wp_scripts->registered[$handle]->deps, $type);
                                    }
                              }
                              if (isset($wp_scripts->registered[$handle]->src) && !empty($wp_scripts->registered[$handle]->src)){
                                    $src = (isset($wp_scripts->registered[$handle]->ver) && !empty($wp_scripts->registered[$handle]->ver) ? add_query_arg('ver', $wp_scripts->registered[$handle]->ver, $wp_scripts->registered[$handle]->src) : $wp_scripts->registered[$handle]->src);
                                    $asset = array('type'=>'script', 'src' => $src);
                                    if (isset($wp_scripts->registered[$handle]->extra)){
                                          if (isset($wp_scripts->registered[$handle]->extra['before'])){
                                                $asset['before'] = array_pop($wp_scripts->registered[$handle]->extra['before']);
                                          }
                                          if (isset($wp_scripts->registered[$handle]->extra['data'])){
                                                $asset['data'] = $wp_scripts->registered[$handle]->extra['data'];
                                          }
                                    }

                                    $assets[] = $asset;
                              }
                        }
                        break;
                  case 'style':
                        global $wp_styles;
                        foreach ($deps as $handle){
                              if (isset($wp_styles->registered[$handle])){
                                    if (!empty($wp_styles->registered[$handle]->deps)){
                                          $assets = self::get_deps($assets, $wp_styles->registered[$handle]->deps, $type);
                                    }
                              }
                              if (isset($wp_styles->registered[$handle]->src) && !empty($wp_styles->registered[$handle]->src)){
                                    $src = (isset($wp_styles->registered[$handle]->ver) && !empty($wp_styles->registered[$handle]->ver) ? add_query_arg('ver', $wp_styles->registered[$handle]->ver, $wp_styles->registered[$handle]->src) : $wp_styles->registered[$handle]->src);
                                    $assets[] = array('type'=>'style', 'src' => $src);
                              }
                        }
                        break;
            }

            return $assets;
      }
      public static function admin_bar_wrapper(){
            global $wp_the_query;

            $request = base64_encode(json_encode(apply_filters('swift3_collage_admin_bar_request', array('collage', $wp_the_query->queried_object_id, 'admin_bar'))));
            echo '<div id="'.hash('crc32', $request).'" class="swift3-fragment" data-condition="lio" data-request="'.$request.'"></div>';
            echo '<script>if (document.cookie.match(\'s3lic=true\')){var s=document.createElement(\'style\');s.innerHTML=\'html{margin-top:46px!important;background:#1d2327;}@media screen and (min-width:783px){html{margin-top:32px!important;}}\';document.head.append(s)}</script>';
      }
      public static function lic(){
            if (is_user_logged_in() && is_admin_bar_showing()){
                  setcookie('s3lic', 'true', 0, '/', parse_url(home_url(), PHP_URL_HOST), true, false);
            }
            else if (isset($_COOKIE['s3lic'])){
                  setcookie('s3lic', 'false', 0, '/', parse_url(home_url(), PHP_URL_HOST), true, false);
            }
      }
}

return new Swift3_Collage();

?>