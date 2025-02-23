<?php
add_action('init', function(){
      if (function_exists('bp_get_root_domain') && isset($_POST['action']) && $_POST['action'] == 'post_update'){
            $bb_permalink = '';
            if (isset($_POST['object']) && $_POST['object'] == 'group'){
                  if (isset($_POST['item_id']) && !empty($_POST['item_id'])){
                        $group = groups_get_group( array( 'group_id' => $_POST['item_id'] ) );
                        $bb_permalink = trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug . '/' );
                  }
            }
            else if ((!isset($_POST['object']) || empty($_POST['object'])) && (!isset($_POST['item_id']) || empty($_POST['item_id']))){
                  $bb_permalink = str_replace(home_url(), '', bp_get_root_domain() .'/'. bp_get_root_slug());
            }

            if (!empty($bb_permalink)){
                  Swift3_Cache::invalidate_object($bb_permalink);
            }
      }
});