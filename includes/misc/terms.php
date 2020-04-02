<?php
/**
 * Created by PhpStorm.
 * User: Pietro
 * Date: 11/03/2019
 * Time: 10:34
 */

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 * Gets the terms from the database
 *
 * @param string $taxonomy
 *
 * @param int $limit max number of terms to get
 *
 * @return object[]
 */
function udesly_get_terms($taxonomy, $limit = 0)
{

    global $post;

    $args = array(
        "number" => $limit,
        "hide-empty" => true,
    );



    if (get_post_type() === 'product') {
        $settings = \Udesly\Dashboard\Views\Settings::get_wc_settings();
        if(isset($settings['show_categories']) && $settings['show_categories'] !== 'all') {
            $args[$settings['show_categories']] = get_queried_object_id();
        }
    } else {
        $settings = \Udesly\Dashboard\Views\Settings::get_blog_settings();
        if(isset($settings['show_categories']) && $settings['show_categories'] !== 'all') {
            $args[$settings['show_categories']] = get_queried_object_id();
        }
    }


    $result = array();

    $args = apply_filters('udesly_get_terms_args', $args, $post->ID);

    $posts_term = get_terms($taxonomy, $args);

    foreach ($posts_term as $term) {

        $result[] = (object)array(
            "name" => $term->name,
            "permalink" => get_term_link($term->term_id),
            "description" => $term->description,
            "term_id" => $term->term_id,
            "count" => $term->count
        );
    }

    return $result;
}

/**
 * Gets Term featured image
 *
 * @param $term_id
 * @param string $dimension
 * @param $override
 * @return mixed
 */
function udesly_get_term_featured_image($term_id = null, $dimension = 'full', $override = false)
{

    if (!$term_id) {
        $term_id = get_queried_object_id();
    }
    $type = get_post_type();
    $term = get_term( $term_id );

    if ("product" === $type && !$override) { // override is used only on product attributes
        $key = 'thumbnail_id';
        if (function_exists('is_product_tag') && is_product_tag()) {
            $key = '_featured_image';
        }
    } else {
        $key = '_featured_image';
    }

    if ($term->taxonomy === "product_tag" || $term->taxonomy === "product_cat") {
        $key = 'thumbnail_id';
    }

    $img = get_term_meta($term_id, $key, true);
    if ($img) {
        $dimension = apply_filters('udesly_get_term_featured_image_dimension', $dimension);
        $img_url = wp_get_attachment_image_src($img, $dimension)[0];
    }
    else {
        $img_url = "";
    }

    if ($img_url == "" && ("product" == $type || $term->taxonomy === "product_tag" || $term->taxonomy === "product_cat")) {
        if ($key == "_featured_image") {
            $key = "thumbnail_id";
        } else {
            $key = "_featured_image";
        }
        $img = get_term_meta($term_id, $key, true);
        if ($img) {
            $dimension = apply_filters('udesly_get_term_featured_image_dimension', $dimension);
            $img_url = wp_get_attachment_image_src($img, $dimension)[0];
        }
        else {
            $img_url = "";
        }
        if ($img_url == "") {
            $img_url = esc_url( wc_placeholder_img_src() );
        }
    }

    return apply_filters('udesly_get_term_featured_image', $img_url, $term_id);
}


function udesly_get_main_category($id = null){
    if(!$id) {
        global $post;
        $id = $post->ID;
    }

    $main_category_id = get_post_meta($id, '_udesly_main_category', true);

    $term = get_term($main_category_id);

    if(is_null($term) || is_wp_error($term))
        return;

    return (object) array(
        'name' => $term->name,
        'url' => get_term_link($term),
    );
}

function udesly_get_taxonomies($taxonomy, $limit = 0, $post_id = null)
{
    if (!$post_id) {
        global $post;
        $post_id = $post->ID; // loop post
    }

    $taxonomy = $taxonomy != '' ? $taxonomy : 'cat';

    $tax_ids = wp_get_post_terms($post_id, $taxonomy, array(
        'number' => $limit
    ));

    // Return categories
    $taxonomies = array();

    foreach ($tax_ids as $tax) {
        $taxonomies[] = (object)array('name' => $tax->name, 'link' => get_term_link($tax));
    }

    return $taxonomies;
}