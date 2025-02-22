<?php

class Swift3_WooCommerce_Module {

      public static $admin_slugs = array('wc-admin', 'coupons-moved', 'wc-reports', 'wc-settings', 'wc-status', 'wc-addons', 'wc-orders', 'nbdesigner_manager_product');

      public static $post_types = array('shop_order', 'shop_coupon', 'product', 'shop_subscription');

      public function __construct(){
            add_action('init', array($this, 'init'));
            if (swift3_check_option('price-fragments', 'on') && Swift3_Fragments::should_fragment()){
                  add_filter('swift3_fragment_woo-price', array(__CLASS__, 'price_fragment'), 10, 2);

                  add_filter('woocommerce_get_price_html', array(__CLASS__, 'fragment_wrapper'), 10, 2);

                  add_filter('swift3_optimizer_scripts', function($scripts){
                        $scripts['woo_price_fragments'] = "document.addEventListener('swift/js', function(){jQuery( '.single_variation_wrap' ).on( 'show_variation', load_fragments);})";
                        return $scripts;
                  });
            }

            // Scheduled sales
            add_action('wc_after_products_starting_sales', array(__CLASS__, 'invalidate_cache'));
            add_action('wc_after_products_ending_sales', array(__CLASS__, 'invalidate_cache'));
            add_filter('swift3_checksum_source', array(__CLASS__, 'checksum'));
            if (isset($_REQUEST['wc-ajax'])){
                  add_filter('swift3_skip_optimizer', '__return_true');
            }

            add_filter('swift3_image_handler_img_tag', array(__CLASS__, 'remove_hidden_attr'));
            Swift3_Code_Optimizer::add('woocommerce/woocommerce.php', array(__CLASS__, 'coop'));
            add_action('swift3_code_optimizer_before_check_plugin', array(__CLASS__, 'coop_extensions'));
            add_action('swift3_before_shortcode_fragment_callback', array(__CLASS__, 'fragment_mimic_product_page'));
      }

      public function init(){
            if(defined('WC_PLUGIN_FILE') || in_array('woocommerce/woocommerce.php', Swift3_Code_Optimizer::$disabled_plugins)){
                  Swift3_Config::register_integration_panel('woocommerce', __('WooCommerce', 'swift3'), array(__CLASS__, 'settings_panel'));
                  Swift3_Config::register_settings(array(
                        'prebuild-product-variation' => array('', false, array(array('Swift3_Cache', 'invalidate'))),
                        'checkout-booster' => array('on', false, array(array('Swift3_Cache', 'purge_object'))),
                        'price-fragments' => array('', false, array(array('Swift3_Cache', 'purge_object'))),
                  ));
                  if (swift3_check_option('prebuild-product-variation', 'on')){
                        Swift3_Exclusions::add_allowed_query_parameter('product_variation');
                        add_filter('swift3_warmup_post_types', function($post_types){
                              $post_types[] = 'product_variation';
                              return $post_types;
                        });
                        add_filter('swift3_allowed_query_parameters', function($query_parameters){
                              return array_merge($query_parameters, self::get_variation_attributes());
                        });
                        add_filter('swift3_single_priority', function($priority, $post){
                              if ($post->post_type == 'product_variation'){
                                    return 6;
                              }

                              return $priority;
                        }, 10, 2);
                        add_action('swift3_page_discovered', function($url, $priority){
                              $attributes = self::get_variation_attributes();
                              if (!empty($attributes) && preg_match('~\?(' . implode('|', $attributes) . ')~', $url)){
                                    Swift3_Warmup::update_url($url, array('priority' => 6));
                              }
                        }, 10, 2);
                  }
                  if (swift3_check_option('checkout-booster', 'on')){
                        add_action('template_redirect', array(__CLASS__, 'checkout_booster'));

                        add_action('swift3_optimizer_scripts', array(__CLASS__, 'add_scripts'));
                  }
                  add_filter('swift3_get_archive_urls', array(__CLASS__, 'get_archive_urls'), 10, 2);

                  if (swift3_check_option('caching', 'on')){
                        add_action('woocommerce_product_object_updated_props', array(__CLASS__, 'invalidate_cache'));
                        add_action('woocommerce_product_set_stock', array(__CLASS__, 'invalidate_cache'));
                        add_action('woocommerce_variation_set_stock', array(__CLASS__, 'invalidate_cache'));
                        add_action('wc_after_products_starting_sales', array(__CLASS__, 'invalidate_cache'));
                        add_action('wc_after_products_ending_sales', array(__CLASS__, 'invalidate_cache'));
                  }
                  add_filter('swift3_url_match_excluded', function($urls){
                        foreach (self::get_excluded_pages() as $page_id){
                              $urls[] = get_permalink($page_id);
                        }
                        return $urls;
                  });
                  add_filter('swift3_prefetch_ignore', array(__CLASS__, 'prefetch_ignore'));
                  add_filter('swift3_is_priority_3', function($result){
                        return (function_exists('is_product_category') && is_product_category());
                  });
                  Swift3_System::register_includes('woocommerce');
                  add_action('swift3_get_install_steps', function(){
                        Swift3_Setup::add_step(21, esc_html__('WooCommerce detected', 'swift3'));
                  });
            }
            if ((isset($_REQUEST['_wc_notice_nonce']) && wp_verify_nonce($_GET['_wc_notice_nonce'], 'woocommerce_hide_notices_nonce')) || (isset($_REQUEST['wc_db_update_nonce']) && wp_verify_nonce($_GET['wc_db_update_nonce'], 'wc_db_update'))){
                  Swift3_Code_Optimizer::clear_admin_cache();
            }
      }
      public static function settings_panel(){
            include 'settings.tpl.php';
      }
      public static function invalidate_cache($object){
            if (is_array($object)){
                  foreach ($object as $element){
                        self::invalidate_cache($object);
                  }
                  return;
            }
            if (!is_object($object)){
                  $object = wc_get_product($object);
            }

            Swift3_Cache::invalidate($object->get_id(), 'save_post');
      }
      public static function get_excluded_pages(){
            return apply_filters('swift3_excluded_woocommerce_pages', Swift3_Helper::$db->get_col("SELECT option_value FROM " . Swift3_Helper::$db->options . " WHERE option_name IN ('woocommerce_cart_page_id', 'woocommerce_checkout_page_id', 'woocommerce_myaccount_page_id') AND option_value != ''"));
      }
      public static function get_archive_urls($urls, $post_id){
            $namespace  = 'wp/v2/';

            $urls[] = get_permalink(get_option('woocommerce_shop_page_id'));
            if (function_exists('wc_get_product')){
                  $maybe_product = wc_get_product($post_id);
                  if (!empty($maybe_product) && get_class($maybe_product) == 'WC_Product_Variable'){
                        foreach ((array)$maybe_product->get_available_variations() as $variation){
                              $variation_url = get_permalink($variation['variation_id']);
                              Swift3_Warmup::maybe_insert_url($variation_url, 6);
                              $urls[] = $variation_url;
                        }
                  }
            }
            $tags = get_the_tags($post_id);
            if (!empty($tags)){
                  foreach ((array)$tags as $tag){
                        $urls[] = get_tag_link($tag->term_id);
                        if (function_exists('get_rest_url')){
      			      $urls[] = get_rest_url() . $namespace . 'tags/' . $tag->term_id . '/';
                        }
                  }
            }

            return $urls;
      }
      public static function checkout_booster(){
            if (isset($_SERVER['HTTP_S3_CHECKOUT_BOOSTER']) && (is_cart() || is_checkout())){
                  header("Cache-control: max-age=60");
                  header('Expires: '.gmdate('D, d M Y H:i:s', time()+60).' GMT');
            }
      }
      public static function price_fragment($html, $data){
            $maybe_product = wc_get_product($data[1]);
            if (!empty($maybe_product)){
                  return $maybe_product->get_price_html();
            }

            return $html;
      }

      public static function fragment_wrapper($content, $product){
            if (!is_admin() && !defined('REST_REQUEST') && !Swift3_Helper::check_constant('DOING_FRAGMENTS') && apply_filters('swift3_woocommerce_price_fragment', true, $product)){
                  $request = base64_encode(json_encode(array('woo-price', $product->get_id())));
                  return '<span id="s3-'.hash('crc32', $request).'" class="swift3-fragment" data-request="'.$request.'">'.$content.'</span>';
            }
            return $content;
      }

      public static function add_scripts($scripts){
            if (function_exists('wc_get_cart_url')){
                  $scripts['chkbstr/1'] = 'var chkbstr = ' . json_encode(array('crt' => wc_get_cart_url(), 'chk' => wc_get_checkout_url(), 'atc' => (isset($_REQUEST['add-to-cart']) || isset($_REQUEST['removed-item'])), 'fr' => (is_cart() || is_checkout()) ,'chash' => (isset($_COOKIE['woocommerce_cart_hash']) ? $_COOKIE['woocommerce_cart_hash'] : md5('dummy'))));
                  $scripts['chkbstr/2'] = file_get_contents(__DIR__ . '/checkout-booster.js');
            }
            return $scripts;
      }

      public static function get_variation_attributes(){
            return (array)Swift3_Helper::$db->get_col("SELECT DISTINCT meta_key FROM `".Swift3_Helper::$db->postmeta."` WHERE `meta_key` LIKE 'attribute_%'");
      }

      public static function prefetch_ignore($list){
            $list[] = 'add-to-cart';
            return $list;
      }
      public static function checksum($code){
            $code = preg_replace('~<section class="related products">(?:(?!:</section>).)*</section>~is', '', $code);
            $code = preg_replace('~quantity_([0-9abcdef]{13})~i', '', $code);

            return $code;
      }
      public static function remove_hidden_attr($img){
            if (isset($img->attributes['class']) && strpos($img->attributes['class'], 'variable-item-image') !== false){
                  $img->remove_attribute('hidden');
            }
            return $img;
      }
      public static function coop($plugin){
            if (!is_admin() && !preg_match('~^/wp-json/~', Swift3_Code_Optimizer::$url['path'])){
                  return false;
            }
            if (isset(Swift3_Code_Optimizer::$query_string['post_type']) && in_array(Swift3_Code_Optimizer::$query_string['post_type'], self::$post_types)){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file(array('post.php')) && isset(Swift3_Code_Optimizer::$query_string['action']) && in_array(Swift3_Code_Optimizer::$query_string['action'], array('trash', 'delete'))){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file(array('post.php', 'post-new.php'))) {
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && (in_array(Swift3_Code_Optimizer::$query_string['page'], self::$admin_slugs) || strpos(Swift3_Code_Optimizer::$query_string['page'], 'woo') !== false)){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('options-permalink.php')){
                  return false;
            }
            if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['action']) && preg_match('~^(woo|wcs?_|wp_async_request|(edit|replyto)-comment)~', $_REQUEST['action'])){
                  return false;
            }
            if (preg_match('~^/wp-json/wc-(admin|analytics)~', Swift3_Code_Optimizer::$url['path'])){
                  return false;
            }
      	add_filter('display_post_states', function($states, $post){
                  if ( self::get_page_id( 'shop' ) === $post->ID ) {
      			$post_states['wc_page_for_shop'] = __( 'Shop Page', 'woocommerce' );
      		}

      		if ( self::get_page_id( 'cart' ) === $post->ID ) {
      			$post_states['wc_page_for_cart'] = __( 'Cart Page', 'woocommerce' );
      		}

      		if ( self::get_page_id( 'checkout' ) === $post->ID ) {
      			$post_states['wc_page_for_checkout'] = __( 'Checkout Page', 'woocommerce' );
      		}

      		if ( self::get_page_id( 'myaccount' ) === $post->ID ) {
      			$post_states['wc_page_for_myaccount'] = __( 'My Account Page', 'woocommerce' );
      		}

      		if ( self::get_page_id( 'terms' ) === $post->ID ) {
      			$post_states['wc_page_for_terms'] = __( 'Terms and Conditions Page', 'woocommerce' );
      		}

      		return $states;
            }, 11, 2);

            add_action('admin_enqueue_scripts', function(){
                  if (strpos(Swift3_Code_Optimizer::$admin_cache_raw, 'woocommerce-message') !== false)
                  wp_enqueue_style('woocommerce-activation', WP_PLUGIN_URL . '/woocommerce/assets/css/activation.css');
            });



            return true;
      }
      public static function coop_extensions($plugin){
            if (preg_match('~((_|-)(woo(commerce)?)|((woo(commerce)?))(_|-))~', $plugin)){
                  Swift3_Code_Optimizer::add($plugin, array(__CLASS__, 'coop'));
            }
      }
      public static function fragment_mimic_product_page($data){
            if (!empty($data[1]) && function_exists('wc_get_product')){
                  global $product;
                  $product = wc_get_product($data[1]);
            }
      }

      public static function get_page_id( $page ) {
      	if ( 'pay' === $page || 'thanks' === $page ) {
      		$page = 'checkout';
      	}
      	if ( 'change_password' === $page || 'edit_address' === $page || 'lost_password' === $page ) {
      		$page = 'myaccount';
      	}

      	$page = apply_filters( 'woocommerce_get_' . $page . '_page_id', get_option( 'woocommerce_' . $page . '_page_id' ) );

      	return $page ? absint( $page ) : -1;
      }

}

new Swift3_WooCommerce_Module();