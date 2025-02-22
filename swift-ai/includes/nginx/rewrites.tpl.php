# BEGIN SWIFT3
<?php if (swift3_check_option('enable-gzip', 'on')):?>
<?php do_action('swift3_nginx_compression');?>

# ------------------------------------------------------------------------------
# | Compression                                                                |
# ------------------------------------------------------------------------------
gzip on;
gzip_disable "msie6";

gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_buffers 16 8k;
gzip_http_version 1.1;
gzip_min_length 256;
gzip_types text/plain text/css application/json application/x-javascript application/javascript text/xml application/xml application/xml+rss text/javascript application/vnd.ms-fontobject application/x-font-ttf font/opentype image/svg+xml image/x-icon;

<?php endif;?>

<?php if (swift3_check_option('browser-cache', 'on')):?>
<?php do_action('swift3_nginx_browser-cache');?>

# ------------------------------------------------------------------------------
# | Expires headers (for better cache control)                                 |
# ------------------------------------------------------------------------------

location ~*  \.(css|js|woff2?|ttf|otf|eot)$ {
      expires 365d;
}
location ~*  \.(jpe?g|png|gif|webp|ico|ogg|mp4|svg)$ {
      expires 30d;
}

<?php endif;?>

<?php if (apply_filters('swift3_nginx_cache_rewrites', swift3_check_option('caching', 'on'))):?>
# ------------------------------------------------------------------------------
# | Caching                                                                    |
# ------------------------------------------------------------------------------

<?php if (swift3_check_option('force-ssl', 'on')):?>
if ($scheme = http) {
      return 301 https://$server_name$request_uri;
}
<?php endif;?>

set $swift_cache 1;
if ($request_method = POST){
	set $swift_cache 0;
}

if ($args != ''){
      set $swift_cache 0;
}

if ($http_cookie ~* "(<?php echo esc_attr(implode('|', Swift3_Exclusions::get_bypass_cookies()));?>)") {
      set $swift_cache 0;
}

if (!-f "<?php echo Swift3::get_module('cache')->get_basedir();?>$http_host/$request_uri/__index/index.html") {
      set $swift_cache 0;
}

if ($swift_cache = 1){
      rewrite .* <?php echo Swift3::get_module('cache')->get_reldir();?>$http_host/$request_uri/__index/index.html last;
}

location <?php echo Swift3::get_module('cache')->get_reldir();?> {
      add_header Swift3 "HIT/rewrite";
}

<?php endif;?>

# ------------------------------------------------------------------------------
# | Self-check                                                                  |
# ------------------------------------------------------------------------------

location /test-swift-rewrites {
      return 200 <?php echo Swift3_Nginx_Module::get_settings_hash();?>;
}

# END SWIFT3