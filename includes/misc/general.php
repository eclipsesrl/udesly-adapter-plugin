<?php
/**
 * Created by PhpStorm.
 * User: Pietro
 * Date: 12/03/2019
 * Time: 10:06
 */

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 * Gets a post of $post_type by slug
 *
 * @param $page_slug
 * @param string $output
 * @param string $post_type
 * @return array|WP_Post|null
 */
function udesly_get_post_by_slug($page_slug, $output = OBJECT, $post_type = 'page' ){
    global $wpdb;
    $page_slug = sanitize_title($page_slug);
    if ( is_array( $post_type ) ) {
        $post_type = esc_sql( $post_type );
        $post_type_in_string = "'" . implode( "','", $post_type ) . "'";
        $sql = $wpdb->prepare( "
			SELECT ID
			FROM $wpdb->posts
			WHERE post_name = %s
			AND post_type IN ($post_type_in_string)
		", $page_slug );
    } else {
        $sql = $wpdb->prepare( "
			SELECT ID
			FROM $wpdb->posts
			WHERE post_name = %s
			AND post_type = %s
		", $page_slug, $post_type );
    }
    $page = $wpdb->get_var( $sql );
    if ( $page )
        return get_post( $page, $output );
    return null;
}

/**
 * Gets permalink by slug
 *
 * @param $page_slug
 * @param string $output
 * @param string $post_type
 * @return false|string
 */
function udesly_get_permalink_by_slug($page_slug, $output = OBJECT, $post_type = 'page' ){

    if(strpos($page_slug, '#') === 0){
        return $page_slug;
    }

    $post = udesly_get_post_by_slug($page_slug, $output, $post_type);

    if(!is_null($post) || is_wp_error($post))
        return get_permalink($post);

    return $page_slug;
}

/**
 * Gets Guid by Slug
 *
 * @param $page_slug
 * @param string $output
 * @param string $post_type
 * @return string
 */
function udesly_get_guid_by_slug($page_slug, $output = OBJECT, $post_type = 'page' ){
    $post = udesly_get_post_by_slug($page_slug, $output, $post_type);

    if(!is_null($post) || is_wp_error($post))
        return $post->guid;

    return '#';
}

function udesly_get_numbers_links(){

    $saved_settings = \UdyWfToWp\Plugins\ContentManager\Settings\Settings_Manager::get_saved_settings();

    global $wp_query, $wp_rewrite;
    $args = array();
    // Setting up default values based on the current URL.
    $pagenum_link = html_entity_decode( get_pagenum_link() );
    $url_parts    = explode( '?', $pagenum_link );

    // Get max pages and current page out of the current query, if available.
    $total   = isset( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;
    $current = get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1;

    // Append the format placeholder to the base URL.
    $pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';

    // URL base depends on permalink settings.
    $format  = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
    $format .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';

    $defaults = array(
        'base'               => $pagenum_link,
        'format'             => $format,
        'total'              => $total,
        'current'            => $current,
        'aria_current'       => 'page',
        'show_all'           => false,
        'prev_text'          => __( '&laquo; Previous' ),
        'next_text'          => __( 'Next &raquo;' ),
        'end_size'           => isset($saved_settings['pagination_end_size']) ? $saved_settings['pagination_end_size'] : 1,
        'mid_size'           => isset($saved_settings['pagination_mid_size']) ? $saved_settings['pagination_mid_size'] : 2,
        'type'               => 'plain',
        'add_args'           => array(),
        'add_fragment'       => '',
        'before_page_number' => '',
        'after_page_number'  => '',
    );

    $args = wp_parse_args( $args, $defaults );

    if ( ! is_array( $args['add_args'] ) ) {
        $args['add_args'] = array();
    }

    if ( isset( $url_parts[1] ) ) {
        $format = explode( '?', str_replace( '%_%', $args['format'], $args['base'] ) );
        $format_query = isset( $format[1] ) ? $format[1] : '';
        wp_parse_str( $format_query, $format_args );

        // Find the query args of the requested URL.
        wp_parse_str( $url_parts[1], $url_query_args );

        // Remove the format argument from the array of query arguments, to avoid overwriting custom format.
        foreach ( $format_args as $format_arg => $format_arg_value ) {
            unset( $url_query_args[ $format_arg ] );
        }

        $args['add_args'] = array_merge( $args['add_args'], urlencode_deep( $url_query_args ) );
    }

    // Who knows what else people pass in $args
    $total = (int) $args['total'];
    if ( $total < 2 ) {
        return;
    }
    $current  = (int) $args['current'];
    $end_size = (int) $args['end_size']; // Out of bounds?  Make it the default.
    if ( $end_size < 1 ) {
        $end_size = 1;
    }
    $mid_size = (int) $args['mid_size'];
    if ( $mid_size < 0 ) {
        $mid_size = 2;
    }
    $add_args = $args['add_args'];
    $page_links = array();
    $dots = false;

    for ( $n = 1; $n <= $total; $n++ ) :
        if ( $n == $current ) :
            $page_links[] = array( 'url' => $current , 'type' => 'current' );
            $dots = true;
        else :
            if ( $args['show_all'] || ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) ) :
                $link = str_replace( '%_%', 1 == $n ? '' : $args['format'], $args['base'] );
                $link = str_replace( '%#%', $n, $link );
                if ( $add_args )
                    $link = add_query_arg( $add_args, $link );
                $link .= $args['add_fragment'];

                $page_links[] = array( 'url' => esc_url( apply_filters( 'paginate_links', $link ) ) , 'type' => 'number' , 'number' => $n );
                $dots = true;
            elseif ( $dots && ! $args['show_all'] ) :
                $page_links[] = array( 'url' => '#' , 'type' => 'dots' );
                $dots = false;
            endif;
        endif;
    endfor;
    return $page_links;
}

function udesly_get_term_link_by_slug($slug, $type){

    $term_link = get_term_link( $slug, $type );

    if(is_null($term_link) || is_wp_error($term_link))
        return '#';

    return $term_link;
}



function udesly_get_identifier( $identifier_template ) {
    global $post;
    if (!$post) {
        return '';
    }
    $identifier = str_replace('{id}', $post->ID, $identifier_template);
    $identifier = str_replace('{slug}', $post->post_name, $identifier);
    return $identifier;
}


function udesly_module_path( $module ) {
    return UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . "assets/js/$module.js";
}

function udesly_load_modules( $scripts ) {
    foreach ($scripts as $script) {
        wp_enqueue_script( $script, udesly_module_path( $script ), array(), UDESLY_ADAPTER_VERSION, true);
    }
}

/**
 *  Checks if the page is lost password
 * 
 */
function udesly_is_lost_password() {
    return !isset($_GET['key']) && !isset($_GET['login']);
}


/**
 * Checks if the page is reset password
 * 
 * 
 */
function udesly_is_reset_password() {
    return isset($_GET['key']) && isset($_GET['login']);
}

function udesly_get_reset_password_error() {
    $errors = new WP_Error();
    if ( ! isset( $_GET['key'], $_GET['login'] ) ) {
        $errors->add('notVisible', __( 'Sorry, this form should not be visible' ) );
    } else {
        $user = check_password_reset_key( $_GET['key'], $_GET['login'] );

        if ( is_wp_error( $user ) ) {
            if ( $user->get_error_code() === 'expired_key' ) {
                $errors->add( 'expiredkey', __( 'Sorry, that key has expired. Please try again.' ) );
            } else {
                $errors->add( 'invalidkey', __( 'Sorry, that key does not appear to be valid.' ) );
            }
        }
    }
    return $errors;
}

function udesly_get_featured_image_lightbox_json() {

    $result = [];

    $image = get_the_post_thumbnail_url('full');
    $result[] = array(
        "caption" => get_the_post_thumbnail_caption(),
        "url" => $image ? $image : '',
        "type" => "image",
    );

    return json_encode(array(
        "items" => $result
    ));
}

function udesly_get_archive_featured_image()
{

    if (is_home() && get_option('page_for_posts')) {
        $img = wp_get_attachment_image_src(get_post_thumbnail_id(get_option('page_for_posts')), 'full');
        $featured_image = $img[0];
        return $featured_image;
    } else {
        $cached_post_types = wp_cache_get("udesly_registered_types");

        $object = get_queried_object();
        if($cached_post_types && is_archive() && $object && in_array($object->name, $cached_post_types) ) {
            return \Udesly\Dashboard\Views\CustomPostTypes::get_cpt_setting($object->name)['archive_image'];
        }

        return udesly_get_term_featured_image();
    }
}


function udesly_setup_post_data( $post_identifier, $type = "post") {
    if (is_numeric($post_identifier)) {
        $post = get_post($post_identifier);
        if (setup_postdata($GLOBALS['post'] =& $post)){
            if ($type === "product") {
              wc_setup_product_data($post);
            }
            return true;
        } else {
            return false;
        }
    } else {
        $post = udesly_get_post_by_slug( $post_identifier, OBJECT, $type );
        if ($post) {
            if (setup_postdata($GLOBALS['post'] =& $post)){
                if ($type === "product") {
                    wc_setup_product_data($post);
                }
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}


function udesly_get_custom_post_type_archive_link( $cpt ) {
    return get_post_type_archive_link( $cpt );
}

if (!function_exists('write_error_log')) {

    function write_error_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}

function __udesly_get_default_theme_mods() {
    $cache = wp_cache_get('__udesly_default_mods');
    if ($cache) {
        return $cache;
    }
    $path = \Udesly\Theme\DataManager::get_options_udesly_data_path();
    if (!file_exists($path)) {
        wp_cache_set('__udesly_defaults_mods', []);
        return [];
    }
    try {
        $options = (array) json_decode(file_get_contents($path));
        wp_cache_set('__udesly_defaults_mods', $options);
        return $options;
    }catch (Exception $e) {
        return [];
    }

}

function udesly_get_theme_mod($option, $type) {

    $options = get_theme_mods();

    if (isset($options[$option])) {
        return $options[$option];
    } else {
        $defaults = __udesly_get_default_theme_mods();
        if (isset($defaults[$option])) {
            return $defaults[$option]->default;
        } else {
            return "";
        }
    }
}