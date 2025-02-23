<?php

/**
 * %PLUGIN_HEADER%
 */

class Swift3_Loader {

	public static function load(){
		wp_cookie_constants();
		$plugins = get_option('active_plugins');
		$plugin_file = '%PLUGIN_DIR%main.php';
		if (in_array('%PLUGIN_BASENAME%', (array)$plugins) && file_exists($plugin_file)){
			include_once $plugin_file;
		}
	}
}
Swift3_Loader::load();
?>
