<?php
/**
 *  Misc functions for ACF plugin
 */

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 *
 * Gets image url based on ACF field
 *
 * @param $field_slug
 * @param $subfield bool
 * @return mixed|string
 */
function udesly_get_acf_image_url($field_slug, $subfield = false)
{
    $image_field = $subfield ? get_sub_field($field_slug) : get_field($field_slug);
    if (empty($image_field)) {
        return '';
    }
    if (is_array($image_field)) {
        return $image_field['url'];
    }
    if (is_numeric($image_field)) {
        $image_src = wp_get_attachment_image_src($image_field, 'full');
        if ($image_src) {
            return $image_src[0];
        }
        return '';
    }
    return $image_field;
}

