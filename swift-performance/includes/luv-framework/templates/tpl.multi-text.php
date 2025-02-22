<?php
      $is_autocomplete = (isset($field['autocomplete']) && $field['autocomplete'] && isset($field['options']) && !empty($field['options']));
?>

<div class="<?php echo implode(' ', $classes);?>"<?php echo (!empty($default) ? ' data-default="' . $default . '"' : '');?> data-type="<?php echo $field['type'];?>" id="<?php echo esc_attr($prefix . $field['id']);?>-container">
      <div class="luv-framework-multifield-outer<?php echo ($is_autocomplete ? ' is-autocomplete' : '');?> luv-framework-multifield-sample luv-hidden">
            <input class="luv-framework-text-field" type="text"<?php echo (isset($field['placeholder']) ? ' placeholder="' . esc_attr($field['placeholder']) . '"' : ''); ?> name="<?php echo esc_attr($name);?>">
            <a href="#" class="luv-framework-remove-multi-field"><i class="far fa-times-circle"></i></a>
            <?php if ($is_autocomplete):?>
            <a href="#" class="luv-autocomplete-toggle"><i class="fas fa-list"></i></a>
            <div class="luv-autocomplete-wrapper luv-hidden">
                  <input type="text" class="luv-autocomplete-search ignore-change" placeholder="<?php esc_attr_e('Search', 'swift-performance')?>">
                  <ul class="luv-autocomplete-list">
                        <?php foreach((array)$field['options'] as $key => $option):?>
                        <li data-value="<?php echo esc_attr($key)?>"><?php echo esc_html($option);?></li>
                        <?php endforeach?>
                  </ul>
            </div>
            <?php endif;?>
      </div>
      <div class="luv-framework-field-title">
            <a href="#" class="luv-framework-reset-single-field"><?php esc_html_e('RESET TO DEFAULT', 'swift-performance')?></a>

            <strong><?php echo esc_html($label);?></strong>
            <?php if (!empty($info)):?>
                  <a href="#" class="luv-framework-show-info">?</a>
                  <div class="luv-framework-info">
                        <?php echo luv_framework_kses($info);?>
                  </div>
            <?php endif;?>
            <?php if (!empty($description)):?>
                  <div class="luv-framework-field-description">
                        <?php echo esc_html($description); ?>
                  </div>
            <?php endif;?>
      </div>
      <?php if (!empty($field['value'])):?>
            <?php foreach ((array)$field['value'] as $value): ?>
                  <div class="luv-framework-multifield-outer">
                        <input class="luv-framework-text-field" type="text" name="<?php echo esc_attr($name);?>" value="<?php echo esc_attr($value)?>">
                        <a href="#" class="luv-framework-remove-multi-field"><i class="far fa-times-circle"></i></a>
                  </div>
            <?php endforeach; ?>
      <?php else:?>
            <div class="luv-framework-multifield-outer<?php echo ($is_autocomplete ? ' is-autocomplete' : '');?>">
                  <input class="luv-framework-text-field" type="text"<?php echo (isset($field['placeholder']) ? ' placeholder="' . esc_attr($field['placeholder']) . '"' : ''); ?> name="<?php echo esc_attr($name);?>">
                  <a href="#" class="luv-framework-remove-multi-field"><i class="far fa-times-circle"></i></a>
                  <?php if ($is_autocomplete):?>
                  <a href="#" class="luv-autocomplete-toggle"><i class="fas fa-list"></i></a>
                  <div class="luv-autocomplete-wrapper luv-hidden">
                        <input type="text" class="luv-autocomplete-search ignore-change" placeholder="<?php esc_attr_e('Search', 'swift-performance')?>">
                        <ul class="luv-autocomplete-list">
                              <?php foreach((array)$field['options'] as $key => $option):?>
                              <li data-value="<?php echo esc_attr($key)?>"><?php echo esc_html($option);?></li>
                              <?php endforeach?>
                        </ul>
                  </div>
                  <?php endif;?>
            </div>
      <?php endif;?>
      <a href="#" class="luv-framework-button luv-framework-add-multi-field"><?php esc_html_e('Add more', 'luv-framework');?></a>

</div>
