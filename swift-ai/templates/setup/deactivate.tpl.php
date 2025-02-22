<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>
<?php Swift3_Helper::print_svg('ai-background-static');?>
<img id="swift3-logo" src="<?php echo SWIFT3_URL;?>assets/images/logo.png" width="200" height="120">
<ul id="swift3-setup-deactivate">
      <li>
            <a href="<?php echo wp_nonce_url(admin_url('plugins.php?swift3-deactivate=temporary'), 'swift3-deactivate')?>"><?php esc_html_e('Temporary deactivation', 'swift3');?></a>
            <span><?php esc_html_e('Keep settings, cache and optimized images', 'swift3');?></span>
      </li>
      <li>
            <a href="<?php echo wp_nonce_url(admin_url('plugins.php?swift3-deactivate=uninstall'), 'swift3-deactivate')?>"><?php esc_html_e('Deactivate and uninstall', 'swift3');?></a>
            <span><?php esc_html_e('All your settings, and optimized images will be lost', 'swift3');?></span>
      </li>
</ul>
<a href="<?php echo admin_url('plugins.php');?>" class="swift3-cancel-deactivate"><?php esc_html_e('Cancel', 'swift3');?></a>
