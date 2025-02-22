<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}
?>
<div class="swift3-notice" data-notice="<?php echo (isset($data['log-key']) ? 'id/' . esc_attr($data['log-key']) : 'group/' . $data['group']);?>" data-hash="<?php echo hash('crc32', json_encode($data))?>">
      <?php echo wp_kses($data['message'], array('a' => array('href' => array(), 'data-swift3' => array(), 'class' => array(),'title' => array()),'br' => array(),'em' => array(),'strong' => array(), 'span' => array()));?>
      <?php if (!isset($data['permanent']) || $data['permanent'] !== true):?>
      <a href="#" data-swift3="dismiss">&times;</a>
      <?php endif;?>
</div>