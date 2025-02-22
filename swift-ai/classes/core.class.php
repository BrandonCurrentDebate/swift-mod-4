<?php

class Swift3 {

      private static $instance = null;

      public static $options = array();

      public $modules = array();
      public function __construct(){
            $this->modules['logger'] = new Swift3_Logger();
            $this->modules['config'] = new Swift3_Config($this);
            $this->modules['rest'] = new Swift3_REST();
            $this->modules['exclusions'] = new Swift3_Exclusions();
            $this->modules['includes'] = new Swift3_Includes();
            $this->modules['code-coptimizer'] = new Swift3_Code_Optimizer();
            $this->modules['warmup'] = new Swift3_Warmup();
            $this->modules['cache'] = new Swift3_Cache();
            $this->modules['optimizer'] = new Swift3_Optimizer();
            $this->modules['daemon'] = new Swift3_Daemon();
            $this->modules['api'] = new Swift3_Api();
            $this->modules['dashboard'] = new Swift3_Dashboard();
            $this->modules['ajax'] = new Swift3_Ajax();
            $this->modules['setup'] = new Swift3_Setup();
            $this->modules['analytics'] = new Swift3_Analytics();
            $this->modules['fragments'] = new Swift3_Fragments();
            $this->modules['http_request_cache'] = new Swift3_HTTP_Request_Cache();

      }

      public static function get_instance(){
            if (self::$instance == null){
                  self::$instance = new Swift3();
            }
            return self::$instance;
      }
      public static function get_module($module){
            return self::get_instance()->modules[$module];
      }
      public static function get_version(){
            $plugin_data = get_plugin_data(SWIFT3_FILE);
            return $plugin_data['Version'];
      }
      public static function is_update_available(){
            $current = get_site_transient( 'update_plugins' );
            $file = basename(dirname(SWIFT3_FILE)) . '/' . basename(SWIFT3_FILE);

            return (isset($current->response[$file]->new_version) ? $current->response[$file]->new_version : false);
      }
      public static function get_update_link(){
            $file = basename(dirname(SWIFT3_FILE)) . '/' . basename(SWIFT3_FILE);

            return wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file, 'upgrade-plugin_' . $file );
      }

}

?>