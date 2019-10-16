<?php
/*
 * All functions needed for Blog Functionalities
 *
 */

// Security check
if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
* Gets all categories for current post
 * @param integer $limit number of categories
 * @param int $post_id  the post
 * @return object[]
*/
function udesly_blog_get_categories($limit = 0, $post_id = null)
{
    if (!$post_id) {
        global $post;
        $post_id = $post->ID; // loop post
    }
    $categories_ids = wp_get_post_categories($post_id, array(
        'number' => $limit
    ));

    // Return categories
    $cats = array();

    foreach ($categories_ids as $c) {
        $cat = get_category($c);
        $cats[] = (object)array('name' => $cat->name, 'link' => get_category_link($cat));
    }

    return $cats;
}

/**
* Gets all tags for current post
* @param integer $limit number of tags
* @param int $post_id  the post
* @return object[]
*/
function udesly_blog_get_tags($limit = 0, $post_id = null)
{
    if (!$post_id) {
        global $post;
        $post_id = $post->ID; // loop post
    }
    $categories_ids = wp_get_post_tags($post_id, array(
        'number' => $limit
    ));

    // Return categories
    $cats = array();

    foreach ($categories_ids as $c) {
        $cat = get_category($c);
        $cats[] = (object)array('name' => $cat->name, 'link' => get_tag_link($cat));
    }

    return $cats;
}


function udesly_blog_breadcrumb() {
    $result = array();
    if ( is_category() ) {
        $thisCat           = get_category( get_query_var( 'cat' ), false );
        if ( $thisCat->parent != 0 ) {
            $parents       = get_ancestors( $thisCat->term_id, 'category', 'taxonomy' );
            foreach ( array_reverse( $parents ) as $term_id ) {
                $parent = get_term( $term_id, 'category' );
                array_push( $result,  (object) array(
                    'name' => $parent->name,
                    'href' => get_term_link( $parent->term_id, 'category' ),
                    'type' => 'category'
                ) );
                array_push( $result, (object) array(
                    'type' => 'separator'
                ) );
            }
        }
        $result[] = (object) array( 'name' => $thisCat->name, 'type' => 'current' );
    } elseif ( is_single() && get_post_type() == 'post' ) {
        $parents       = wp_get_post_categories( get_the_ID(), array( 'hide_empty' => 0 ) );
        foreach ( $parents as $term_id ) {
            $parent = get_term( $term_id, 'category' );
            array_push( $result, (object) array(
                'name' => $parent->name,
                'href' => get_term_link( $parent->term_id, 'category' ),
                'type' => 'category'
            ) );
            array_push( $result, (object) array(
                'type' => 'separator'
            ) );
        }
        $result[] = (object) array( 'name' => get_the_title(), 'type' => 'current', 'href' => '#' );

    }

    return $result;
}



function udesly_get_archive_categories() {

    $settings = array();
    $subcategories_type = isset($settings['blog_archive_categories']) ? $settings['blog_archive_categories'] : 0;

    $args = array(
        'taxonomy' => 'category'
    );

    if ( $subcategories_type == 0) {
     return _udesly_sanitize_term_query(new WP_Term_Query($args));
    }


    if ( is_category() ) {
        $category = get_category( get_query_var( 'cat' ) );
        $cat_id   = $category->term_id;
        if ( $subcategories_type == 1 ) {
            $args['parent'] = $cat_id;
        } else {
            $args['child_of'] = $cat_id;
        }

        return _udesly_sanitize_term_query(new WP_Term_Query( $args ));

    } elseif ( is_home() ) {
        if ( $subcategories_type == 1 ) {
            $args['parent'] = 0;
        } else {
            $args['child_of'] = 0;
        }

        return _udesly_sanitize_term_query(new WP_Term_Query( $args ));
    }

    return _udesly_sanitize_term_query(new WP_Term_Query( $args ));
}

function _udesly_sanitize_term_query($query){
    if (isset($query->terms) && !is_wp_error($query)) {
        return $query->terms;
    } else {
        return array();
    }
}

function udesly_get_latest_index_global_query(){
    global $wp_query;
    $page  = max( 1, get_query_var( 'paged' ) );
    $ppp   = get_query_var('posts_per_page');
    $start = $ppp * ( $page - 1 ) + 1;
    $end   = $start + $wp_query->post_count - 1;
    return abs($start-$end);
}

function udesly_get_category_link_by_slug( $slug ) {

    $category = get_category_by_slug( $slug );

    if ( $category ) {
        $category_id = $category->term_id;

        return get_category_link( $category_id );
    }

    return '#';

}

function udesly_get_tag_link_by_slug( $slug ) {

    $link = get_term_link( $slug, 'post_tag' );
    if(!is_wp_error($link)) {
        return $link;
    }else {
        return '#';
    }

}

function udesly_get_authors() {
    global $wpdb;

    $defaults = array(
        'orderby'       => 'name',
        'order'         => 'ASC',
        'number'        => '',
        'exclude_admin' => false,
        'hide_empty'    => true,
        'exclude'       => '',
        'include'       => '',
    );

    $args = apply_filters('udesly_get_authors_args', $defaults);

    $query_args           = wp_array_slice_assoc( $args, array( 'orderby', 'order', 'number', 'exclude', 'include' ) );
    $query_args['fields'] = 'ids';
    $authors              = get_users( $query_args );

    $author_count = array();
    foreach ( (array) $wpdb->get_results( "SELECT DISTINCT post_author, COUNT(ID) AS count FROM $wpdb->posts WHERE " . get_private_posts_cap_sql( 'post' ) . ' GROUP BY post_author' ) as $row ) {
        $author_count[ $row->post_author ] = $row->count;
    }

    $result = [];

    foreach ( $authors as $author_id ) {
        $posts = isset($author_count[$author_id]) ? $author_count[$author_id] : 0;

        if (!$posts && $args['hide_empty']) {
            continue;
        }

        $author = get_userdata($author_id);

        if (!$author) {
            continue;
        }

        if ($args['exclude_admin'] && 'admin' === $author->display_name) {
            continue;
        }

        $result[] = (object) array(
            "ID" => $author->ID,
            "display_name" => $author->display_name,
            "first_name" => $author->first_name,
            "last_name" => $author->last_name,
            "email" => $author->user_email,
            "description" => $author->description,
            "website" => $author->user_url,
            "facebook" => get_user_meta($author->ID, "facebook", true),
            "linkedin" => get_user_meta($author->ID, "linkedin", true),
            "youtube" => get_user_meta($author->ID, "youtube", true),
            "twitter" => get_user_meta($author->ID, "twitter", true),
            "dribble" => get_user_meta($author->ID, "dribble", true),
            "instagram" => get_user_meta($author->ID, "instagram", true),
            "reddit" => get_user_meta($author->ID, "reddit", true),
            "phone" => get_user_meta($author->ID, "phonenumber", true),
            "url" => get_author_posts_url($author->ID),
            "avatar" => get_avatar_url($author->user_email),
        );
    }

    return $result;

}