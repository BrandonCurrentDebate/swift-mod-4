<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>

<ul class="swift3-dashboard-menu">
      <li>
            <a href="#overview" class="swift3-dashboard-nav active">
                  <?php Swift3_Helper::print_svg('icons/overview')?>
                  <?php esc_html_e('Overview', 'swift3');?>
            </a>
      </li>
      <li>
            <a href="#configuration" class="swift3-dashboard-nav">
                  <?php Swift3_Helper::print_svg('icons/config')?>
                  <?php esc_html_e('Configuration', 'swift3');?>
            </a>
      </li>
      <?php if (!empty(Swift3_Config::$integrations)):?>
      <li>
            <a href="#integrations" class="swift3-dashboard-nav">
                  <?php Swift3_Helper::print_svg('icons/integrations')?>
                  <?php esc_html_e('Integrations', 'swift3');?>
            </a>
      </li>
      <?php endif;?>
      <li>
            <a href="#support" class="swift3-dashboard-nav">
                  <?php Swift3_Helper::print_svg('icons/support')?>
                  <?php esc_html_e('Support', 'swift3');?>
            </a>
      </li>
      <li class="swift3-dashboard-info">
            <span>
                  <?php echo sprintf(esc_html__('Current version: %s', 'swift3'), '<strong>' . Swift3::get_version() . '</strong>');?>
            </span>
            <?php if (Swift3::is_update_available()):?>
                  <a href="<?php echo Swift3::get_update_link();?>">
                        <?php echo sprintf(esc_html__('Update available: %s', 'swift3'), '<strong>' . Swift3::is_update_available() . '</strong>');?>
                  </a>
            <?php endif;?>
      </li>
</ul>
<div class="swift3-dashboard-section" data-id="overview">
      <?php Swift3_Helper::print_template('overview');?>
</div>