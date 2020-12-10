<?php
/**
 *  Misc functions for ACF plugin
 */

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

function udesly_get_current_term($term) {
    if (!$term || !is_object($term)) {
        return get_queried_object();
    }
    else return get_term_by('term_taxonomy_id', $term->term_id);
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

function udesly_get_acf_image_url_term($field_slug, $term)
{
    $image_field = get_field($field_slug, $term);
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

function udesly_acf_subfield_oembed($field)
{
    $oembed = get_sub_field($field);
    if (filter_var($oembed, FILTER_VALIDATE_URL)) // its url and not oembed
    {
        $wp_embed = wp_oembed_get($oembed);
        if ($wp_embed) {
            echo $wp_embed;
        }
    } else {
        echo $oembed;
    }
}

function udesly_acf_field_oembed($field)
{
    $oembed = get_field($field);
    if (filter_var($oembed, FILTER_VALIDATE_URL)) // its url and not oembed
    {
        $wp_embed = wp_oembed_get($oembed);
        if ($wp_embed) {
            echo $wp_embed;
        }
    } else {
        echo $oembed;
    }
}
