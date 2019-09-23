<?php

namespace Udesly\Query;

use Udesly\Assets\Libraries;
if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 * Class Taxonomies
 * @package Udesly\Query
 */
class Taxonomies
{
    const TYPE_NAME = "udesly_tax_query";

    public static function admin_hooks()
    {
        add_filter("manage_" . self::TYPE_NAME . "_posts_columns", function ($columns) {
            unset($columns['date']);
            $columns['name'] = __('Slug');
            return $columns;
        });

        add_action("manage_" . self::TYPE_NAME . "_posts_custom_column", function ($column) {
            switch ($column) {
                case "name":
                    global $post;
                    echo $post->post_name;
                    break;
            }
        });

        add_action("add_meta_boxes_" . self::TYPE_NAME, function () {
            add_meta_box(
                'udesly-taxonomies-query-configuration',
                __('Query Configuration', UDESLY_TEXT_DOMAIN),
                array(self::class, 'query_configuration')
            );
        });
        add_action('admin_menu', function () {
            remove_meta_box('submitdiv', self::TYPE_NAME, 'core');
        });

        add_filter('get_user_option_screen_layout_udesly_tax_query', '__return_true');

        add_action('wp_ajax_udesly_preview_taxonomies', array(self::class, 'udesly_preview_taxonomies'));
        add_action('wp_ajax_udesly_save_term_query', array(self::class, 'udesly_save_term_query'));
    }

    
    public static function udesly_save_term_query()
    {
        if (!wp_verify_nonce($_POST['security'], 'udesly_preview_term')) {
            wp_send_json_error("security_check", 403);
            wp_die();
        }
        if (!isset($_POST['args'])) {
            wp_send_json_error("data_missing", 400);
            wp_die();
        }

        $args = (object)json_decode(stripslashes($_POST['args']), true);

        $post_id = $args->post_id;
        $data = $args->config;

        if (empty($data['taxonomy'])) {
            unset($data['taxonomy']);
        }

        if (empty($data['name__like'])) {
            unset($data['name__like']);
        }

        $taxonomies = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => serialize($data),
            "post_status" => 'publish'
        ), true);
        if (!is_wp_error($taxonomies)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }

        wp_die();
    }

    public static function udesly_preview_Taxonomies()
    {
        if (!wp_verify_nonce($_POST['security'], 'udesly_preview_term')) {
            wp_send_json_error("security_check", 403);
            wp_die();
        }
        if (!isset($_POST['args'])) {
            wp_send_json_error("Missing Data", 400);
            wp_die();
        }

        $data = json_decode(stripslashes($_POST['args']), true);

        $Taxonomies = self::get_taxonomies_preview($data);

        wp_send_json_success($Taxonomies);
        wp_die();
    }

    public static function query_configuration($post)
    {
        ?>
        <style>
            [v-cloak] {
                display: none;
            }
        </style>
        <script>
            jQuery(document).ready(function()
            {
                jQuery(window).off( 'beforeunload' );
                setInterval(function() {
                    jQuery(window).off( 'beforeunload' );
                }, 500);
            });
        </script>
        <div id="udesly-query-builder">
            <App v-cloak>
                <Query-Breadcrumb><?php _e("Terms Query", UDESLY_TEXT_DOMAIN); ?>
                </Query-Breadcrumb>
                <div class="query-list">
                    <List-Option name="<?php _e("Terms type", UDESLY_TEXT_DOMAIN); ?>">
                        <?php _e("Terms Type", UDESLY_TEXT_DOMAIN); ?>
                        <template v-slot:content>
                            <Material-Select name="taxonomy"
                                             options='<?php echo json_encode(self::get_taxonomies()); ?>'>
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Select the terms type you want to display (categories, tags etc.)", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Taxonomy Type", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Select>
                        </template>
                    </List-Option>
                    <List-Option name="<?php _e("Filters", UDESLY_TEXT_DOMAIN); ?>">
                        <?php _e("Filters", UDESLY_TEXT_DOMAIN); ?>
                        <template v-slot:content>
                            <Material-Input name="name__like" type="text"
                            >
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Based on the term type you selected in the previous section, here you can filter it based on the term name.", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Name Like", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Input>
                            <Checkbox name="top_level">
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Select only terms that are Top Level", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Top Level only", UDESLY_TEXT_DOMAIN); ?>
                                </Help>

                            </Checkbox>
                        </template>
                    </List-Option>
                    <List-Option name="<?php _e("Sort", UDESLY_TEXT_DOMAIN); ?>">
                        <?php _e("Sort", UDESLY_TEXT_DOMAIN); ?>
                        <template v-slot:content>
                            <Material-Select name="orderby"
                                             options='<?php echo json_encode(self::get_orderby()); ?>'>
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Order posts based on date, title, slug or ID.", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Order By", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Select>
                            <Material-Select name="order"
                                             options='<?php echo json_encode(self::get_order()); ?>'>
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Order posts in descending or ascending order.", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Order", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Select>
                        </template>
                    </List-Option>
                    <List-Option name="<?php _e("Count", UDESLY_TEXT_DOMAIN); ?>">
                        <?php _e("Count", UDESLY_TEXT_DOMAIN); ?>
                        <template v-slot:content>
                            <Material-Input name="number" type="number"
                                            min="0">
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Define the number of taxonomies to display.", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Number of Taxonomies", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Input>
                        </template>
                    </List-Option>

                </div>
                <div class="actions">
                    <Material-Button action="previewTaxonomies" idle="<?php _e('Preview', UDESLY_TEXT_DOMAIN); ?>"
                                     loading="<?php _e('Loading...', UDESLY_TEXT_DOMAIN); ?>"
                                     success="<?php _e('Success', UDESLY_TEXT_DOMAIN); ?>"
                                     failed="<?php _e('Failed', UDESLY_TEXT_DOMAIN); ?>"></Material-Button>
                    <Material-Button action="saveTermQuery" idle="<?php _e('Save', UDESLY_TEXT_DOMAIN); ?>"
                                     loading="<?php _e('Saving...', UDESLY_TEXT_DOMAIN); ?>"
                                     success="<?php _e('Success', UDESLY_TEXT_DOMAIN); ?>"
                                     failed="<?php _e('Failed', UDESLY_TEXT_DOMAIN); ?>"></Material-Button>
                </div>
            </App>
        </div>
        <?php
        $args = unserialize($post->post_content);
        $defaults = array(
            "taxonomy" => "",
            "number" => 3,
            "orderby" => "date",
            "order" => "DESC",
            "name_like" => ""
        );

        $args = wp_parse_args($args, $defaults);

        Libraries::enqueue_vue_library("udesly-query-builder", 'udeslyQueryConfig', array(
                "nonce" => wp_create_nonce("udesly_preview_term"),
                "ajaxurl" => admin_url('admin-ajax.php'),
                "config" => $args,
                "posts" => self::get_taxonomies_preview($args),
                "taxonomies" => self::get_taxonomies(),
                "post_id" => $post->ID
            )
        );
    }

    private static function get_taxonomies()
    {

        $post_types = self::get_post_types(true);

        $taxonomies = array();
        foreach ($post_types as $post_type) {
            if ($post_type->name != "attachment") {
                $t = get_object_taxonomies($post_type->name, 'objects');

                foreach ($t as $tax) {

                    if ($tax->name != "post_format" && $tax->public) {
                        $taxonomies[$tax->name] = "[ $post_type->name ] $tax->label";
                    }
                }

            }

        }
        return $taxonomies;
    }

    private static function get_post_types($asObj = false)
    {
        $result = array();
        global $wp_post_types;

        foreach ($wp_post_types as $name => $post_type) {

            if ($post_type->public && $post_type->name != "attachment" && !udesly_string_starts_with($post_type->name, "udesly")) {
                $result[$post_type->name] = $asObj ? $post_type : $post_type->label;
            }
        }

        return $result;
    }

    private static function clean_query_taxonomies_content($data)
    {

        if(empty($data['taxonomy'])) {
            unset($data['taxonomy']);
        }

        if (isset($data['top_level']) && $data['top_level'] == true) {
            unset($data['top_level']);
            $data['parent'] = 0;
        }

        return $data;
    }


    private static function get_taxonomies_preview($args)
    {

        $args = self::clean_query_taxonomies_content($args);
        $query = new TermsQueryBuilder("test", $args);

        return $query->get_results();
    }

    private static function get_orderby()
    {
        return array(
            "date" => __("Date", UDESLY_TEXT_DOMAIN),
            "title" => __("Title", UDESLY_TEXT_DOMAIN),
            "name" => __("Slug", UDESLY_TEXT_DOMAIN),
            "ID" => __("ID", UDESLY_TEXT_DOMAIN),
            "author" => __("Author", UDESLY_TEXT_DOMAIN),
            "rand" => __("Random", UDESLY_TEXT_DOMAIN),
        );
    }


    private static function get_order()
    {
        return array(
            "DESC" => __("Descending", UDESLY_TEXT_DOMAIN),
            "ASC" => __("Ascending", UDESLY_TEXT_DOMAIN),
        );
    }


    public static function public_hooks()
    {
        add_action('init', array(self::class, 'register_type'));
    }

    public static function register_type()
    {

        $labels = array(
            'name' => _x('Terms Query', 'taxonomies type general name', UDESLY_TEXT_DOMAIN),
            'singular_name' => _x('Terms Query', 'taxonomies type singular name', UDESLY_TEXT_DOMAIN),
            'menu_name' => _x('Terms Queries', 'admin menu', UDESLY_TEXT_DOMAIN),
            'name_admin_bar' => _x('Terms Query', 'add new on admin bar', UDESLY_TEXT_DOMAIN),
            'add_new' => _x('Add New Terms Query', 'book', UDESLY_TEXT_DOMAIN),
            'add_new_item' => __('Add New Terms Query', UDESLY_TEXT_DOMAIN),
            'new_item' => __('New Terms Query', UDESLY_TEXT_DOMAIN),
            'edit_item' => __('Edit Terms Query', UDESLY_TEXT_DOMAIN),
            'view_item' => __('View Terms Query', UDESLY_TEXT_DOMAIN),
            'all_items' => __('All Terms Queries', UDESLY_TEXT_DOMAIN),
            'search_items' => __('Search Terms Queries', UDESLY_TEXT_DOMAIN),
            'parent_item_colon' => __('Parent Terms Queries:', UDESLY_TEXT_DOMAIN),
            'not_found' => __('No Terms queries found.', UDESLY_TEXT_DOMAIN),
            'not_found_in_trash' => __('No Terms queries found in Trash.', UDESLY_TEXT_DOMAIN)
        );

        register_post_type(self::TYPE_NAME, array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'supports' => array('title'),
            'show_in_menu' => 'edit.php?taxonomies_type=' . self::TYPE_NAME,
            'publicly_queryable' => false
        ));
    }

}
