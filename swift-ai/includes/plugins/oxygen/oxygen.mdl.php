<?php
add_filter('swift3_skip_optimizer', function($result){
      if (isset($_GET['ct_builder'])){
            return true;
      }
      return $result;
});

add_filter('swift3_skip_js_optimization', function($result, $tag){
      if (isset($tag->attributes['src']) && strpos($tag->attributes['src'], 'oxyextras/includes/js/gridbuildersupport.js') != false){
            return true;
      }
      return $result;
}, 10, 2);
add_filter('swift3_image_fix_missing_dimensions', function($result, $tag){
      if (isset($tag->attributes['data-original-src-width'])){
            return false;
      }

      return $result;
}, 10, 2);
add_action('save_post', function($post_id) {
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
            return;
      }
      if (get_post_type($post_id) == 'ct_template') {
            Swift3_Cache::invalidate_object();
      }
});
