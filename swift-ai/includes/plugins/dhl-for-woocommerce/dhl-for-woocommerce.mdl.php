<?php

if ((isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'dhl_download_label')) || (defined('DOING_AJAX') && isset($_REQUEST['action']) && $_REQUEST['action'] == 'wc_shipment_dhl_gen_label')){
      add_filter('swift3_skip_optimizer', '__return_true');
      add_filter('swift3_is_request_cacheable', '__return_false');

      add_filter('swift3_check_option_caching', '__return_false');
}

?>