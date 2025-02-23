<?php

class Swift3_Fragments {

      public function __construct(){
            if (!self::should_fragment()){
                  return;
            }
            add_action('swift3_rest_action_fragments', array(__CLASS__, 'fragment_loader'));

            add_action('wp_head', function(){
                  echo '<style>.swift3-fragment{opacity:0!important;}.swift3-fragment-block{display:block;}</style>';
            }, 7);
            add_action('swift3_purge_cache', function($url){
                  if (empty($url)){
                        self::delete_buffer();
                  }
            });

            add_filter('render_block', array(__CLASS__, 'block_fragment_wrapper'),10, 2);
            add_filter('do_shortcode_tag', array(__CLASS__, 'shortcode_fragment_wrapper'), 10, 3);
            add_action('init', function(){
                  if (defined('REST_REQUEST') && isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp/v2/block-renderer/') !== false){
                        if (isset($_GET['attributes']['isSwiftPerfrormanceLazyloaded'])){
                              unset($_GET['attributes']['isSwiftPerfrormanceLazyloaded']);
                        }
                  }
            });
            add_action('enqueue_block_editor_assets', function() {
              wp_enqueue_script('swift3-gutenberg-fragments', SWIFT3_URL . '/assets/js/gutenberg-filter.js', array('wp-edit-post'));
            }, PHP_INT_MAX);
            if (swift3_check_option('collage', 'on')){
                  new Swift3_Collage();
            }
      }
      public static function block_fragment_wrapper($content, $block){
            if (isset($block['attrs']['isSwiftPerfrormanceLazyloaded']) && $block['attrs']['isSwiftPerfrormanceLazyloaded'] && !is_admin() && !Swift3_Helper::check_constant('DOING_FRAGMENTS') && !defined('REST_REQUEST') && apply_filters('swift3_gutenberg_block_fragment', true, $block)){
                  $request = base64_encode(json_encode(array('block', get_the_ID(), self::set_buffer($block))));
                  return '<span id="s3-'.hash('crc32', $request).'" class="swift3-fragment' . (Swift3_Helper::maybe_block_element($content) ? ' swift3-fragment-block' : '') . '" data-request="' . $request . '">' . $content . '</span>';
            }
            return $content;
      }
      public static function block_fragment($data){
            if (!empty($data[1])){
                  global $post;
                  $post = get_post($data[1]);
            }

            $block_data = self::get_buffer($data[2]);
            $block      = new WP_Block($block_data);
            return do_shortcode($block->render());
      }
      public static function shortcode_fragment_wrapper($content, $shortcode, $atts){
            $shortcode_fragments = (array)swift3_get_option('shortcode-fragments');

            if (!is_admin() && !Swift3_Helper::check_constant('DOING_FRAGMENTS') && !defined('REST_REQUEST') && in_array($shortcode, $shortcode_fragments) && apply_filters('swift3_shortcode_fragment', true, $shortcode, $atts)){
                  $request = base64_encode(json_encode(array('shortcode', get_the_ID(), self::set_buffer(array($shortcode, $atts)))));
                  return '<span id="s3-'.hash('crc32', $request).'" class="swift3-fragment' . (Swift3_Helper::maybe_block_element($content) ? ' swift3-fragment-block' : '') . '" data-request="'.$request.'">'.$content.'</span>';
            }
            return $content;
      }
      public static function shortcode_fragment($data){
            do_action('swift3_before_shortcode_fragment_callback', $data);
            $attributes = '';
            $shortcode = self::get_buffer($data[2]);
            if (!empty($data[1])){
                  global $post;
                  $post = get_post($data[1]);
            }

            if (isset($shortcode[1]) && !empty($shortcode[1])){
                  foreach ((array)$shortcode[1] as $key => $value){
                        $attributes.= $key . '="'.$value.'" ';
                  }
            }

            return do_shortcode('[' . $shortcode[0] . ' ' . $attributes . ']');
      }
      public static function fragment_loader(){
            define('SWIFT3_DOING_FRAGMENTS', true);
            $response = array();

            if (!empty($_POST['fragments'])){
                  $fragments = json_decode(stripslashes(urldecode($_POST['fragments'])));
                  foreach ((array)$fragments as $fid => $fragment){
                        $data = json_decode(base64_decode($fragment), true);
                        if (!empty($data)){
                              switch($data[0]){
                                    case 'block':
                                          $result = self::block_fragment($data);
                                          break;
                                    case 'shortcode':
                                          $result = self::shortcode_fragment($data);
                                          break;
                                    case 'collage':
                                          $result = Swift3_Collage::load($data);
                                          break;
                                    default:
                                          $result = array();
                                          break;
                              }

                              $result = apply_filters('swift3_fragment_' . $data[0], $result, $data);
                              if (!is_array($result)){
                                    $result = array('html' => $result);
                              }

                              $response[$fid] = $result;
                        }
                  }
            }

            wp_send_json($response);
      }
      public static function set_buffer($data){
            if (empty($data)){
                  return false;
            }

            $buffer     = (array)get_option('swift3_fragments_buffer');
            $hash       = hash('crc32', json_encode($data));

            $buffer[$hash] = $data;
            update_option('swift3_fragments_buffer', $buffer, false);

            return $hash;
      }
      public static function get_buffer($key){
            $buffer = (array)get_option('swift3_fragments_buffer');
            return $buffer[$key];
      }
      public static function delete_buffer(){
            delete_option('swift3_fragments_buffer');
      }
      public static function should_fragment(){
            return apply_filters('swift3_should_fragment', !(apply_filters('swift3_skip_optimizer', false) || isset($_GET['nocache'])));
      }
}

?>