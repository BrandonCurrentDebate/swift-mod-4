<div class="swift3-status-message swift3-hidden" data-text>
      <img class="swift3-status-message-avatar" src="<?php echo SWIFT3_URL?>/assets/images/avatar.png">
      <div class="swift3-status-message-body"></div>
      <div class="swift3-status-message-text swift3-hidden">
            <?php echo wp_kses($data, array(
              'a' => array(
                  'href' => true,
                  'title' => true,
                  'target' => true,
                  'class' => true,
                  'style' => true,
              ),
              'b' => array(),
              'strong' => array(),
              'i' => array(),
              'u' => array(),
              'img' => array(
                  'src' => true,
                  'alt' => true,
                  'title' => true,
                  'width' => true,
                  'height' => true,
                  'class' => true,
                  'style' => true,
                  'id' => true,
              ),
            ));
          ?>
      </div>
</div>