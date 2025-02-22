<?php

/**
 * This class contains all Pro and Extra features
 */


if (!class_exists('Swift_Performance_Pro')){
      class Swift_Performance_Pro {
            /**
             * Construct Pro object
             */
            public function __construct(){
                  // Background requests
                  self::background_requests();

                  // Limit WP Cron requests
                  self::limit_wp_cron();

                  // WooCommerce features
                  self::init_woocommerce();

                  // Content Visibility
                  self::smart_render_html();

                  // Smart lazyload
                  self::smart_lazyload();

                  // Preprocess Scripts
                  self::preprocess_scripts();

                  // Plugin Updater
                  if (defined('SWIFT_PERFORMANCE_FILE')){
                        add_action('init', array('Swift_Performance_Pro', 'plugin_updater'));
                  }
            }

            /**
             * Plugin Updater (Pro Only)
             */
            public static function plugin_updater(){
                  if (!defined('SWIFT_PERFORMANCE_UNINSTALL') && !defined('SWIFT_PERFORMANCE_DISABLE_UPDATE')){
                        require 'puc/plugin-update-checker.php';
                        $update_checker = Puc_v4_Factory::buildUpdateChecker(
                              add_query_arg(array('purchase-key' => Swift_Performance::get_option('purchase-key'), 'site' => Swift_Performance::home_url(), 'beta' => Swift_Performance::get_option('enable-beta')), SWIFT_PERFORMANCE_API_URL . 'update/info/'),
                              SWIFT_PERFORMANCE_FILE,
                              SWIFT_PERFORMANCE_SLUG
                        );

                        // Add purchase key to download url
                        add_filter('puc_request_info_result-' . SWIFT_PERFORMANCE_SLUG, function($info){
                              @$info->download_url = str_replace('[[PARAMETERS]]', '?purchase-key=' . Swift_Performance::get_option('purchase-key') . '&site=' . Swift_Performance::home_url() . '&beta=' . Swift_Performance::get_option('enable-beta'), $info->download_url);
                              return $info;
                        });
                  }
            }

            /**=======================================**/
                        /*** FONTS ***/
            /**=======================================**/

            /**
      	 * Smart Fonts
      	 * @param string $css
      	 * @param string $html
             * @param string $css_dir
      	 * @return mixed
      	 */
      	 public static function smart_fonts($css, $html, $css_dir){
                  $GLOBALS['swift_performance_fonts_buffer'] = array();

                  if (is_array($css)){
                        foreach ($css as $key => $value){
                              $css[$key] = self::prepare_smart_fonts_css($value);
                        }
                  }
                  else {
                        $css = self::prepare_smart_fonts_css($css);
                  }

                  if (isset($css['mobile'])){
                        $style_tag = '<style media="(max-width:768px)">'.$css['mobile'].'</style><style media="(min-width:769px)">'.$css['desktop'].'</style>';
                  }
                  else {
                        $style_tag = '<style>'.$css.'</style>';
                  }

                  // Build cache key
                  $cache_key = md5(implode('',array(
                        $html,
                        json_encode($GLOBALS['swift_performance_fonts_buffer']),
                        $style_tag,
                  )));

                  // Send API request
                  $response = Swift_Performance::api('utils/smart_fonts/' . $cache_key, array(
                        'html' => base64_encode($html),
                        'fonts' => $GLOBALS['swift_performance_fonts_buffer'],
                        'css' => base64_encode($style_tag),
                  ));

                  if (isset($response['smart_fonts']) && !empty($response['smart_fonts'])){
                        $smart_font_css = preg_replace_callback('~url\(data:font\/woff;base64,([^\)]*)\)~',function($matches) use ($css_dir){
					$data = base64_decode($matches[1]);
					return 'url(' . Swift_Performance_Cache::write_file(trailingslashit($css_dir) . md5($data) . '.woff', $data) . ')';
				}, base64_decode($response['smart_fonts']));

                        if (is_array($css)){
                              foreach ($css as $key => $value){
                                    $css[$key] .= $smart_font_css;
                              }
                        }
                        else {
                              $css .= $smart_font_css;
                        }

                        return $css;
                  }
                  else {
                        Swift_Performance::log('Smart Fonts API error',1);
                  }

                  unset ($GLOBALS['swift_performance_fonts_buffer']);
                  return false;
      	 }

             /**
              * Collect Font Face rules and prepare critical CSS
              * @param string $css
              * @return string
              */
             public static function prepare_smart_fonts_css($css){
                   return preg_replace_callback('~@font-face\{([^\}]*)\}~', function($matches){
                        foreach ((array)$matches[1] as $font_face){
                              $rules = array();

                              // Parse rules
                              foreach (explode(';', $font_face) as $rule){
                                    $exploded = explode(':', $rule);
                                    $key = array_shift($exploded);
                                    $value = implode(':', $exploded);
                                    $rules[trim($key)] = trim($value);
                              }

                              // Skip if src is missing
                              if (!isset($rules['src']) || !isset($rules['font-family'])){
                                    return $matches[0];
                              }

                              $family = trim($rules['font-family'], ' "\'');
                              $weight = str_replace('normal','400',(isset($rules['font-weight']) ? $rules['font-weight'] : 400));
                              $style = (isset($rules['font-style']) ? $rules['font-style'] : 'normal');
                              $range = (isset($rules['unicode-range']) ? $rules['unicode-range'] : '');

                              /*
                               * Add fonts to collection
                               */

                              foreach (explode(',', $rules['src']) as $src){
                                    preg_match('~url\(([^\)]*)\.(woff2?|ttf|svg)\)~', $src, $url);
                                    if (isset($url[1]) && isset($url[2])){
                                          $format	= $url[2];
                                          $font		= $url[1];

                                          if (empty($format)){
                                                continue;
                                          }

                                          $font_url	= (preg_match('~^//~', $font) ? (is_ssl() ? 'https:' : 'http:') : (preg_match('~^/~', $font) ? ABSPATH : '')) . $font . '.' . $format;

                                          if (preg_match('~^/~', $font_url)){
                                                if (!file_exists($font_url)){
                                                      return $matches[0];
                                                }
                                                $data = base64_encode(file_get_contents($font_url));
                                          }
                                          else {
                                                $response = wp_remote_get($font_url, array('sslverify' => false, 'timeout' => 30));
                                                if (is_wp_error($response)){
                                                      return $matches[0];
                                                }
                                                else {
                                                      $data = base64_encode($response['body']);
                                                }
                                          }

                                          $GLOBALS['swift_performance_fonts_buffer'][] = array(
                                                'font-family'     => $family,
                                                'font-style'      => $style,
                                                'font-weight'     => $weight,
                                                'unicode-range'   => $range,
                                                'data'            => $data,
                                                'format'          => $format
                                          );
                                          return '';
                                    }
                              }
                         }
                  }, $css);
             }


            /**
      	 * Collect and preload fonts (Pro only)
      	 * @param string critical_css
      	 * @param string html
      	 * @return array
      	 */
      	 public static function preload_fonts($critical_css, $html){
                   $font_preload = array();

                   // Set device
                   $device = (Swift_Performance::is_mobile() ? 'mobile' : 'desktop');

                   // Build cache key
                   $cache_key = md5(implode('',array(
                         $html,
                         $device
                   )));

                   // Send API request
                   $response = Swift_Performance::api('utils/preload_fonts/' . $cache_key, array(
                         'html' => base64_encode($html),
                         'device' => $device
                   ));

                  // Get exceptions
                  $exclude_strings	= array_filter((array)Swift_Performance::get_option('exclude-preload-fonts'));
                  $exclude_regex    = '~' . implode('|', $exclude_strings) . '~';

                  if (!empty($response) && isset($response['fonts']) && !empty($response['fonts'])){
                        $fonts = json_decode(str_replace('\'', '"', $response['fonts']), true);

                        foreach ($fonts as $font){
                              if (!empty($exclude_strings) && preg_match($exclude_regex, $font)){
                                    continue;
                              }
                              $font_preload[] = '<link rel="preload" href="'.$font.'" as="font" crossorigin>';
                        }

                        return $font_preload;
                  }

      		return array();
      	 }

            /**
             * Manual preload fonts (Pro only)
             * @return array
             */
            public static function manual_preload_fonts(){
                  $font_preload = array();
                  $manual_preload_fonts = array_filter((array)Swift_Performance::get_option('manual-preload-fonts'));
                  if (!empty($manual_preload_fonts)){
                        foreach ((array)$manual_preload_fonts as $manual_preload_font){
                              $manual_preload_font_format = pathinfo(parse_url($manual_preload_font, PHP_URL_PATH), PATHINFO_EXTENSION);
                              $font_preload[] = '<link rel="preload" href="'.$manual_preload_font.'" as="font" type="font/' . $manual_preload_font_format . '" crossorigin>';
                        }
                  }
                  return $font_preload;
            }

            /**
             * Force font display swap
             * @param string
             * @return string
             */
            public static function font_display_swap($_css){
                  $_css = preg_replace_callback('~@font-face\s?\{([^\{\}]+)\}~', function($matches){
                        $rule = trim($matches[1]);
                        preg_match('~font-family\s?:([^;]+)~', $rule, $family);
                        $family = strtolower(preg_replace('~("|\')~','',$family[1]));
                        $excluded = (array)Swift_Performance::get_option('exclude-font-display');
                        if (!in_array($family, $excluded)){
                              if (strpos($rule, 'font-display') !== false){
                                    $rule = preg_replace('~font-display:\s?(auto|block);?~', 'font-display:swap;', $rule);
                              }
                              else {
                                    $rule = $rule . (substr($rule, -1, 1) == ';' ? '' : ';') . 'font-display:swap;';
                              }
                        }
                        return '@font-face{'.$rule.'}';
                  }, $_css);

                  return $_css;
            }

            /**
      	 * Host fonts locally
      	 * @param string $css
      	 * @param string $css_dir
      	 * @return string
      	 */

      	public static function local_fonts($matches){
      		if (!isset($matches[2]) || !isset($matches[3]) || empty($matches[2]) || empty($matches[3])){
      			return $matches[0];
      		}

      		$current_path = ltrim(trailingslashit(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)),'/');

      		$font_dir = trailingslashit(apply_filters('swift_performance_css_dir', Swift_Performance::check_option('separate-css', 1) ? $current_path . 'css/fonts' : 'css/fonts'));
      		$font_src = (preg_match('~^//~', $matches[2]) ? 'https:' : '') . $matches[2] . '.' . $matches[3];

      		if (strpos(Swift_Performance::home_url(), $font_src) === 0 || strpos('/', $font_src)){
      			return $matches[0];
      		}

      		$filename = basename($font_src);
      		if (!file_exists(SWIFT_PERFORMANCE_CACHE_DIR . $font_dir . $filename)){
      			$response = wp_remote_get($font_src, array('timeout' => 10, 'sslverify' => false));
      			if (!is_wp_error($response) && !empty($response['body'])){
      				return 'url(' . Swift_Performance_Cache::write_file($font_dir . $filename, $response['body']) . ')';
      			}
      			else {
      				return $matches[0];
      			}
      		}
      		return 'url(' . SWIFT_PERFORMANCE_CACHE_URL . $font_dir . $filename . ')';
      	}

            /**=======================================**/
                        /*** AJAXIFY ***/
            /**=======================================**/

            /**
            * Generate ajaxify styles and scripts and add them to buffer (Pro only)
            * @param string buffer
            * @return string
            */
            public static function get_ajaxify_scirpts($buffer){
                  $ajaxify = array_filter((array)apply_filters('swift_performance_ajaxify', Swift_Performance::get_option('ajaxify')));
                  $ajaxify_style = '';

                  if (!empty($ajaxify)){
                        ob_start();
                        include SWIFT_PERFORMANCE_DIR . 'modules/cache/ajaxify.php';
                        $ajaxify_html = preg_replace('~(\n|\s)+~',' ',ob_get_clean());
                        $buffer = str_replace('</body>',"{$ajaxify_html}\n</body>", $buffer);
                        $ajaxify_css_selector = implode(',', array_map(function($rule){
                              return trim($rule) . ':not(.swift-lazyloaded)';
                        },$ajaxify));

                        if (Swift_Performance::check_option('ajaxify-placeholder', 'blur')){
                              $ajaxify_style = $ajaxify_css_selector . '{filter:blur(5px);pointer-events:none;}';
                        }
                        else if (Swift_Performance::check_option('ajaxify-placeholder', 'hidden')){
                              $ajaxify_style = $ajaxify_css_selector . '{opacity:0;pointer-events:none;}';
                        }
                  }

                  $lazyload_shortcodes    = array_filter((array)Swift_Performance::get_option('lazyload-shortcode'));
                  $lazyload_widgets       = array_filter((array)Swift_Performance::get_option('lazyload-widgets'));
                  if (Swift_Performance::is_option_set('lazyload-nav-menus') || Swift_Performance::is_option_set('lazyload-template-parts') || Swift_Performance::is_option_set('lazyload-shortcodes') || Swift_Performance::is_option_set('lazyload-widgets') || Swift_Performance::check_option('lazyload-blocks', 1) || Swift_Performance::check_option('lazyload-elementor-widgets', 1) || Swift_Performance::check_option('woocommerce-price-ajaxify', 1)){

                        $preload_point = (int)Swift_Performance::get_option('ajaxify-preload-point');

                        $ajaxify_script = "<script data-dont-merge>(function() { function iv(a) { if (typeof a.getBoundingClientRect !== 'function') { return false } var b = a.getBoundingClientRect(); return (b.bottom + {$preload_point} >= 0 && b.right + {$preload_point} >= 0 && b.top - {$preload_point} <= (window.innerHeight || document.documentElement.clientHeight) && b.left - {$preload_point} <= (window.innerWidth || document.documentElement.clientWidth))}function ll() { var elements = document.querySelectorAll('.swift-lazy-wrapper:not(.swift-is-loading), .swift-lazy-marker:not(.swift-is-loading)'); elements.forEach(function(element){ if (iv(element)) { element.classList.add('swift-is-loading'); var data = element.dataset['request']; var xhttp = new XMLHttpRequest(); xhttp.onreadystatechange = function(){if(this.readyState==4&&this.status==200){if (element.className.match(/swift-lazy-marker/)){if (element.nextElementSibling !== null){element.nextElementSibling.outerHTML = this.responseText}}else if(element.className.match(/swift-lazy-replace/)){element.outerHTML = this.responseText}else{element.innerHTML=this.responseText};element.classList.remove('swift-lazy-wrapper');element.classList.remove('swift-lazy-marker');element.classList.remove('swift-is-loading');element.dispatchEvent(new Event('ajaxify-finished'))}};xhttp.open('POST', '".admin_url('admin-ajax.php')."', true); xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded'); xhttp.send('action=swift_performance_ajaxify&data=' + encodeURIComponent(data)); } }); if (elements.length > 0){ requestAnimationFrame(ll) } } requestAnimationFrame(ll)})();</script>";

                        if (Swift_Performance::check_option('ajaxify-placeholder', 'blur')){
                              $ajaxify_style .= '.swift-lazy-wrapper, .swift-lazy-marker + *{display:block;filter:blur(5px);pointer-events:none;}';
                        }
                        else if (Swift_Performance::check_option('ajaxify-placeholder', 'hidden')){
                              $ajaxify_style .= '.swift-lazy-wrapper,.swift-lazy-marker + *{opacity:0;pointer-events:none;}';
                        }

                        if (Swift_Performance::check_option('woocommerce-ajaxify-checkout',1)){
                              $ajaxify_style .= '.swift-lazy-wrapper .woocommerce{opacity:0;pointer-events:none;}';
                        }

                        $buffer = str_replace('</body>',"{$ajaxify_script}\n</body>", $buffer);
                  }

                  if (!empty($ajaxify_style)){
                        $buffer = preg_replace('~<head(\s[^>]*)?>~',"<head$1>\n<style>{$ajaxify_style}</style>", $buffer);
                  }

                  return $buffer;
            }


            /**
            * Filter for do_shortcode_tag to modify html output for lazyloading (Pro only)
            * @param string content
            * @param string shortcode
            * @param array atts
            * @return string
            */
            public static function lazyload_shortcode($content, $shortcode, $atts){
                  // Collect shortcode for autocomplete
                  Swift_Performance_Autocomplete::collect('shortcodes', $shortcode);

                  $lazyload_shortcodes = (array)Swift_Performance::get_option('lazyload-shortcode');

                  if (!is_admin() && !Swift_Performance::is_rest() && in_array($shortcode, $lazyload_shortcodes) && apply_filters('swift_performance_lazyload_shortcode', true, $shortcode, $atts)){
                        $request = base64_encode(json_encode(array('shortcode', get_the_ID(), Swift_Performance_Cache::set_lazyload_buffer(array($shortcode, $atts)))));
                        return '<span class="swift-lazy-wrapper" data-request="'.$request.'">'.$content.'</span>';
                  }
                  return $content;
            }

            /**
            * Lazyload template parts (Pro only)
            */
            public static function lazyload_template_parts($template){
                  // Collect template_part for autocomplete
                  Swift_Performance_Autocomplete::collect('template_parts', $template);

                  $template_parts = (array)Swift_Performance::get_option('lazyload-template-parts');
                  if (!is_admin() && in_array($template, $template_parts) && !defined('DOING_SWIFT_AJAXIFY')){
                        $request = base64_encode(json_encode(array('template-part', get_the_ID(), $template)));
                        echo '<span class="swift-lazy-marker" data-request="'.$request.'"></span>';
                  }
            }
            /**
            * Lazyload nav menu (Pro only)
            */
            public static function lazyload_nav_menu($nav_menu, $args){
                  if (defined('DOING_SWIFT_AJAXIFY') ){
                        return $nav_menu;
                  }

                  $lazyload_nav_menus = array_filter((array)Swift_Performance::get_option('lazyload-nav-menus'));

                  if (!isset($args->menu) || !is_object($args->menu) || !isset($args->menu->term_id) || !in_array($args->menu->term_id, $lazyload_nav_menus)){
                        return $nav_menu;
                  }

                  preg_match('~<([^>]*)>~', $nav_menu, $menu_wrapper);
                  $menu_wrapper = $menu_wrapper[0];

                  // Add wrapper class
                  if (strpos($menu_wrapper, 'class=') !== false){
                        $new_wrapper = preg_replace('~class=("|\')([^"\']*)("|\')~', 'class=$1$2 swift-lazy-wrapper swift-lazy-replace$1', $menu_wrapper);
                  }
                  else {
                        $new_wrapper = preg_replace('~>~', 'class="swift-lazy-wrapper"', $menu_wrapper);
                  }

                  // Add request data
                  $request = base64_encode(json_encode(array('nav-menu', get_the_ID(), Swift_Performance_Cache::set_lazyload_buffer($args))));
                  $new_wrapper = preg_replace('~>~', ' data-request="'.$request.'">', $new_wrapper);

                  return str_replace($menu_wrapper, $new_wrapper, $nav_menu);
            }

            /**
            * Filter for Gutenberg block to modify html output for lazyloading (Pro only)
            * @param string content
            * @param array block
            * @return string
            */
            public static function lazyload_blocks($content, $block){
                  if (isset($block['attrs']['isSwiftPerfrormanceLazyloaded']) && $block['attrs']['isSwiftPerfrormanceLazyloaded'] && !is_admin() && !Swift_Performance::is_rest() && apply_filters('swift_performance_lazyload_block', true, $block)){
                        return '<span class="swift-lazy-wrapper" data-request="'.base64_encode(json_encode(array('block', get_the_ID(), Swift_Performance_Cache::set_lazyload_buffer($block)))).'">'.$content.'</span>';
                  }
                  return $content;
            }

            /**
            * Filter for widget_display_callback to modify html output for lazyloading (Pro only)
            * @param WP_Wigdet instance
            * @param object WP_Widget
            * @param array args
            * @return string
            */
            public static function lazyload_widgets($instance, $that, $args){
                  $lazyload_widgets = (array)Swift_Performance::get_option('lazyload-widgets');
                  if (!in_array(get_class($that), $lazyload_widgets) || (defined('DOING_SWIFT_AJAXIFY')) || apply_filters('swift_performance_lazyload_widget', false, $instance, $args)){
                        return $instance;
                  }
                  $request = base64_encode(json_encode(array('widget', get_the_ID(), Swift_Performance_Cache::set_lazyload_buffer(array(get_class($that), $instance, $args)))));
                  $args['before_widget'] = '<span class="swift-lazy-wrapper" data-request="' . $request . '">' . $args['before_widget'];
                  $args['after_widget'] .= '</span>';
                  $that->widget( $args, $instance );
                  return false;
            }

            /**
            * Filter for Elementor render callback to modify html output for lazyloading (Pro only)
            * @param WP_Wigdet instance
            * @param object WP_Widget
            * @param array args
            * @return string
            */
            public static function lazyload_elementor_widgets($widget_content, $that){
                  if (defined('DOING_SWIFT_AJAXIFY')){
                        return $widget_content;
                  }
                  $data = $that->get_data();
                  if (isset($data['settings']['swift-ajaxify']) && $data['settings']['swift-ajaxify'] == 'yes' && apply_filters('swift_performance_lazyload_elementor_widget', true, $that)){
                        $widget_content = '<span class="swift-lazy-wrapper" data-request="'.base64_encode(json_encode(array('elementor', get_the_ID(), Swift_Performance_Cache::set_lazyload_buffer($that->get_data())))).'">' . $widget_content . '</span>';
                  }
                  return $widget_content;
            }

            /**=======================================**/
                        /*** WOOCOMMERCE ***/
            /**=======================================**/

            /**
             * Init WooCommerce features
             */
            public static function init_woocommerce(){
                  // WooCommerce GEOIP
                  if (Swift_Performance::check_option('woocommerce-geoip-support',1) && Swift_Performance::check_option('caching-mode', array('memcached_php', 'disk_cache_php'), 'IN')){
                        add_filter('swift_performance_cache_folder_prefix', array(__CLASS__, 'woocommerce_geoip_prefix'));
                        add_action('swift_performance_prebuild_cache_hit', array(__CLASS__, 'woocommerce_geiop_prebuild'));
                  }

                  // WooCommerce Session Cache
                  if (Swift_Performance::check_option('woocommerce-session-cache', 1)){
                        add_action( 'swift_performance_woocommerce_session_cache_prebuild', array(__CLASS__, 'woocommerce_session_cache_prebuild'), 10, 2);

                        global $wpdb;
                        $shop_pages = (array)$wpdb->get_col("SELECT post_name FROM {$wpdb->posts} LEFT JOIN {$wpdb->options} ON option_value = ID WHERE option_name IN ('woocommerce_cart_page_id', 'woocommerce_checkout_page_id')");

                        foreach (array('wp_login', 'woocommerce_removed_coupon','woocommerce_cart_emptied','woocommerce_add_to_cart','woocommerce_cart_item_removed','woocommerce_cart_item_restored','woocommerce_applied_coupon') as $action){
                              add_action($action, array(__CLASS__, 'prepare_woocommere_clear_session_cache'), PHP_INT_MAX);
                        }
                        add_filter('woocommerce_update_cart_action_cart_updated', array(__CLASS__, 'prepare_woocommere_clear_session_cache'), PHP_INT_MAX);

                        $cookie_name = apply_filters( 'woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH );
                        if (in_array(trim($_SERVER['REQUEST_URI'],'/'), $shop_pages) && isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name]) && !isset($_POST['update_cart'])) {
                              add_filter('swift_performance_is_cacheable_dynamic', '__return_true');
                              $_POST['woocommerce-session-cache'] = md5($_COOKIE[$cookie_name]);

                              if (Swift_Performance::check_option('optimize-woocommerce-session-cache', 1, '!=')){
                                    Swift_Performance::set_option('merge-scripts', 0);
                                    Swift_Performance::set_option('merge-styles', 0);
                                    add_filter('swift_performance_dynamic_cache_expiry', function(){
                                          return 3600;
                                    });
                              }
                        }
                  }

                  // Disable WooCommerce Cart Fragments AJAX
                  add_action( 'wp_enqueue_scripts', array(__CLASS__, 'dequeue_woocommerce_cart_fragments'), 11);


                  // WooCommerce Geolocation + Caching prebuild fix
                  add_filter('option_woocommerce_default_customer_address', function($value){
                        if ($value == 'geolocation_ajax' && (isset($_GET['swift-preview']) || isset($_SERVER['HTTP_X_PREBUILD']) || isset($_SERVER['HTTP_X_MERGE_ASSETS']))){
                              return 'base';
                        }
                        else {
                              return $value;
                        }
                  });

                  // AJAXIFY WooCommerce prices
                  if (Swift_Performance::check_option('woocommerce-price-ajaxify', 1)){
                        add_filter('woocommerce_get_price_html', function($content, $product){
                              if (!is_admin() && !Swift_Performance::is_rest()){
                                    $request = base64_encode(json_encode(array('woo-price', get_the_ID(), $product->get_id())));
                                    return '<span class="swift-lazy-wrapper" data-request="'.$request.'">'.$content.'</span>';
                              }
                              return $content;
                        },10,2);
                  }

                  // Prebuild WooCommerce Variations
                  if (Swift_Performance::check_option('prebuild-woocommerce-variations',1)){
                        Swift_Performance::set_option('dynamic-caching',1);
                        add_filter('swift_performance_option_cacheable-dynamic-requests', function($params){
                              foreach ($_GET as $key => $value){
                                    if (preg_match('~^attribute_pa_~', $key)){
                                          $params[] = $key;
                                    }
                              }
                              return $params;
                        });

                        add_filter('swift_performance_is_object_cacheable', function($result, $url){
                              $query_string = parse_url($url, PHP_URL_QUERY);
                              if (!empty($query_string)){
                                    parse_str($query_string, $get);
                                    foreach ($get as $key => $value){
                                          if (preg_match('~^attribute_pa_~', $key)){
                                                return true;
                                          }
                                    }
                              }
                              return $result;
                        },10 ,2);

                        add_action('swift_performance_prebuild_cache_hit', array(__CLASS__, 'woocommerce_variation_prebuild'));
                  }
            }

            /**
            * Disable Cart Fragments
            */
            public static function dequeue_woocommerce_cart_fragments() {
                  $disable = false;
                  if (Swift_Performance::check_option('disable-cart-fragments', 'everywhere')){
                        $disable = true;
                  }
                  else if (Swift_Performance::check_option('disable-cart-fragments', 'non-shop')){
                        global $wpdb;
                        $results = $wpdb->get_col("SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE 'woocommerce_%_page_id'", ARRAY_A);

                        if ((!function_exists('is_shop') || !is_shop()) || in_array(get_the_ID(), $results)){
                              $disable = true;
                        }
                  }
                  else if (Swift_Performance::check_option('disable-cart-fragments', 'specified-pages')){
                        $pages = (array)Swift_Performance::get_option('disable-cart-fragments-pages');
                        if (in_array(get_the_ID(), $pages)){
                              $disable = true;
                        }
                  }
                  else if (Swift_Performance::check_option('disable-cart-fragments', 'specified-urls')){
                        $urls = (array)Swift_Performance::get_option('disable-cart-fragments-urls');
                        foreach ($urls as $url){
                              if (strpos(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), parse_url($url, PHP_URL_PATH)) !== false){
                                    $disable = true;
                              }
                        }
                  }
                  if ($disable){
                        wp_dequeue_script('wc-cart-fragments');
                  }
            }

            /**
            * Run prebuild cache for all enabled countries
            */
            public static function woocommerce_geiop_prebuild($permalink){
                  // Get allowed countries
                  $allowed_countries = array_filter((array)Swift_Performance::get_option('woocommerce-geoip-allowed-countries'));

                  if(empty($allowed_countries) && file_exists(WP_PLUGIN_DIR . '/woocommerce/i18n/countries.php')){
                        $allowed_countries = array_keys((array)apply_filters( 'woocommerce_countries', include WP_PLUGIN_DIR . '/woocommerce/i18n/countries.php'));
                  }

                  foreach ((array)$allowed_countries as $allowed_country) {
                        // Add country code to prebuild header
                        add_filter('swift_performance_prebuild_headers', function($headers) use ($allowed_country){
                              $headers['X-swift-country-code'] = strtoupper($allowed_country);
                              return $headers;
                        });

                        // Add country code to mobile prebuild header
                        add_filter('swift_performance_mobile_prebuild_headers', function($headers) use ($allowed_country){
                              $headers['X-swift-country-code'] = strtoupper($allowed_country);
                              return $headers;
                        });

                        Swift_Performance::prebuild_cache_hit($permalink);
                  }
            }

            /**
            * Add country prefix
            */
            public static function woocommerce_geoip_prefix($prefix){
                  if (isset($_SERVER['HTTP_X_SWIFT_COUNTRY_CODE'])){
                        add_filter('woocommerce_geolocate_ip', function(){
                              return $_SERVER['HTTP_X_SWIFT_COUNTRY_CODE'];
                        });
                  }

                  if(@file_exists(WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-geolocation.php')){
                        include_once WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-geolocation.php';
                        include_once WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-geo-ip.php';

                        $geoloacte            = WC_Geolocation::geolocate_ip();
                        $allowed_countries    = (array)Swift_Performance::get_option('woocommerce-geoip-allowed-countries');
                        if (isset($geoloacte['country']) && !empty($geoloacte['country']) && (empty($allowed_countries) || in_array($geoloacte['country'], $allowed_countries)) ){
                              $prefix    = $geoloacte['country'];
                        }
                  }

                  return $prefix;
            }

            /**
            * Prebuild variations for WooCommerce product
            */
            public static function woocommerce_variation_prebuild($permalink){
                  @$maybe_product_id      = url_to_postid($permalink);
                  $maybe_product          = wc_get_product($maybe_product_id);
                  if (!empty($maybe_product) && get_class($maybe_product) == 'WC_Product_Variable'){
                        foreach ((array)$maybe_product->get_available_variations() as $variation){
                              $variation_permalink = get_permalink($variation['variation_id']);
                              Swift_Performance::log('Prebuild variation for ' . $maybe_product->get_name() . ', variation ID: ' . $variation['variation_id']);
                              Swift_Performance::prebuild_cache_hit($variation_permalink);
                        }

                  }
            }

            /**
             * Prepare clearing session cache
             */
            public static function prepare_woocommere_clear_session_cache($param){
                  add_action('shutdown', array(__CLASS__, 'woocommere_clear_session_cache'));

                  return $param;
            }

            /**
            * Clear WooCommerce session cache
            */
            public static function woocommere_clear_session_cache(){
                  global $wpdb;
                  $cookie_name = apply_filters( 'woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH );

                  $headers = headers_list();
                  foreach ($headers as $header){
                        if (preg_match('~^Set-Cookie:\s?'.$cookie_name.'=([abcdef0-9|%]*)~i', $header, $matches)){
                              $_COOKIE[$cookie_name] = urldecode($matches[1]);
                        }
                  }

		      $_COOKIE['woocommerce_cart_hash'] = WC()->cart->get_cart_hash();


                  if (isset($_COOKIE[$cookie_name])){
                        $hash = hash('crc32', serialize(array('woocommerce-session-cache' => md5($_COOKIE[$cookie_name]))));
                        $transients = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_swift_performance_dynamic_%_{$hash}'");
                        foreach ($transients as $transient) {
                              delete_transient(str_replace('_transient_','',$transient));
                        }

                        // Prebuild cache
                        $useragent = apply_filters('swift_performance_session_cache_useragent', (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:52.0) Gecko/20100101 Firefox/52.0'));
                        wp_schedule_single_event(time(), 'swift_performance_woocommerce_session_cache_prebuild', array($_COOKIE, $useragent));
                  }
            }

            /**
            * Preload session cache
            * @param array $user_cookies
            */
            public static function woocommerce_session_cache_prebuild($user_cookies, $useragent){
                  global $wpdb;
                  $shop_pages = (array)$wpdb->get_col("SELECT post_name FROM {$wpdb->posts} LEFT JOIN {$wpdb->options} ON option_value = ID WHERE option_name IN ('woocommerce_cart_page_id', 'woocommerce_checkout_page_id')");
                  $cookies = array();

                  foreach ($user_cookies as $name => $value) {
                      $cookies[] = new WP_Http_Cookie( array( 'name' => $name, 'value' => $value ) );
                  }

                  foreach ($shop_pages as $shop_page){
                        $response = wp_remote_get( trailingslashit(home_url($shop_page)), array('sslverify' => false,'useragent' => $useragent, 'headers' => array('X-merge-assets' => 'true', 'X-Prebuild' => 'true'),  'cookies' => $cookies ) );
                  }

            }




            /**=======================================**/
                        /*** IMAGES ***/
            /**=======================================**/

            /**
            * Collect and preload images (Pro only)
            * @param string buffer
            * @param array media_preload
            * @return array
            */
            public static function preload_media($buffer, $media_preload){
                  if (is_array($buffer)){
      			$buffer = implode("\n", $buffer);
      		}
                  $preload_images_by_url = array_filter((array)Swift_Performance::get_option('preload-images-by-url'));

                  if (!empty($preload_images_by_url)){
                        preg_match_all('~url\s?\((\'|")?((((?!\)).)*)\.(png|jpg|webp|gif|svg))(\'|")?\)~', $buffer, $matches);
                        if (isset($matches[2])){
                              foreach ((array)$matches[2] as $src){
                                    if (preg_match('~('.implode('|', $preload_images_by_url).')~', $src)){
                                          $media_preload[] = '<link rel="preload" href="'.$src.'" as="image">';
                                    }
                              }
                        }
                  }

                  return $media_preload;
            }

            /**
             * Flag images for preload in DOM (Pro only)
             * @param SimpleDOMParser
             * @return SimpleDOMParser
             */
            public static function preload_images_by_class($html){
                  $preload_images_by_class = array_filter((array)Swift_Performance::get_option('preload-images-by-class'));
      		foreach ($preload_images_by_class as $preload_image){
      			foreach ($html->find('.' . $preload_image . ' img') as $node){
      				$node->{'data-swift-preloaded'} = 'true';
      			}
      		}
                  return $html;
            }

            /**
             * Prepare preload image link tag (Pro only)
             * @param SimpleDOMParser node
             * @return SimpleDOMParser node
             */
            public static function preload_image_tag($node){
                  $preload = $responsive = '';

                  $preload_images_by_url		= array_filter((array)Swift_Performance::get_option('preload-images-by-url'));
                  $preload_images_by_class	= array_filter((array)Swift_Performance::get_option('preload-images-by-class'));
                  if ((!empty($preload_images_by_url) && preg_match('~('.implode('|', $preload_images_by_url).')~', $node->src)) || (!empty($preload_images_by_class) && isset($node->class) && preg_match('~('.implode('|', Swift_Performance::padding_str($preload_images_by_class, '\b')).')~', $node->class)) || isset($node->{'data-swift-preloaded'})){
                        if (isset($node->srcset)){
                              $responsive = ' imagesrcset="'.$node->srcset.'"';
                        }
                        if (isset($node->sizes)){
                              $responsive .= ' imagesizes="'.$node->sizes.'"';
                        }
                        $preload = '<link rel="preload" href="'.$node->src.'"'.$responsive.' as="image">';
                  }

                  return $preload;
            }

            /**
             * Fix missing image dimensions
             * @param SimpleDOMParser node
             * @return SimpleDOMParser node
             */
            public static function fix_missing_image_dimensions($node){
                  $file = str_replace(apply_filters('swift_performance_media_host', Swift_Performance::home_url()), Swift_Performance::get_home_path(), Swift_Performance::canonicalize($node->src));

                  // local files only
                  if (file_exists($file)){
                        list($width, $height, $image_type) = getimagesize($file);
                        if (!isset($node->width) || empty($node->width)){
                              $node->width = $width;
                        }
                        if (!isset($node->height) || empty($node->height)){
                              $node->height = $height;
                        }
                  }
                  else {
                        $src = (preg_match('~^//~', $node->src) ? 'https:' . $node->src : (preg_match('~^http~', $node->src) ? $node->src : parse_url(Swift_Performance::home_url(), PHP_URL_SCHEME) . '://' . $node->src));
                        $response = wp_remote_get($src, array('sslverify' => false, 'timeout' => 15, 'headers' => array('Referer' => home_url())));

                        if (is_wp_error($response)){
                              Swift_Performance::log($src . ' can not be loaded. ' . $response->get_error_message(), 6);
                              return $node;
                        }
                        else if (empty($response['body'])){
                              Swift_Performance::log($src . ' empty response. ', 6);
                              return $node;
                        }
                        else {
                              // Disable logging to prevent warnings in log if the image is not in a recognized format
                              @$tmp_image	= imagecreatefromstring( $response['body'] );
                              if (!empty($tmp_image)){
                                    @$width	= imagesx($tmp_image);
                                    @$height	= imagesy($tmp_image);
                                    @imagedestroy($tmp_image);

                                    if (!isset($node->width) && !empty($width)){
                                          $node->width = $width;
                                    }
                                    if (!isset($node->height) && !empty($height)){
                                          $node->height = $height;
                                    }
                              }
                        }
                  }

                  return $node;
            }


            /**=======================================**/
                        /*** CACHE ***/
            /**=======================================**/


            /**
             * Bypass or extend WP nonce lifetime (Pro only)
             */
            public static function bypass_nonce(){
                  if (Swift_Performance::check_option('cache-expiry-mode','actionbased') && Swift_Performance::check_option('bypass-nonce',1)){
                        add_filter('nonce_user_logged_out', function($uid){
                              add_filter('nonce_life', $that = function($nonce_life) use (&$that, $uid){
                                    remove_filter('nonce_life', $that, PHP_INT_MAX);
                                    // If $uid was overwritten return the original expiry
                                    if (!empty($uid)){
                                          return $nonce_life;
                                    }
                                    return PHP_INT_MAX;
                              }, PHP_INT_MAX);
                              return $uid;
                        }, PHP_INT_MAX);

                        add_filter( 'nonce_user_logged_out', '__return_zero', PHP_INT_MAX );
                  }
                  else if (Swift_Performance::check_option('cache-expiry-mode','timebased') && Swift_Performance::check_option('extend-nonce-life',1)){
                        add_filter('nonce_life', function($nonce_life){
                              $expiry = Swift_Performance::get_option('cache-expiry-time');
                              return max($nonce_life, $expiry*2);
                        });
                  }
            }

            /**
            * Clear cache for short lifespan pages (Pro only)
            */
            public static function clear_short_lifespan(){
                  global $wpdb;
                  do_action('swift_performance_before_clear_short_lifespan_cache');

                  $short_lifespan_page_ids = implode(', ',
                        array_map(function($page_id){
                              return '"' . Swift_Performance::get_warmup_id(get_permalink($page_id)) . '"';
                        }, array_filter((array)Swift_Performance::get_option('short-lifespan-pages')))
                  );

                  if (!empty($short_lifespan_page_ids)){
                        $timestamp  = time() - 39600;
                        $expired    = $wpdb->get_col("SELECT url FROM " . SWIFT_PERFORMANCE_TABLE_PREFIX . "warmup WHERE id IN ({$short_lifespan_page_ids}) AND timestamp <= '{$timestamp}'");
                        foreach ($expired as $permalink) {
                              Swift_Performance_Cache::clear_permalink_cache($permalink);
                        }
                  }

                  do_action('swift_performance_after_clear_short_lifespan_cache');
            }

            /**
             * Send Proxy cache headers (Pro only)
             */
            public static function proxy_cache(){
                  Swift_Performance::header('Cache-Control: "s-maxage=' . Swift_Performance::get_option('proxy-cache-maxage') . ', max-age=0, public, must-revalidate"');
            }

            /**=======================================**/
                        /*** MISC ***/
            /**=======================================**/


            /**
            * Check request and call flush connection if the parameters are met with one rule of the ruleset
            */
            public static function background_requests(){
                  foreach ((array)Swift_Performance::get_option('background-requests') as $rule) {
                        if (!empty($rule)){
                              @list($key, $value) = explode('=', $rule);
                              $key        = trim($key);
                              $value      = (!isset($value) ? '' : trim($value));
                              if (isset($key) && !empty($key) && isset($_REQUEST[$key]) && $_REQUEST[$key] == $value){
                                    Swift_Performance::flush_connection();
                              }
                        }
                  }
            }

            /**
            * Limit WP Cron requests
            */
            public static function limit_wp_cron(){
                  $limit = Swift_Performance::get_option('limit-wp-cron');
                  if ($limit < 100 && mt_rand(0,100) > $limit){
                        remove_action('init', 'wp_cron');
                  }
            }

            /**
            * Exclude specific roles from GA
            */
            public static function ga_exclude_roles(){
                  $excluded_roles = Swift_Performance::get_option('ga-exclude-roles');
                  if (!empty($excluded_roles) && isset($_COOKIE[LOGGED_IN_COOKIE]) && !empty($_COOKIE[LOGGED_IN_COOKIE])){
                        @list($login, ) = explode('|', $_COOKIE[LOGGED_IN_COOKIE]);
                        if (!empty($login)){
                              global $wpdb;
                              $roles = maybe_unserialize($wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->usermeta} LEFT JOIN {$wpdb->users} ON user_id = ID WHERE user_login = %s AND meta_key LIKE '{$wpdb->prefix}capabilities'", $login)));
                              foreach ($excluded_roles as $excluded_role){
                                    if ( isset($roles[$excluded_role]) ) {
                                        // Role is excluded, stop here
                                        die;
                                    }
                              }
                        }
                  }
            }

            /**
            * Youtube Smart Embed
            * @param SimpleDOMParser node
            * @return SimpleDOMParser node
            */
            public static function youtube_smart_embed($node, $current_path){
                  preg_match('#https://www.youtube(-nocookie)?.com/embed/([^\?/\s"]+)#',$node->src, $matches);
                  $height = isset($node->height) ? $node->height . 'px' : '100%';
                  if (isset($matches[2]) && !empty($matches[2])){
                        $src = add_query_arg('autoplay', 1, $node->src);
                        $node->src='';
                        $node->class = (isset($node->class) ? $node->class . ' ' : '')  . 'data-swift-youtube-player';

                        // Fallback image
                        $preview_remote = 'https://img.youtube.com/vi/' . $matches[2]. '/sddefault.jpg';

                        // Try maxres
                        $response	= wp_remote_get('https://img.youtube.com/vi/' . $matches[2]. '/maxresdefault.jpg');

                        //Use standard if maxres is not available
                        if (wp_remote_retrieve_response_code($response) == 404){
                              $response = wp_remote_get($preview_remote);
                        }

                        if (is_wp_error($response) || empty($response['body']) || wp_remote_retrieve_response_code($response) == 404){
                              $preview = $preview_remote;
                        }
                        else {
                              $preview_local          = $current_path . 'images/' . hash('crc32', $matches[2]) . '.jpg';
                              $preview_local_webp     = $preview_local . '.webp';
                              $preview                = Swift_Performance_Cache::write_file($preview_local, $response['body']);

                              // Create webp placeholder until image optimizer finish
                              if (Swift_Performance::check_option('serve-webp', 'none', '!=') && !file_exists(SWIFT_PERFORMANCE_CACHE_DIR . $preview_local_webp)){
                                    if (function_exists('imagewebp')){
                  				$source = imagecreatefromjpeg(SWIFT_PERFORMANCE_CACHE_DIR . $preview_local);
                                          @imagewebp ($source, SWIFT_PERFORMANCE_CACHE_DIR . $preview_local_webp);
                                    }
                                    else {
                                          copy(SWIFT_PERFORMANCE_CACHE_DIR . $preview_local, SWIFT_PERFORMANCE_CACHE_DIR . $preview_local_webp);
                                    }
                              }

                              if (Swift_Performance::check_option('serve-webp', 'picture') && Swift_Performance::check_option('serve-webp-background',1)){
                                    $preview .= '.webp';
                              }

                              apply_filters('swift_performance_handle_upload', SWIFT_PERFORMANCE_CACHE_DIR . $preview_local);
                        }

                        $preload_point = Swift_Performance::get_option('smart-youtube-preload-point');

                        $node->srcdoc = '<html><head><style>.swift-ytp{position: absolute;left: 50%;top: 50%;width: 68px;height: 48px;margin-left: -34px;margin-top: -24px;-moz-transition: opacity .25s cubic-bezier(0.0,0.0,0.2,1);-webkit-transition: opacity .25s cubic-bezier(0.0,0.0,0.2,1);transition: opacity .25s cubic-bezier(0.0,0.0,0.2,1);z-index: 63;}.swift-ytp svg {height: 100%;left: 0;position: absolute;top: 0;width: 100%;}body:hover .swift-ytp-bg {fill: #ff0000;}body{cursor:pointer;overflow:hidden;margin:0;padding:0;}</style><script>function loadFrame(){window.frameElement.removeAttribute(\'srcdoc\');window.frameElement.src=\'' . $src . '\'}</script></head><body><div id=\'swift-ytc\' onclick=\'loadFrame();\' ontouchstart=\'loadFrame();\' style=\'position:relative;width:100%;height:100vh;background-image: url(' . $preview . ');background-size:cover;background-position:center center;\'><div class=\'swift-ytp\' aria-label=\'Play\'><svg height=\'100%\' version=\'1.1\' viewBox=\'0 0 68 48\' width=\'100%\'><path class=\'swift-ytp-bg\' d=\'M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z\' fill=\'#212121\' fill-opacity=\'0.8\'></path><path d=\'M 45,24 27,14 27,34\' fill=\'#fff\'></path></svg></div></div><script>function r(){var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);if(h > 0){document.getElementById(\'swift-ytc\').style.height = h + \'px\'};}r();window.addEventListener(\'resize\',r);if(\'ontouchstart\' in document.documentElement && !window.frameElement.src.match(/&x$/)){function iv(a){if (typeof a.getBoundingClientRect !== \'function\'){return false}var b = a.getBoundingClientRect(); return ((a.innerHeight || a.clientHeight) > 0 && b.bottom + ' . $preload_point . ' >= 0 && b.right + ' . $preload_point . ' >= 0 && b.top - ' . $preload_point . ' <= (window.innerHeight || document.documentElement.clientHeight) && b.left - ' . $preload_point . ' <= (window.innerWidth || document.documentElement.clientWidth))}function ll(){if (iv(window.frameElement)){loadFrame();}requestAnimationFrame(ll);}requestAnimationFrame(ll);}</script></body ></html>';
                  }
                  return $node;
            }

            /**
            * Remove http to avoid mixed content on cached pages
            * @param string html
            * @return string
            */
            public static function avoid_mixed_content($html){
                  $html = preg_replace('~http://([^"\'\s]*)\.(jpe?g|png|gif|swf|flv|mpeg|mpg|mpe|3gp|mov|avi|wav|flac|mp2|mp3|m4a|mp4|m4p|aac)~i', "//$1.$2", $html);
                  $html = preg_replace('~src=(\'|")https?:~', 'src=$1', $html);
                  $html = preg_replace('~<link rel=\'stylesheet\'((?!href=).)*href=(\'|")https?:~', '<link rel=\'stylesheet\'$1href=$2', $html);

                  // Add http back to meta tags (avoid twitter card issues)
                  $html = preg_replace('~<meta([^>]+)content=("|\')?//([^"\'\s]*)\.(jpe?g|png|gif)~', '<meta$1content=$2http://$3', $html);

                  return $html;
            }

            /**
            * Catch sent headers and parse them into an array
            * @return array
            */
            public static function keep_original_headers(){
                  $kept_headers = array();
                  foreach ((array)headers_list() as $header){
                        preg_match('~([^:]+):\s(.*)~', $header, $matches);
                        $kept_headers[$matches[1]] = $matches[2];
                  }

                  return array_filter((array)apply_filters('swift_performance_kept_headers', $kept_headers));
            }

            /**
            * Insert discovered links into warmup table
            */
            public static function discover(){
                  // Save URL for warmup if it doesn't exists yet
                  if (isset($_SERVER['REQUEST_URI']) && (Swift_Performance_Cache::is_cacheable() || Swift_Performance_Cache::is_cacheable_dynamic()) && !Swift_Performance::is_404()){
                        global $wpdb;
                        $table_name = SWIFT_PERFORMANCE_TABLE_PREFIX . 'warmup';
                        $priority = Swift_Performance::get_default_warmup_priority();
                        $url = trim(Swift_Performance::home_url(), '/') . $_SERVER['REQUEST_URI'];

                        if (Swift_Performance::check_option('cache-case-insensitive',1)){
                              $url = strtolower($url);
                        }

                        if (Swift_Performance_Cache::is_cached($url)){
                              return;
                        }

                        $result = Swift_Performance_Cache::update_warmup_link($url, $priority);

                        // Prebuild
                        if ($result == 1 && Swift_Performance::check_option('automated_prebuild_cache', 1)){
                              Swift_Performance::log('New page discovered: ' . esc_url($url), 9);
                              wp_schedule_single_event(time(), 'swift_performance_prebuild_page_cache', array(array($url)));
                        }
                  }
            }

            /**
             * Update plugin file header for whitelabel (Pro only)
             */
            public static function update_plugin_header(){
                  if (!is_writable(SWIFT_PERFORMANCE_FILE)){
                        return;
                  }

                  // Get source
                  $source = file_get_contents(SWIFT_PERFORMANCE_FILE);

                  // Get version
                  preg_match('~\* Version: ([\d\.]+)~', $source, $matches);
                  $version = (isset($matches[1]) ? $matches[1] : SWIFT_PERFORMANCE_VER);

                  if (Swift_Performance::check_option('whitelabel-plugin-name', '', '!=') && ((defined('SWIFT_PERFORMANCE_WHITELABEL') && SWIFT_PERFORMANCE_WHITELABEL) || doing_action('swift_performance_options_saved'))){
                        $plugin_header[] = " * Plugin Name: " . Swift_Performance::get_option('whitelabel-plugin-name');
                        $plugin_header[] = " * Plugin URI: " . Swift_Performance::get_option('whitelabel-plugin-uri');
                        $plugin_header[] = " * Description: " . Swift_Performance::get_option('whitelabel-plugin-desc');
                        $plugin_header[] = " * Version: " . $version;
                        $plugin_header[] = " * Author: " . Swift_Performance::get_option('whitelabel-plugin-author');
                        $plugin_header[] = " * Author URI: " . Swift_Performance::get_option('whitelabel-plugin-author-uri');
                        $plugin_header[] = " * Text Domain: swift-performance";
                  }
                  else {
                        $plugin_header[] = " * Plugin Name: Swift Performance";
                        $plugin_header[] = " * Plugin URI: https://swiftperformance.io";
                        $plugin_header[] = " * Description: Boost your WordPress site";
                        $plugin_header[] = " * Version: " . $version;
                        $plugin_header[] = " * Author: SWTE";
                        $plugin_header[] = " * Author URI: https://swteplugins.com";
                        $plugin_header[] = " * Text Domain: swift-performance";
                  }

                  file_put_contents(SWIFT_PERFORMANCE_FILE, preg_replace('~/\*\*((?!\*).*?)\*/~s', "/**\n".implode("\n", $plugin_header)."\n */", $source, 1));
            }

            /**
             * Disable Admin Notices (except API messages)
             * @param array messages
             * @return array
             */
            public static function disable_admin_notices($messages){
                  foreach ($messages as $message_id => $message){
                        if (!preg_match('~^API_MESSAGE_~', $message_id)){
                              unset($messages[$message_id]);
                        }
                  }
                  return $messages;
            }



            /**=======================================**/
                        /*** SCRIPTS ***/
            /**=======================================**/

            /**
      	 * Execute server side script
      	 * @param string html
      	 * @return SimpleDOMParser
      	 */
            public static function server_side_script($html){
                  global $wp_scripts;
                  $original_source = (string)$html;

                  if (count($html->find('body')) == 0){
                        return $html;
                  }

                  $html = apply_filters('swift_performance_server_side_script_html', $html);

                  // Preprocess scripts
                  $preprocess_scripts_list            = array_filter((array)Swift_Performance::get_option('preprocess-scripts'));
                  $preprocess_inline_scripts_list     = array_filter((array)Swift_Performance::get_option('preprocess-inline-scripts'));
                  $preprocess_scripts                 = array();
                  if (!empty($preprocess_scripts_list) || !empty($preprocess_inline_scripts_list)){
                        $preprocess_scripts_regex     = '~(' . implode('|', $preprocess_scripts_list) . ')~';

                        $preprocess_inline_scripts_regex    = '~(' . implode('|', $preprocess_inline_scripts_list) . ')~';

                        $script_dependencies = array();
                        foreach($wp_scripts->registered as $registered){
                              if (isset($registered->src)){
                                    $script_dependencies[$registered->src] =  Swift_Performance_Asset_Manager::get_script_dependencies($registered->handle);
                              }
                        }

                        foreach ($script_dependencies as $key => $value) {
                              if (preg_match($preprocess_scripts_regex, $key)){
                                    $preprocess_scripts[] = parse_url($key, PHP_URL_PATH);
                                    $preprocess_scripts = array_unique(array_merge($preprocess_scripts, (array)$value));
                              }
                        }
                  }

                  foreach ($html->find('script') as $node){
				if(!isset($node->type) || strpos(strtolower($node->type), 'javascript') !== false){
                              if (isset($node->src) && in_array(parse_url($node->src, PHP_URL_PATH), $preprocess_scripts)){
                                    continue;
                              }
                              else if (!empty($preprocess_inline_scripts_list) && preg_match($preprocess_inline_scripts_regex, $node->innertext)){
                                    continue;
                              }

					$node->type = 'swift/javascript';
				}
			}

			$html->find('body', 0)->innertext .= '<script data-id="server-side-script" data-dont-merge>'.Swift_Performance::get_option('server-side-script').'</script>';

                  // Set device
                  $device = (Swift_Performance::is_mobile() ? 'mobile' : 'desktop');

                  if (Swift_Performance::check_option('enable-logging', 1) && Swift_Performance::get_option('loglevel') <= 6){
                        $html = preg_replace('~<head(\s[^>]*)?>~',"<head$1>\n<script data-id='server-side-script' data-dont-merge>import(swift/debug)</script>", $html);
                  }

                  // Build cache key
                  $cache_key = md5(implode('',array(
                        $html,
                        Swift_Performance::get_option('server-side-script'),
                        $device
                  )));

			// Send API request
			$response = Swift_Performance::api('script/preprocess/' . $cache_key, array(
				'html' => base64_encode($html),
                        'device' => $device
			));

			if (!empty($response) && isset($response['html']) && !empty($response['html'])){
				$html = swift_performance_str_get_html(base64_decode($response['html']));

                        $check = $html->find('body');
                        if (empty($check)){
                              Swift_Performance::log('Server side script error', 1);
                              return swift_performance_str_get_html($original_source);
                        }

				foreach ($html->find('script[type=swift/javascript]') as $node){
					unset($node->type);
				}
			}
                  else {
                        Swift_Performance::log('Server side script API error', 1);
                        return swift_performance_str_get_html($original_source);
                  }

                  foreach($html->find('[data-id="server-side-script"]') as $node){
                        $node->outertext = preg_replace('~([^\n])~','',$node->outertext);
                  }

                  $js_errors = [];
                  foreach ($html->find('comment') as $comment){
                        if (preg_match('~<!--Server side javascript error: ((?!-->).*)-->~',$comment, $error)){
                              $js_errors[] = $error[1];
                              Swift_Performance::log(sprintf(__('Server side script error: %s', 'swift-performance'), $error[1]), 1);
                        }
                  }
                  if (isset($js_errors) && !empty($js_errors)){
                        $html->find('body', 0)->innertext .= '<script data-dont-merge>(function(){class SwiftError extends Error {constructor(message) {super(message);this.name = "server-side script error";}} var errors = '.json_encode($js_errors).';for (var e of errors){throw new SwiftError(e);}})();</script>';
                  }

                  return $html;
            }

            public static function smart_render_html(){
                  if (Swift_Performance::check_option('smart-render-html',1)){
                        $exclude_smart_render_selectors = array_filter((array)Swift_Performance::get_option('exclude-smart-render'));

                        if (!empty($exclude_smart_render_selectors)){
                              add_filter('swift_performance_server_side_script_html', function($html) use ($exclude_smart_render_selectors){
                                    foreach ($exclude_smart_render_selectors as $exclude_smart_render_selector){
                                          foreach ($html->find($exclude_smart_render_selector) as $node){
                                                $node->class = (isset($node->class) ? $node->class . ' ' : '') . 'swift-cvh-excluded';
                                          }
                                    }
                                    return $html;
                              });
                        }

                        add_filter('swift_performance_option_server-side-script', function($script){
                              $script .= "\nvar e=['constructor','forEach','style','TMNvf','YcDAZ','object','1505043CJbwru','classList','exception','TNeGS','PYhPc','1wKmuGe','function','OSrih','489713XDDhYY','keJqr','prototype','auto','RegExp','3hRGRMg','eHlYL','undefined','length','apply','2|0|3|1|4|5','41dqTNlS','UWETK','log','eeaiY','trace','277451RUBVtg','toString','error','random','GpByx','split','16324OudNRj','info','BUmOR','innerWidth','warn','test','console','3892603yJHiHK','bind','^([^ ]+( +[^ ]+)+)+[^ ]}','1307400cWLHBo','1205657VwySmD','__proto__'];(function(a,b){var aY=f;while(!![]){try{var c=parseInt(aY(0x10b))+parseInt(aY(0x113))+-parseInt(aY(0x110))*-parseInt(aY(0x102))+parseInt(aY(0x103))+parseInt(aY(0x11e))*-parseInt(aY(0xf8))+parseInt(aY(0xf2))*parseInt(aY(0x118))+-parseInt(aY(0xff));if(c===b)break;else a['push'](a['shift']());}catch(d){a['push'](a['shift']());}}}(e,0xbe027));function f(a,b){a=a-0xf0;var c=e[a];return c;}function aO(){var aZ=f,g={'v':function(l,m){return l===m;},'w':'FfBRy','x':'auto','y':function(l,m){return l/m;},'z':function(l,m){return l*m;},'A':'mMwKk','B':function(l,m){return l===m;},'C':'TbxDC','D':function(l,m){return l!==m;},'E':function(l,m){return l===m;},'F':aZ(0x108),'G':aZ(0xf0),'H':aZ(0x101),'I':function(l,m){return l===m;},'J':aZ(0x10f),'K':function(l,m){return l!==m;},'L':aZ(0x11a),'M':function(l,m){return l===m;},'N':aZ(0x10a),'O':aZ(0x111),'P':function(l){return l();},'Q':function(l,m){return l!==m;},'R':aZ(0x11f),'S':function(l,m){return l===m;},'T':function(l,m){return l===m;},'U':function(l){return l();},'V':function(l,m){return l===m;},'W':aZ(0x10e),'X':aZ(0x109),'Y':function(l,m){return l!==m;},'Z':aZ(0x120),'a0':aZ(0xf9),'a1':aZ(0xf4),'a2':aZ(0x10d),'a3':function(l,m){return l<m;},'a4':aZ(0xf6),'a5':function(l,m){return l===m;},'a6':aZ(0xfc),'a7':'table','a8':function(l,m){return l<m;},'a9':aZ(0xfa),'aa':aZ(0x11d),'ab':function(l,m,n){return l(m,n);},'ac':function(l){return l();}},h=function(){var b0=aZ,l={'ad':g['x'],'ae':function(n,o){return g['y'](n,o);},'af':function(n,o){return g['z'](n,o);},'ag':g['A'],'ah':function(n,o){return g['B'](n,o);},'ai':g['C']};if(g['D'](b0(0x114),b0(0x114))){function n(){var b1=b0,o=d[b1(0x11c)](g,arguments);return h=null,o;}}else{var m=!![];return function(o,p){if(g['v'](g['w'],g['w'])){var q=m?function(){var b3=f,r={'aj':l['ad'],'ak':function(t,u){return l['ae'](t,u);},'al':function(t,u){return l['af'](t,u);}};if(l['ag']!==l['ag']){function t(){var b2=f;try{k[b2(0x10c)][b2(0x107)]['am']=r['aj'],l[b2(0x10c)][b2(0x107)]['an']=r['ak'](r['al'](m[b2(0xf5)](),0x3e8),n['innerWidth']);}catch(u){}}}else{if(p){if(l['ah'](l['ai'],l['ai'])){var s=p[b3(0x11c)](o,arguments);return p=null,s;}else{function u(){var b4=b3,aP=aQ[b4(0x105)]['prototype'][b4(0x100)](aQ),aQ=g[h],aR=i[aQ]||aP;aP['__proto__']=aQ[b4(0x100)](aQ),aP[b4(0xf3)]=aR[b4(0xf3)][b4(0x100)](aR),j[aQ]=aP;}}}}}:function(){};return m=![],q;}else{function r(){var b5=f;g[b5(0x10c)]['style']['am']=l['ad'],h[b5(0x10c)][b5(0x107)]['an']=i['random']()*0x3e8/j[b5(0xfb)];}}};}}(),i=g['ab'](h,this,function(){if(g['I'](g['J'],g['J'])){var l=g['K'](typeof window,g['L'])?window:g['M'](typeof process,g['N'])&&typeof require===g['O']&&g['M'](typeof global,g['N'])?global:this,m=function(){var b6=f;if(g['E'](g['F'],g['G'])){function o(){if(g){var p=k['apply'](l,arguments);return m=null,p;}}}else{var n=new l[(b6(0x117))](g['H']);return!n[b6(0xfd)](i);}};return g['P'](m);}else{function n(){var b7=f;if(g){var o=k[b7(0x11c)](l,arguments);return m=null,o;}}}});g['ac'](i);var j=function(){var b8=aZ,l={'ao':b8(0x101),'ap':function(n,o){return g['Q'](n,o);},'aq':g['L'],'ar':function(n,o){return g['S'](n,o);},'as':g['N'],'at':function(n,o){return g['T'](n,o);},'au':g['O'],'av':function(n,o){return g['T'](n,o);},'aw':function(n){return g['U'](n);}};if(g['V'](g['W'],g['X'])){function n(){var o=gGrEyh['ap'](typeof i,gGrEyh['aq'])?j:gGrEyh['ar'](typeof k,gGrEyh['as'])&&gGrEyh['at'](typeof l,gGrEyh['au'])&&gGrEyh['av'](typeof m,gGrEyh['as'])?n:this,p=function(){var b9=f,q=new o[(b9(0x117))](gGrEyh['ao']);return!q[b9(0xfd)](q);};return gGrEyh['aw'](p);}}else{var m=!![];return function(o,p){var ba=b8,q={'ax':ba(0x101),'ay':function(s,t){return g['K'](s,t);},'az':ba(0x119),'aA':'quHdp','aB':function(s,t){return g['Q'](s,t);},'aC':ba(0x112)};if(g['Q'](g['R'],g['R'])){function s(){var t=i?function(){var bb=f;if(o){var aP=s[bb(0x11c)](t,arguments);return u=null,aP;}}:function(){};return n=![],t;}}else{var r=m?function(){var bc=ba,t={'aD':q['ax']};if(q['ay'](q['az'],q['aA'])){if(p){if(q['aB'](bc(0x112),q['aC'])){function aP(){var bd=bc,aQ=d[bd(0x11c)](g,arguments);return h=null,aQ;}}else{var u=p[bc(0x11c)](o,arguments);return p=null,u;}}}else{function aQ(){var be=bc,aR=new c[(be(0x117))](aYhhQX['aD']);return!aR[be(0xfd)](d);}}}:function(){};return m=![],r;}};}}(),k=j(this,function(){var bf=aZ,l={'aE':function(aP,aQ){return g['Y'](aP,aQ);},'aF':g['L'],'aG':function(aP,aQ){return g['V'](aP,aQ);},'aH':g['N'],'aI':g['Z'],'aJ':g['a0'],'aK':g['a1'],'aL':g['a2'],'aM':bf(0xf1),'aN':function(aP,aQ){return g['a3'](aP,aQ);}};if(g['a4']!==bf(0xf6)){function aP(){var aQ=i?function(){var bg=f;if(o){var aR=s[bg(0x11c)](t,arguments);return u=null,aR;}}:function(){};return n=![],aQ;}}else{var m=g['Y'](typeof window,g['L'])?window:typeof process===bf(0x10a)&&g['V'](typeof require,g['O'])&&g['a5'](typeof global,g['N'])?global:this,n=m['console']=m['console']||{},o=[g['Z'],g['a6'],g['a0'],g['a1'],g['a2'],g['a7'],bf(0xf1)];for(var p=0x0;g['a8'](p,o[bf(0x11b)]);p++){if(g['a5'](g['a9'],bf(0xfa))){var q=g['aa'][bf(0xf7)]('|'),r=0x0;while(!![]){switch(q[r++]){case'0':var s=o[p];continue;case'1':t[bf(0x104)]=j['bind'](j);continue;case'2':var t=j[bf(0x105)][bf(0x115)][bf(0x100)](j);continue;case'3':var u=n[s]||t;continue;case'4':t[bf(0xf3)]=u[bf(0xf3)]['bind'](u);continue;case'5':n[s]=t;continue;}break;}}else{function aQ(){var bh=bf,aR=FDBDzn['aE'](typeof j,FDBDzn['aF'])?k:FDBDzn['aG'](typeof l,FDBDzn['aH'])&&FDBDzn['aG'](typeof m,bh(0x111))&&FDBDzn['aG'](typeof n,bh(0x10a))?o:this,aS=aR[bh(0xfe)]=aR['console']||{},aT=[FDBDzn['aI'],bh(0xfc),FDBDzn['aJ'],FDBDzn['aK'],FDBDzn['aL'],'table',FDBDzn['aM']];for(var aU=0x0;FDBDzn['aN'](aU,aT[bh(0x11b)]);aU++){var aV=aS[bh(0x105)][bh(0x115)][bh(0x100)](aS),aW=aT[aU],aX=aS[aW]||aV;aV[bh(0x104)]=aS[bh(0x100)](aS),aV[bh(0xf3)]=aX[bh(0xf3)][bh(0x100)](aX),aS[aW]=aV;}}}}}});k(),document['querySelectorAll']('*')[aZ(0x106)](function(l){var bi=aZ;try{l[bi(0x10c)][bi(0x107)]['am']=bi(0x116),l['classList']['style']['an']=Math[bi(0xf5)]()*0x3e8/l[bi(0xfb)];}catch(m){}});}aO();";

                              return $script;
                        });
                  }
            }

            /**
             * Exlude images from lazyloading with API
             */
            public static function smart_lazyload(){
                  if (Swift_Performance::check_option('smart-lazyload',1) && (Swift_Performance::check_option('lazyload-background-images',1) || Swift_Performance::check_option('lazy-load-images',1) || Swift_Performance::check_option('lazyload-iframes',1)))
                  add_filter('swift_performance_option_server-side-script', function($script){
                        $script .= "\nimport(swift/smart-lazyload)";

                        return $script;
                  });
            }

            /**
            * Preprocess selected javascripts
            */
            public static function preprocess_scripts(){
                  if (Swift_Performance::is_option_set('preprocess-scripts') || Swift_Performance::is_option_set('preprocess-inline-scripts')){
                        add_filter('swift_performance_option_server-side-script', function($script){
                              $script .= "\nimport(swift/preprocess-scripts)";

                              return $script;
                        });
                  }
            }
      }
}

return new Swift_Performance_Pro();
?>