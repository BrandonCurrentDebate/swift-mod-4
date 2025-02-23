<?php

class Swift3_Yoast_Module {

      public static $plugin_version = 1;

      public static function load(){
            if (file_exists(WP_PLUGIN_DIR . '/wordpress-seo/wp-seo.php')){
                  preg_match('~Version:(\s+)?([\d\.]+)~', file_get_contents(WP_PLUGIN_DIR . '/wordpress-seo/wp-seo.php'), $matches);
                  if (!empty($matches[2])){
                        self::$plugin_version = $matches[2];
                  }

            }

            Swift3_Code_Optimizer::add('wordpress-seo/wp-seo.php', array(__CLASS__, 'coop'));
            Swift3_Code_Optimizer::add('wordpress-seo-premium/wp-seo-premium.php', array(__CLASS__, 'coop'));
            Swift3_Code_Optimizer::add('wpseo-woocommerce/wpseo-woocommerce.php', array(__CLASS__, 'coop'));


            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('yoast');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('Yoast SEO detected', 'swift3'));
                        });
                  }
            });
      }
      public static function coop($plugin, $active_plugins){
            if (!is_admin()){
                  if (isset($_REQUEST['wc-ajax'])){
                        return true;
                  }
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file(array('post-new.php', 'post.php', 'edit.php', 'edit-tags.php'))){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin.php') && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^wpseo_~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~(wpseo|yoast|(focus|term)_keyword_usage|dismiss_(premium_deactivated|first_time_configuration|old_premium))~', $_REQUEST['action'])){
                  return false;
            }
            if ($plugin == 'wordpress-seo/wp-seo.php'){
                  add_action('admin_enqueue_scripts', function(){
                        $flat_version = self::flatten_version();
                        wp_enqueue_style('yoast-adminbar', WP_PLUGIN_URL . '/wordpress-seo/css/dist/adminbar-' . $flat_version . '.css');
                        wp_enqueue_style('yoast-admin-global', WP_PLUGIN_URL . '/wordpress-seo/css/dist/admin-global-' . $flat_version . '.css');
            	});

                  add_action('admin_head', function(){
                        echo '<style>' . file_get_contents(__DIR__ . '/admin.css') . '</style>';
                  },7);
            }


            return true;
      }

      public static function flatten_version() {
		$parts = explode( '.', self::$plugin_version );

		if ( count( $parts ) === 2 && preg_match( '/^\d+$/', $parts[1] ) === 1 ) {
			$parts[] = '0';
		}

		return implode( '', $parts );
	}

      public static function detected(){
            return defined( 'WPSEO_FILE' );
      }

}

Swift3_Yoast_Module::load();