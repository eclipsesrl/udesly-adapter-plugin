<?php

namespace Udesly\CPT;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class CustomPostTypes
{
    public static function init_all_custom_post_types()
    {
        $post_types = \Udesly\Dashboard\Views\CustomPostTypes::get_all_custom_post_types();

        $registered_types = array();
        $registered_taxonomies = array();

        foreach ($post_types as $key => $options) {
            if ($options['enabled'] === true && !isset($options['third_party'])) {
                $type = str_replace('cpt_', '', $key);

                $registered_types[] = $type;
                $taxonomies = explode(",", $options['taxonomies']);

                $labels = array(
                    'name' => $options['plural'],
                    'singular_name' => $options['singular'],
                    'menu_name' => ucfirst($options['plural']),
                    'name_admin_bar' => $options['singular'],
                    'archives' => $options['singular'] . __('Archives', UDESLY_TEXT_DOMAIN),
                    'attributes' => $options['singular'] . __('Attributes', UDESLY_TEXT_DOMAIN),
                    'parent_item_colon' => __('Parent Item:', UDESLY_TEXT_DOMAIN),
                    'all_items' => __('All ', UDESLY_TEXT_DOMAIN) . $options['plural'],
                    'add_new_item' => __('Add New ', UDESLY_TEXT_DOMAIN) . $options['singular'],
                    'add_new' => __('Add New', UDESLY_TEXT_DOMAIN),
                    'new_item' => __('New ', UDESLY_TEXT_DOMAIN) . $options['singular'],
                    'edit_item' => __('Edit ', UDESLY_TEXT_DOMAIN) . $options['singular'],
                    'update_item' => __('Update ', UDESLY_TEXT_DOMAIN) . $options['singular'],
                    'view_item' => __('View ', UDESLY_TEXT_DOMAIN) . $options['singular'],
                    'view_items' => __('View ', UDESLY_TEXT_DOMAIN) . $options['plural'],
                    'search_items' => __('Search ', UDESLY_TEXT_DOMAIN) . $options['singular'],
                    'not_found' => __('Not found', UDESLY_TEXT_DOMAIN),
                    'not_found_in_trash' => __('Not found in Trash', UDESLY_TEXT_DOMAIN),
                    'featured_image' => __('Featured Image', UDESLY_TEXT_DOMAIN),
                    'set_featured_image' => __('Set featured image', UDESLY_TEXT_DOMAIN),
                    'remove_featured_image' => __('Remove featured image', UDESLY_TEXT_DOMAIN),
                    'use_featured_image' => __('Use as featured image', UDESLY_TEXT_DOMAIN),
                    'insert_into_item' => __('Insert into item', UDESLY_TEXT_DOMAIN),
                    'uploaded_to_this_item' => __('Uploaded to this item', UDESLY_TEXT_DOMAIN),
                    'items_list' => __('Items list', UDESLY_TEXT_DOMAIN),
                    'items_list_navigation' => __('Items list navigation', UDESLY_TEXT_DOMAIN),
                    'filter_items_list' => __('Filter items list', UDESLY_TEXT_DOMAIN),
                );

                $args = array(
                    'label' => $options['singular'],
                    'description' => "",
                    'labels' => $labels,
                    'supports' => array('title', 'author', 'editor', 'thumbnail', 'custom-fields', 'excerpt'),
                    'hierarchical' => false,
                    'public' => true,
                    'show_ui' => true,
                    'show_in_menu' => true,
                    'menu_position' => 5,
                    'show_in_admin_bar' => true,
                    'show_in_nav_menus' => true,
                    'can_export' => true,
                    'has_archive' => true,
                    'rewrite' => array(
                        'slug' => $options['archive_rewrite'],
                        'with_front' => true,
                        'pages' => true,
                        'feeds' => true
                    ),
                    'show_in_rest' => true,
                    'exclude_from_search' => false,
                    'publicly_queryable' => true,
                    'capability_type' => 'page',
                    'menu_icon' => $options['icon']
                );

                $args = apply_filters("udesly_register_custom_post_type_args_$type", $args);
                register_post_type($type, $args);


                foreach ($taxonomies as $taxonomy) {
                    $taxonomy = sanitize_title(trim($taxonomy));
                    if ($taxonomy) {
                        $taxonomy = str_replace('-', '_', $taxonomy);
                        $tax_name = str_replace('_', ' ', ucfirst($taxonomy));
                        $slug = get_option('udesly_cpt_rewrite_' . $type . '_' . $taxonomy . '_field_id', $type . '_' . $taxonomy);
                        // var_dump('udesly_cpt_rewrite_' . $type . '_' . $taxonomy . '_field_id');
                        $registered_taxonomies[$type . '_' . $taxonomy] = $type;
                        register_taxonomy($type . '_' . $taxonomy, $type, array(
                            'labels' => array(
                                'name' => $tax_name,
                                'singular_name' => $tax_name,
                                'menu_name' => __('All ', UDESLY_TEXT_DOMAIN) . $tax_name,
                                'all_items' => __('All ', UDESLY_TEXT_DOMAIN) . $tax_name,
                                'edit_item' => __('Edit ', UDESLY_TEXT_DOMAIN) . $tax_name,
                                'view_item' => __('View ', UDESLY_TEXT_DOMAIN) . $tax_name,
                                'update_item' => __('Update ', UDESLY_TEXT_DOMAIN) . $tax_name,
                                'add_new_item' => __('Add new ', UDESLY_TEXT_DOMAIN) . $tax_name,
                                'new_item_name' => __('New ', UDESLY_TEXT_DOMAIN) . $tax_name . __(' name', UDESLY_TEXT_DOMAIN),
                                'search_items' => __('Search ', UDESLY_TEXT_DOMAIN) . $tax_name,
                                'popular_items' => __('Popular ', UDESLY_TEXT_DOMAIN) . $tax_name,
                                'separate_items_with_commas' => __('Separate ', UDESLY_TEXT_DOMAIN) . $tax_name . __(' with commas', UDESLY_TEXT_DOMAIN),
                                'add_or_remove_items' => __('Add or remove ', UDESLY_TEXT_DOMAIN) . $tax_name,
                                'choose_from_most_used' => __('Choose most used ', UDESLY_TEXT_DOMAIN) . $tax_name,
                                'not_found' => __('No ', UDESLY_TEXT_DOMAIN) . $tax_name . __(' found', UDESLY_TEXT_DOMAIN),
                            ),
                            'rewrite' => array(
                                'slug' => $slug
                            ),
                            'show_ui' => true,
                            'show_admin_column' => true,
                            'query_var' => true,
                        ));
                    }
                }

            }
        }

        wp_cache_set('udesly_registered_types', $registered_types);
        wp_cache_set('udesly_registered_taxonomies', $registered_taxonomies);
    }

    /* Setting Section Description */
    public static function taxonomy_rewrite_slug()
    {
        echo wpautop("Change the permalink structure");
    }

    /* Settings Field Callback */
    public static function taxonomy_rewrite_slug_field($args)
    {
        ?>
        <input id="<?php echo $args[0]; ?>" type="text" value="<?php echo get_option($args[0], $args[1]); ?>"
               name="<?php echo $args[0]; ?>"/>
        <?php
    }



    public static function rewrite_custom_taxonomies()
    {
        $cached_types = wp_cache_get('udesly_registered_types');
        $cached_taxonomies = wp_cache_get('udesly_registered_taxonomies');

        if (!$cached_taxonomies || !$cached_types) {
            return;
        }

        foreach ($cached_types as $post_type) {
            add_settings_section(
                'udesly_cpt_rewrite_' . $post_type . '_section_id',                   // Section ID
                ucfirst($post_type) . ' permalinks',  // Section title
                array(self::class, 'taxonomy_rewrite_slug'), // Section callback function
                'permalink'                          // Settings page slug
            );
        }


        foreach ($cached_taxonomies as $taxonomy => $type) {

            $option_name = 'udesly_cpt_rewrite_' . $taxonomy . '_field_id';

            $taxonomy = trim($taxonomy);
            /* Register Settings */
            register_setting(
                'permalink', // Options group
                $option_name              // Option name/database
            );

            /* Create settings field */
            add_settings_field(
                'udesly_cpt_rewrite_' . $type . '_' . $taxonomy . '_field_id',       // Field ID
                'Archive base ' . $taxonomy,       // Field title
                array(self::class, 'taxonomy_rewrite_slug_field'),
                'permalink',                    // Settings page slug
                'udesly_cpt_rewrite_' . $type . '_section_id',               // Section ID
                array($option_name, $type . '_' . $taxonomy)
            );

            //Register settings in DB (is not possible to do with Settings API in permalink page, WP BUG)
            if (isset($_POST[$option_name])) {
                update_option($option_name, sanitize_title($_POST[$option_name]));
            }
        }
    }

    public static function add_registered_taxonomies_to_featured_image($post_types)
    {
        $registered_taxonomies = wp_cache_get('udesly_registered_taxonomies');

        if ($registered_taxonomies) {
            foreach ($registered_taxonomies as $tax => $val) {
                $post_types[] = $tax;
            }
        }
        return $post_types;
    }

    public static function add_custom_metaboxes() {
        $cached_taxonomies = wp_cache_get('udesly_registered_taxonomies');

        foreach ($cached_taxonomies as $taxonomy => $post_type) {

            $title = str_replace('_', ' ', $taxonomy);
            $title = ucfirst($title);
            add_meta_box( "taxonomy-dropdown-div--${taxonomy}", $title, [self::class, 'add_taxonomy_metabox'],$post_type ,'side','core', $taxonomy);
        }
    }

    public static function add_taxonomy_metabox( $post, $args ) {
        $title = $args['title'];
        $taxonomy = $args['args'];
        //The name of the form
        $name = 'tax_input[' . $taxonomy . '][]';
        $id = $taxonomy.'dropdown';
        //Get current and popular terms
        $postterms = get_the_terms( $post->ID, $taxonomy );
        $current = ($postterms ? array_pop($postterms) : false);
        $current = ($current ? $current->term_id : 0);
        ?>

        <div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
            <!-- Display taxonomy terms -->
            <div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
                <?php $args = array(
                    'show_option_all'    => "Choose a $title",
                    'show_option_none'   => '',
                    'orderby'            => 'ID',
                    'order'              => 'ASC',
                    'show_count'         => 0,
                    'hide_empty'         => 0,
                    'child_of'           => 0,
                    'exclude'            => '',
                    'echo'               => 1,
                    'selected'           => 1,
                    'hierarchical'       => 1,
                    'name'               => $name,
                    'id'                 => $id,
                    'class'              => 'form-no-clear',
                    'depth'              => 0,
                    'tab_index'          => 0,
                    'taxonomy'           => $taxonomy,
                    'hide_if_empty'      => true
                ); ?>

                <?php wp_dropdown_categories($args); ?>
            </div>
        </div>
        <?php
    }

    public static function admin_hooks()
    {
        add_action('admin_init', array(self::class, "rewrite_custom_taxonomies"));
        add_filter('udesly_attach_featured_image_terms', array(self::class, "add_registered_taxonomies_to_featured_image"), 1, 1);
        add_action( 'add_meta_boxes', array(self::class, "add_custom_metaboxes"));
    }

    public static function custom_taxonomy_archive($template)
    {
        $obj = get_queried_object();

        if (!$obj) {
            return $template;
        }

        $cached_taxonomies = wp_cache_get('udesly_registered_taxonomies');

        if (!$cached_taxonomies) {
            return $template;
        }

        if (isset($obj->taxonomy) &&
            taxonomy_exists($obj->taxonomy) && isset($cached_taxonomies[$obj->taxonomy]) &&
            file_exists(get_template_directory() . '/archive-' . $cached_taxonomies[$obj->taxonomy] . '.php')) {

            $new_template = locate_template(array('archive-' . $cached_taxonomies[$obj->taxonomy] . '.php'));
            if (!empty($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }

    public static function public_hooks()
    {
        add_action('init', array(self::class, 'init_all_custom_post_types'));
        add_filter('template_include', array(self::class, 'custom_taxonomy_archive'), 98);
    }

}