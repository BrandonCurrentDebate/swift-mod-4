<?php

class Swift3_ACF_Module {

      public function __construct(){
            add_action('acf/save_post', array('Swift3_Cache', 'invalidate_object'));
            if (!empty($_POST['_acf_changed'])){
                  Swift3_Cache::invalidate_object();
            }
      }

}

new Swift3_ACF_Module();