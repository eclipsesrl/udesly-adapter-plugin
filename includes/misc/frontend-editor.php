<?php

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

function udesly_set_fe_items($page_name) {

    $cache = get_transient("udesly_fe_data_$page_name");

    if (false !== $cache) {
        return $cache;
    }else {
        $items = _udesly_set_fe_items($page_name);
        set_transient("udesly_fe_data_$page_name",$items);
        return $items;
    }
}

function udesly_get_fe_items($page_name) {
    $page_slug = "$page_name" . "_udesly_fe_data";
    $fe_page = udesly_get_post_by_slug($page_slug, OBJECT, "udesly_fe_data");

    if ($fe_page) {
        return udesly_mb_unserialize($fe_page->post_content); // fix for some multi char strings;
    }
    return array();
}

function udesly_mb_unserialize($string) {
    $string2 = preg_replace_callback(
        '/(?<=^|\{|;)s:(\d+):\"(.*?)\";(?=[asbdiO]\:\d|N;|\}|$)/s',
        function($m){
            return 's:' . strlen($m[2]) . ':"' . $m[2] . '";';
        },
        $string
    );
    return unserialize($string2);
}

function _udesly_set_fe_items($page_name) {

    $items = udesly_get_fe_items($page_name);

    $theme_dir = trailingslashit(get_stylesheet_directory_uri());

    $udesly_fe_items = array();
    foreach ($items as $key => $value) {
        if (udesly_string_starts_with($key, 'image_')) {
            $value = json_decode($value);

            if(isset($value->id)) {

                $id = $value->id;

                $img = wp_get_attachment_image_src(sanitize_key($id), 'full');


                if ($img) {
                    $value = (object) array(
                        'src' => wp_get_attachment_image_src(sanitize_key($id), 'full')[0],
                        'srcset' => wp_get_attachment_image_srcset(sanitize_key($id)),
                        'alt' => trim(strip_tags(get_post_meta(sanitize_key($id), '_wp_attachment_image_alt', true)))
                    );
                }else {
                    $value = (object) array(
                        'src' => '',
                        'srcset' => '',
                        'alt' => ''
                    );
                }

            }else {
                if (!udesly_string_is_absolute($value->src)) {
                    $value->src = $theme_dir . udesly_string_strip_subdirectory_dots($value->src);
                }
                if (is_array($value->srcset)) {
                    $images = array();
                    foreach ($value->srcset as $img) {
                        if (!udesly_string_is_absolute($img)) {
                            $images[] = $theme_dir . udesly_string_strip_subdirectory_dots($img);
                        }else {
                            $images[] = $img;
                        }
                    }
                    $value->srcset = implode(', ', $images);

                }
            }

        }
        if (udesly_string_starts_with($key, 'bg_image_')) {
            if ($value === 'udesly-no-content') {
                $value = '';
            }else if(is_numeric($value)){
                $img = wp_get_attachment_image_src(sanitize_key($value), 'full')[0];
                $value = "background-image: url($img)";
            }else {
                $value = "background-image: url($theme_dir$value)";
            }
        }
        if (udesly_string_starts_with($key, 'video_')) {
            $videos = '';

            if (is_string($value)) {
                $value = json_decode($value);
            }

            foreach ($value->videos as $video) {
                if (!udesly_string_is_absolute($video)) {
                    $videos .=  "<source src='". $theme_dir . udesly_string_strip_subdirectory_dots($video) . "' data-wf-ignore='true'>";
                }else {
                    $videos .=  "<source src='". $video . "' data-wf-ignore='true'>";
                }
            }

            $value->videos = $videos;
        }
        if (udesly_string_starts_with($key, 'iframe_')) {
            if (!udesly_string_is_absolute($value)) {
                $value = $theme_dir . $value;
            }
        }
        if (udesly_string_starts_with($key, 'link_')) {
            if (!udesly_string_is_absolute($value) && !udesly_string_starts_with($value, 'sms:') && !udesly_string_starts_with($value, 'mailto:') && !udesly_string_starts_with($value, 'tel:') && !udesly_string_starts_with($value, 'javascript:') && !udesly_string_starts_with($value, 'skype:') && !udesly_string_starts_with($value, '#')) {
                // Check if file
                if (strpos($value, '.') !== false) {
                    $value = $theme_dir . $value;
                } else {
                    if ($value === 'index') {
                        $value = get_site_url();
                    } else {
                        $value = udesly_get_permalink_by_slug($value);
                    }
                }
            }
        }
        $udesly_fe_items[$key] = $value;
    }
    return $udesly_fe_items;
}


function udesly_set_fe_configuration($items, $page) {
    if ($items) { ?>
        <script type="application/json" id="udesly-fe-config" data-page="<?php echo $page; ?>" ><?php echo json_encode($items); ?></script>
    <?php }
}