<?php
add_filter('swift3_skip_optimizer', function($result){
      if (isset($_GET['breakdance']) || isset($_GET['breakdance_iframe'])){
            return true;
      }
      return $result;
});
add_action('save_post', function($post_id) {
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
            return;
      }
      if (get_post_type($post_id) == 'breakdance_template') {
            Swift3_Cache::invalidate_object();
      }
});