<?php

class Swift3_Bricks_Builder_Module {

      public function __construct(){
            add_filter('swift3_skip_optimizer', function($result){
                  if (isset($_GET['bricks']) && $_GET['bricks'] == 'run'){
                        return true;
                  }
                  return $result;
            });
      }

}

new Swift3_Bricks_Builder_Module();