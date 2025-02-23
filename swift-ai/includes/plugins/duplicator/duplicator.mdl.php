<?php

class Swift3_Duplicator_Module {

      public static $plugin_version = 1;

      public static function load(){
            Swift3_Code_Optimizer::add('duplicator/duplicator.php', array(__CLASS__, 'coop'));

            add_action('plugins_loaded', function(){
                  if (self::detected()){
                        Swift3_System::register_includes('duplicator');
                        add_action('swift3_get_install_steps', function(){
                              Swift3_Setup::add_step(21, esc_html__('Duplicator SEO detected', 'swift3'));
                        });
                  }
            });
      }
      public static function coop($plugin, $active_plugins){
            if (!is_admin()){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file(array('index.php','admin.php')) && isset(Swift3_Code_Optimizer::$query_string['page']) && preg_match('~^duplicator~', Swift3_Code_Optimizer::$query_string['page'])){
                  return false;
            }
            if (Swift3_Code_Optimizer::is_current_admin_file('admin-ajax.php') && isset($_REQUEST['action']) && preg_match('~(DUP_|duplicator)~', $_REQUEST['action'])){
                  return false;
            }
            add_action('admin_head', function(){
                  echo '<style>' . file_get_contents(__DIR__ . '/admin.css') . '</style>';
            },7);
            
            add_action('admin_footer', function(){
                  echo '<script>' . file_get_contents(__DIR__ . '/admin.js') . '</script>';
            });



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

Swift3_Duplicator_Module::load();