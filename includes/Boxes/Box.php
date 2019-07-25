<?php

namespace Udesly\Boxes;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class Box
{

    const TYPE_NAME = "udesly_box";

    public static function register_type()
    {

        $labels = array(
            'name' => _x('Boxes', 'post type general name', UDESLY_TEXT_DOMAIN),
            'singular_name' => _x('Box', 'post type singular name', UDESLY_TEXT_DOMAIN),
            'menu_name' => _x('Box', 'admin menu', UDESLY_TEXT_DOMAIN),
            'name_admin_bar' => _x('Box', 'add new on admin bar', UDESLY_TEXT_DOMAIN),
            'add_new' => _x('Add New Box', 'book', UDESLY_TEXT_DOMAIN),
            'add_new_item' => __('Add New Box', UDESLY_TEXT_DOMAIN),
            'new_item' => __('New Box', UDESLY_TEXT_DOMAIN),
            'edit_item' => __('Edit Box', UDESLY_TEXT_DOMAIN),
            'view_item' => __('View Box', UDESLY_TEXT_DOMAIN),
            'all_items' => __('All Boxes', UDESLY_TEXT_DOMAIN),
            'search_items' => __('Search Boxes', UDESLY_TEXT_DOMAIN),
            'parent_item_colon' => __('Parent Box:', UDESLY_TEXT_DOMAIN),
            'not_found' => __('No boxes found.', UDESLY_TEXT_DOMAIN),
            'not_found_in_trash' => __('No boxes found in Trash.', UDESLY_TEXT_DOMAIN)
        );

        register_post_type(self::TYPE_NAME, array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'supports' => array('title', 'editor'),
            'show_in_menu' => 'edit.php?post_type=' . self::TYPE_NAME,
            'publicly_queryable' => false,
            'show_in_rest' => true,
        ));

    }

    public static function change_columns($cols)
    {
        $cols = array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Title', UDESLY_TEXT_DOMAIN),
            'shortcode' => __('Shortcode', UDESLY_TEXT_DOMAIN),
        );


        return $cols;
    }

    public static function custom_columns($column, $post_id)
    {

        $boxes = get_post($post_id);

        switch ($column) {
            case "shortcode":
                echo '<input style="width: 300px;" class="click-to-select" type="text" readonly value="[udesly-boxes slug=&quot;' . $boxes->post_name . '&quot;]" />';
                break;
        }

    }

    public static function render_udesly_box($atts)
    {
        $output = '';

        extract(shortcode_atts(array(
            'slug' => ''
        ), $atts));

        $args = array('post_type' => 'udesly_box', 'name' => trim($slug));
        $box_query = new \WP_Query($args);

        if ($box_query->have_posts()) :
            while ($box_query->have_posts()) : $box_query->the_post();

                $output .= '<div class="clearfix">';
                $output .= the_content();
                $output .= '</div>';

            endwhile;
        endif;
        wp_reset_postdata();

        return $output;
    }

    public static function render_udesly_dynamic_box()
    {

        if (!udesly_has_dynamic_box()) {
            return '';
        }

        $post_id = get_the_ID();

        if (!$post_id)
            return '';

        $post_meta = get_post_meta($post_id, '_udesly_dynamic_box', true);

        $output = '';

        $output .= '<div class="clearfix">';
        $output .= apply_filters('the_content', $post_meta);
        $output .= '</div>';


        return $output;
    }

    public static function add_meta_boxes_dynamic_box($post)
    {

        if (in_array($post, array('udesly_box', 'udesly_posts_query', 'udesly_tax_query', 'udesly_rule_content', 'acf-field-group', 'udesly_cpt')))
            return;

        add_meta_box('box_meta_box_dynamic', // ID attribute of metabox
            __('Dynamic Box', UDESLY_TEXT_DOMAIN),       // Title of metabox visible to user
            array(self::class, 'udesly_boxes_meta_box_dynamic'), // Function that prints box in wp-admin
            $post,              // Show box for posts, pages, custom, etc.
            'normal',            // Where on the page to show the box
            'high');

    }
    public static function udesly_boxes_meta_box_dynamic($post){
        $init = get_post_meta($post->ID, '_udesly_dynamic_box', true);
        wp_editor($init,'udesly_dynamic_box');
    }

    public static function save_dynamic_box($post_id){
        if(isset($_POST['udesly_dynamic_box'])){
            $dynamic_box = wp_kses_post($_POST['udesly_dynamic_box']);
            update_post_meta($post_id, '_udesly_dynamic_box', $dynamic_box);
        }
    }

    public static function public_hooks()
    {
        add_action('init', array(self::class, 'register_type'));
        add_filter('manage_' . self::TYPE_NAME . '_posts_columns', array(self::class, 'change_columns'));
        add_action('manage_' . self::TYPE_NAME . '_posts_custom_column', array(self::class, 'custom_columns'), 10, 2);

        add_shortcode('udesly-boxes', array(self::class, 'render_udesly_box'));
        add_shortcode('udesly_dynamic_box', array(self::class, 'render_udesly_dynamic_box'));

        add_action( 'add_meta_boxes', array(self::class,'add_meta_boxes_dynamic_box' ));
        add_action( 'save_post',  array(self::class, 'save_dynamic_box' ));
    }
}