<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}

global $title, $hook_suffix, $current_screen, $wp_locale, $pagenow, $wp_version,
		$update_title, $total_update_count, $parent_file;
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta name="viewport" content="width=device-width" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo sprintf(esc_html__('%s setup', 'swift-performance'), 'Swift Performance');?></title>
	<script type="text/javascript">
	addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
	var ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>',
		pagenow = '',
		typenow = '',
		adminpage = '',
		thousandsSeparator = '<?php echo addslashes( $wp_locale->number_format['thousands_sep'] ); ?>',
		decimalPoint = '<?php echo addslashes( $wp_locale->number_format['decimal_point'] ); ?>',
		isRtl = <?php echo (int) is_rtl(); ?>;
	</script>
	<?php do_action( 'admin_print_styles' ); ?>
	<?php do_action( 'admin_print_scripts' );?>
	<?php do_action( 'admin_head' ); ?>
</head>
<body class="wp-core-ui swift3-setup <?php echo esc_attr($current_screen->base)?>">
	<div id="swift3-setup-wrapper">
	      <?php Swift3_Helper::print_template('setup/' . $data['template']);?>
	</div>
</body>
</html>