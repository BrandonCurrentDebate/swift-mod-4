<?php
add_action('init', function(){
      if (isset($_POST['bbp_reply_content']) && isset($_POST['bbp_topic_id']) && !empty($_POST['bbp_topic_id'])){
            Swift3_Cache::invalidate($_POST['bbp_topic_id'], 'save_post');
      }
});

?>