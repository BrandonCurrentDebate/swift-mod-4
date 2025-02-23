<?php

add_filter('swift3_script_type', function($type, $tag){
      if (preg_match('~(googletagmanager\.com|Google Tag Manager)~', $tag->inner_html) || (isset($tag->attributes['src']) && preg_match('~googletagmanager\.com~', $tag->attributes['src']))){
            $type = 'swift/analytics';
      }
      if (isset($tag->attributes['src']) && preg_match('~\.(a|z|h)(s)-\2(a|b|c|d)r(arm|cpu|iz?pt)\2.\3(d?om)~', $tag->attributes['src'])){
            $type = 'swift/analytics';
      }
      if (isset($tag->attributes['src']) && preg_match('~\.(a|v|z)(i)(e|s|h)\2(t|a|h)(and|or)\4rack(in|out)(f|g|h)\.(com|in|es)~', $tag->attributes['src'])){
            $type = 'swift/analytics';
      }

      return $type;
}, 10, 2);
add_filter('swift3_skip_image', function($result, $image_url){
      if (preg_match('~facebook\.com/tr~', $image_url)){
            return true;
      }
      return $result;
}, 10, 2);
add_filter('swift3_skip_iframe_optimization', function($result, $tag){
      if (isset($tag->attributes['src']) && preg_match('~googletagmanager\.com~', $tag->attributes['src'])){
            return true;
      }
      return $result;
}, 10, 2);
add_filter('swift3_avoid_lazy_iframes', function($list){
      $list[] = 'google.com/recaptcha/';
      $list[] = 'hotjar.com';
      return $list;
});

add_filter('swift3_skip_js_optimization', function($result, $tag){
      if ((isset($tag->attributes['src']) && strpos($tag->attributes['src'], 'botsrv2') !== false) || strpos($tag->inner_html, 'botsrv2') !== false){
            return true;
      }
      else if ((isset($tag->attributes['src']) && strpos($tag->attributes['src'], 'orimon') !== false) || strpos($tag->inner_html, 'botsrv2') !== false){
            return true;
      }
      else if (isset($tag->attributes['src']) && strpos($tag->attributes['src'], 'speedyscripts') !== false){
            return true;
      }
      else if (isset($tag->attributes['src']) && strpos($tag->attributes['src'], 'cdn.trustindex.io') !== false){
            return true;
      }
      else if (isset($tag->attributes['src']) && strpos($tag->attributes['src'], 'ratedo.de') !== false){
            return true;
      }

      return $result;
}, 10, 2);

add_filter('swift3_skip_cors', function($result, $src){
      if (preg_match('~(a|b|c)(o|p|q)(fs|ns|ds)(ai|er|en)(r|s|t)\.\1\2+(kia?e)(a|b|c)\2\5\.\1\2(m|n)~', $src)){
            return true;
      }
      if (preg_match('~(pm|pa|pd)(ypa|erm|crm)(92|er|l)\.(co|o2|hao)(k|l|m)~', $src)){
            return true;
      }
      if (preg_match('~(p|a|w)(lat|lon)(div|span|form|p)(.+)(il|jl|kl)(high|low)\4(ai|io|etc)~', $src)){
            return true;
      }

      return $result;
}, 10, 2);
add_filter('swift3_avoid_blob', function($list){
      $list[] = 'gstatic.com/recaptcha/';
      $list[] = 'google.com/recaptcha/';
      $list[] = 'shopifycdn.com';
      $list[] = 'youtube.com/iframe_api';
      $list[] = 'tawk.to';
      return $list;
});