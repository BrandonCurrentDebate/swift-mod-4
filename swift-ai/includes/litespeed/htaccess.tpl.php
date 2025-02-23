<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}

$bypass_cookies = Swift3_Exclusions::get_bypass_cookies();

?>
# BEGIN SWIFT3
<?php if (swift3_check_option('browser-cache', 'on')):?>
<?php do_action('swift3_htaccess_compression');?>
# ------------------------------------------------------------------------------
# | Expires headers (for better cache control)                                 |
# ------------------------------------------------------------------------------
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresDefault                                      "access plus 1 month"

  # CSS
    ExpiresByType text/css                              "access plus 1 year"

  # Data interchange
    ExpiresByType application/json                      "access plus 0 seconds"
    ExpiresByType application/xml                       "access plus 0 seconds"
    ExpiresByType text/xml                              "access plus 0 seconds"

  # Favicon (cannot be renamed!)
    ExpiresByType image/x-icon                          "access plus 1 week"

  # HTML components (HTCs)
    ExpiresByType text/x-component                      "access plus 1 month"

  # HTML
    ExpiresByType text/html                             "access plus 0 seconds"

  # JavaScript
    ExpiresByType application/javascript                "access plus 1 year"

  # Manifest files
    ExpiresByType application/x-web-app-manifest+json   "access plus 0 seconds"
    ExpiresByType text/cache-manifest                   "access plus 0 seconds"

  # Media
    ExpiresByType audio/ogg                             "access plus 1 month"
    ExpiresByType image/gif                             "access plus 1 month"
    ExpiresByType image/jpeg                            "access plus 1 month"
    ExpiresByType image/png                             "access plus 1 month"
    ExpiresByType image/webp                            "access plus 1 month"
    ExpiresByType video/mp4                             "access plus 1 month"
    ExpiresByType video/ogg                             "access plus 1 month"
    ExpiresByType video/webm                            "access plus 1 month"

  # Web feeds
    ExpiresByType application/atom+xml                  "access plus 1 hour"
    ExpiresByType application/rss+xml                   "access plus 1 hour"

  # Web fonts
    ExpiresByType application/font-woff                 "access plus 1 year"
    ExpiresByType application/font-woff2                "access plus 1 year"
    ExpiresByType application/vnd.ms-fontobject         "access plus 1 year"
    ExpiresByType application/x-font-ttf                "access plus 1 year"
    ExpiresByType font/opentype                         "access plus 1 year"
    ExpiresByType image/svg+xml                         "access plus 1 year"

</IfModule>
<?php endif;?>
<IfModule mod_headers.c>
      <FilesMatch "\.(ttf|ttc|otf|eot|woff|woff2|font.css|css|js|gif|png|jpe?g|svg|svgz|ico|webp)$">
            Header set Access-Control-Allow-Origin "*.<?php echo Swift3_Helper::get_tld();?>"
      </FilesMatch>
</IfModule>
<?php if (apply_filters('swift3_litespeed_cache_rewrites', swift3_check_option('caching', 'on'))):?>
<IfModule LiteSpeed>
RewriteEngine on
<?php if (swift3_check_option('force-ssl', 'on')):?>
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
<?php endif;?>
CacheLookup on
<?php if (!empty($bypass_cookies)):?>
RewriteRule .* - [E="Cache-Vary:,<?php echo esc_attr(implode(',', $bypass_cookies));?>"]
<?php endif;?>

<?php foreach (Swift3_Exclusions::get_ignored_query_strings() as $qs):?>
CacheKeyModify -qs:<?php echo esc_attr($qs) . "\n"?>
<?php endforeach;?>
</IfModule>
<?php endif;?>
# END SWIFT3

