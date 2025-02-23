<?php
if (!defined('ABSPATH')){
      die('Keep Calm and Carry On');
}

$bypass_cookies = Swift3_Exclusions::get_bypass_cookies();

?>
# BEGIN SWIFT3
<?php if (Swift3_Helper::check_constant('DISABLE_LSCACHE')):?>
<IfModule LiteSpeed>
CacheDisable public /
</IfModule>
<?php endif;?>

<?php if (swift3_check_option('enable-gzip', 'on')):?>
<?php do_action('swift3_htaccess_compression');?>
# ------------------------------------------------------------------------------
# | Compression                                                                |
# ------------------------------------------------------------------------------
<IfModule mod_deflate.c>

    # Force compression for mangled headers.
    # http://developer.yahoo.com/blogs/ydn/posts/2010/12/pushing-beyond-gzipping
    <IfModule mod_setenvif.c>
        <IfModule mod_headers.c>
            SetEnvIfNoCase ^(Accept-EncodXng|X-cept-Encoding|X{15}|~{15}|-{15})$ ^((gzip|deflate)\s*,?\s*)+|[X~-]{4,13}$ HAVE_Accept-Encoding
            RequestHeader append Accept-Encoding "gzip,deflate" env=HAVE_Accept-Encoding
        </IfModule>
    </IfModule>

    # Compress all output labeled with one of the following MIME-types
    # (for Apache versions below 2.3.7, you don't need to enable `mod_filter`
    #  and can remove the `<IfModule mod_filter.c>` and `</IfModule>` lines
    #  as `AddOutputFilterByType` is still in the core directives).
    <IfModule mod_filter.c>
       AddOutputFilterByType DEFLATE "application/atom+xml" \
                                  "application/javascript" \
                                  "application/json" \
                                  "application/ld+json" \
                                  "application/manifest+json" \
                                  "application/rdf+xml" \
                                  "application/rss+xml" \
                                  "application/schema+json" \
                                  "application/vnd.geo+json" \
                                  "application/vnd.ms-fontobject" \
                                  "application/x-font-ttf" \
                                  "application/x-javascript" \
                                  "application/x-web-app-manifest+json" \
                                  "application/xhtml+xml" \
                                  "application/xml" \
                                  "font/eot" \
                                  "font/opentype" \
                                  "image/bmp" \
                                  "image/svg+xml" \
                                  "image/vnd.microsoft.icon" \
                                  "image/x-icon" \
                                  "text/cache-manifest" \
                                  "text/css" \
                                  "text/html" \
                                  "text/javascript" \
                                  "text/plain" \
                                  "text/vcard" \
                                  "text/vnd.rim.location.xloc" \
                                  "text/vtt" \
                                  "text/x-component" \
                                  "text/x-cross-domain-policy" \
                                  "text/xml"
    </IfModule>
</IfModule>
<?php endif;?>
<?php if (swift3_check_option('browser-cache', 'on')):?>
<?php do_action('swift3_htaccess_browser-cache');?>
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
# ------------------------------------------------------------------------------
# | CORS headers                                                               |
# ------------------------------------------------------------------------------
<IfModule mod_headers.c>
      <?php do_action('swift3_htaccess_cors-headers');?>
      <FilesMatch "\.(ttf|ttc|otf|eot|woff|woff2|font.css|css|js|gif|png|jpe?g|svg|svgz|ico|webp)$">
            Header set Access-Control-Allow-Origin "*.<?php echo Swift3_Helper::get_tld();?>"
      </FilesMatch>
</IfModule>

<?php if (apply_filters('swift3_apache_cache_rewrites', swift3_check_option('caching', 'on'))):?>
# ------------------------------------------------------------------------------
# | Caching                                                                    |
# ------------------------------------------------------------------------------
RewriteEngine On
<?php if (swift3_check_option('force-ssl', 'on')):?>
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
<?php endif;?>
RewriteBase /
RewriteCond %{REQUEST_METHOD} !POST
<?php if (!empty($bypass_cookies)):?>
RewriteCond %{HTTP:Cookie} !^.*(<?php echo esc_attr(implode('|', $bypass_cookies));?>).*$
<?php endif;?>
RewriteCond %{QUERY_STRING} ^$
RewriteCond <?php echo Swift3::get_module('cache')->get_basedir();?>%{HTTP_HOST}%{REQUEST_URI}/__index/index.html -f
RewriteRule (.*) <?php echo Swift3::get_module('cache')->get_reldir();?>%{HTTP_HOST}%{REQUEST_URI}/__index/index.html [L,ENV=SWIFTCACHE:true]
<IfModule mod_headers.c>
<?php do_action('swift3_htaccess_cache-headers');?>
Header set Swift3 "HIT/rewrite" "env=REDIRECT_SWIFTCACHE"
</IfModule>
<?php endif;?>
# END SWIFT3

