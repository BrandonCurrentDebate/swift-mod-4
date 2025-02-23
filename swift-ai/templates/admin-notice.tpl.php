<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>
<div class="notice notice-error">
      <p>
            <strong>Swift Performance AI</strong><br><br>
            <?php echo wp_kses($data['message'], array('a' => array('href' => array(), 'class' => array(),'title' => array()),'br' => array(),'em' => array(),'strong' => array()));?>
      </p>
</div>