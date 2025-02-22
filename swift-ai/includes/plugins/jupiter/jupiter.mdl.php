<?php
      add_filter('swift3_image_handler_img_tag', function($tag){
            if ((defined('JUPITERX_VERSION') || defined('V2ARTBEESAPI')) && isset($tag->attributes['src']) && !empty($tag->attributes['data-mk-image-src-set'])){
                  $maybe_decoded = json_decode($tag->attributes['data-mk-image-src-set'], true);
                  if (!empty($maybe_decoded)){
                        if (!empty($maybe_decoded['2x'])){
                              $tag->attributes['src'] = $maybe_decoded['2x'];
                              unset($tag->attributes['data-mk-image-src-set']);
                        }
                        else if (!empty($maybe_decoded['default'])){
                              $tag->attributes['src'] = $maybe_decoded['default'];
                              unset($tag->attributes['data-mk-image-src-set']);
                        }
                  }
            }

            return $tag;
      });

?>