<?php

namespace Udesly\Query;

use Udesly\Assets\Libraries;
if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 * Class Posts
 * @package Udesly\Query
 */
class Posts
{
    const TYPE_NAME = "udesly_posts_query";

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

        add_action("load-post-new.php", function () {
            add_action("add_meta_boxes_" . self::TYPE_NAME, function () {
                add_meta_box(
                    'udesly-posts-query-configuration',
                    __('Query Configuration', UDESLY_TEXT_DOMAIN),
                    array(self::class, 'query_configuration')
                );
            });
        });

        add_action("load-post.php", function () {
            add_action("add_meta_boxes_" . self::TYPE_NAME, function () {
                add_meta_box(
                    'udesly-posts-query-configuration',
                    __('Query Configuration', UDESLY_TEXT_DOMAIN),
                    array(self::class, 'query_configuration')
                );
            });
        });

        add_action('admin_menu', function () {
            remove_meta_box('submitdiv', self::TYPE_NAME, 'core');
        });

        add_filter('get_user_option_screen_layout_udesly_posts_query', '__return_true');

        add_action('wp_ajax_udesly_preview_posts', array(self::class, 'udesly_preview_posts'));
        add_action('wp_ajax_udesly_search_taxonomy', array(self::class, 'udesly_search_taxonomy'));
        add_action('wp_ajax_udesly_save_post_query', array(self::class, 'udesly_save_post_query'));
    }


    private static function get_authors()
    {
        global $wpdb;

        $args = array(
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => '',
            'optioncount' => false,
            'exclude_admin' => false,
            'show_fullname' => false,
            'hide_empty' => true,
            'feed' => '',
            'feed_image' => '',
            'feed_type' => '',
            'echo' => true,
            'style' => 'list',
            'html' => true,
            'exclude' => '',
            'include' => '',
        );

        $return = '';

        $query_args = wp_array_slice_assoc($args, array('orderby', 'order', 'number', 'exclude', 'include'));
        $query_args['fields'] = 'ids';
        $authors = get_users($query_args);

        $author_count = array();
        foreach ((array)$wpdb->get_results("SELECT DISTINCT post_author, COUNT(ID) AS count FROM $wpdb->posts WHERE " . get_private_posts_cap_sql('post') . ' GROUP BY post_author') as $row) {
            $author_count[$row->post_author] = $row->count;
        }
        $result = array();
        foreach ($authors as $author_id) {
            $author = get_userdata($author_id);

            if ($args['exclude_admin'] && 'admin' == $author->display_name) {
                continue;
            }

            $posts = isset($author_count[$author->ID]) ? $author_count[$author->ID] : 0;

            if (!$posts && $args['hide_empty']) {
                continue;
            }

            $result[$author_id] = $author->display_name;

        }

        return $result;

    }

    public static function udesly_save_post_query()
    {
        if (!wp_verify_nonce($_POST['security'], 'udesly_preview_posts')) {
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
        if (empty($data['terms'])) {
            unset($data['terms']);
        }
        if (empty($data['author'])) {
            unset($data['author']);
        }
        if (empty($data['meta_key'])) {
            unset($data['meta_key']);
        }
        if (empty($data['meta_value'])) {
            unset($data['meta_value']);
        }

        /* if (isset($data['taxonomy']) && isset($data['terms'])) {
            $data['tax_query'] = array(array(
                'taxonomy' => $data['taxonomy'],
                'terms' => $data['terms']
            ));
            unset($data['taxonomy']);
            unset($data['terms']);
        }
        if (isset($data['author'])) {
            $data['author'] = join(",", $data['author']);
        } */

        $post = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => serialize($data),
            "post_status" => 'publish'
        ), true);
        if (!is_wp_error($post)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }

        wp_die();
    }

    public static function udesly_search_taxonomy()
    {
        if (!wp_verify_nonce($_POST['security'], 'udesly_preview_posts')) {
            wp_send_json_error("security_check", 403);
            wp_die();
        }
        if (!isset($_POST['data'])) {
            wp_send_json_error("data_missing", 400);
            wp_die();
        }
        $data = (object)json_decode(stripslashes($_POST['data']), true);
        if (!isset($data->taxonomy) || $data->taxonomy == "") {
            wp_send_json_error("taxonomy_missing", 400);
            wp_die();
        }

        $args = array(
            "taxonomy" => $data->taxonomy
        );

        if (isset($data->search)) {
            $args['search'] = $data->search;
        }

        $terms = get_terms($args);

        wp_send_json_success($terms);
        wp_die();
    }

    public static function udesly_preview_posts()
    {
        if (!wp_verify_nonce($_POST['security'], 'udesly_preview_posts')) {
            wp_send_json_error("security_check", 403);
            wp_die();
        }
        if (!isset($_POST['args'])) {
            wp_send_json_error("Missing Data", 400);
            wp_die();
        }

        $data = json_decode(stripslashes($_POST['args']), true);
        $posts = self::get_posts_preview($data);

        wp_send_json_success($posts);
        wp_die();
    }

    public static function query_configuration($post)
    {
        ?>
        <style>
            [v-cloak] {
                display: none;
            }
            #udesly-query-wrapper {
                height: 100%;
                padding-bottom: 90px;
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
                <Query-Breadcrumb><?php _e("Posts Query", UDESLY_TEXT_DOMAIN); ?>
                </Query-Breadcrumb>
                <div class="query-list">
                    <List-Option name="<?php _e("Post type", UDESLY_TEXT_DOMAIN); ?>">
                        <?php _e("Post Type", UDESLY_TEXT_DOMAIN); ?>
                        <template v-slot:content>
                            <Material-Select name="post_type"
                                             options='<?php echo json_encode(self::get_post_types()); ?>'>
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Here you can select the post type you want to display (posts, products, pages, etc.)", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Post Type", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Select>
                        </template>
                    </List-Option>
                    <List-Option name="<?php _e("Filters", UDESLY_TEXT_DOMAIN); ?>">
                        <?php _e("Filters", UDESLY_TEXT_DOMAIN); ?>
                        <template v-slot:content>
                            <Material-Select name="taxonomy"
                                             options-name='taxonomy'>
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Filter the selected post type based on a specific taxonomy such as categories or tags. If you set a taxonomy such as Current Category, you can use it only on Single Page", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Taxonomy", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Select>
                            <udesly-multi name="terms" options="[]"
                                          search="udesly_search_taxonomy">
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Filter the selected post type based on a specific term contained it the taxonomy you have choosen.", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Terms", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </udesly-multi>
                            <udesly-multi-simple name="author"
                                                 options='<?php echo json_encode(self::get_authors()); ?>'>
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("View all the posts belonging to a specific author.", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Authors", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </udesly-multi-simple>
                            <Material-Input name="meta_key" type="text"
                            >
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("It is the name of a custom field of the post you're searching.", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Meta Key", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Input>
                            <Material-Input name="meta_value" type="text"
                            >
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("It is the value corresponded to the key you have chosen above.", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Meta Value", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Input>
                            <Material-Select name="post_status"
                                             options='<?php echo json_encode(self::get_post_statuses()); ?>'>
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Filter posts by post status", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Post Status", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Select>
                            <Material-Select name="has_password"
                                             options='<?php echo json_encode(self::get_has_password_options()); ?>'>
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Filter posts by Password status", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Has Password", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Select>
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
                                        <?php _e("Order posts in descending or ascending order", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Order", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Select>
                        </template>
                    </List-Option>
                    <List-Option name="<?php _e("Count", UDESLY_TEXT_DOMAIN); ?>">
                        <?php _e("Count", UDESLY_TEXT_DOMAIN); ?>
                        <template v-slot:content>
                            <Material-Input name="posts_per_page" type="number"
                                            min="-1">
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Define the number of posts to display.", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Number of Posts", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Input>
                            <Material-Input name="offset" type="number"
                                            min="0">
                                <Help>
                                    <template v-slot:help>
                                        <?php _e("Skip a given number of posts, if you need", UDESLY_TEXT_DOMAIN); ?>
                                    </template>
                                    <?php _e("Offset", UDESLY_TEXT_DOMAIN); ?>
                                </Help>
                            </Material-Input>
                        </template>
                    </List-Option>
                </div>
                <div class="actions">
                    <Material-Button action="previewPosts" idle="<?php _e('Preview', UDESLY_TEXT_DOMAIN); ?>"
                                     loading="<?php _e('Loading...', UDESLY_TEXT_DOMAIN); ?>"
                                     success="<?php _e('Success', UDESLY_TEXT_DOMAIN); ?>"
                                     failed="<?php _e('Failed', UDESLY_TEXT_DOMAIN); ?>"></Material-Button>
                    <Material-Button action="saveQuery" idle="<?php _e('Save', UDESLY_TEXT_DOMAIN); ?>"
                                     loading="<?php _e('Saving...', UDESLY_TEXT_DOMAIN); ?>"
                                     success="<?php _e('Success', UDESLY_TEXT_DOMAIN); ?>"
                                     failed="<?php _e('Failed', UDESLY_TEXT_DOMAIN); ?>"></Material-Button>
                </div>
            </App>
        </div>
        <?php

        $args = unserialize($post->post_content);
        $defaults = array(
            "post_type" => 'post',
            "posts_per_page" => 3,
            "offset" => 0,
            "orderby" => "date",
            "order" => "DESC",
            "taxonomy" => "",
            "terms" => [],
            "author" => [],
            "meta_key" => "",
            "meta_value" => "",
            "has_password" => "both",
            "post_status" => "publish"
        );

        $args = wp_parse_args($args, $defaults);

        Libraries::enqueue_vue_library("udesly-query-builder", 'udeslyQueryConfig', array(
                "nonce" => wp_create_nonce("udesly_preview_posts"),
                "ajaxurl" => admin_url('admin-ajax.php'),
                "config" => $args,
                "posts" => self::get_posts_preview($args),
                "taxonomies" => self::get_taxonomies(),
                "post_id" => $post->ID
            )
        );

    }

    private static function clean_query_post_content($data)
    {

        if (empty($data['taxonomy'])) {
            unset($data['taxonomy']);
        }
        if (empty($data['terms'])) {
            unset($data['terms']);
        }
        if (empty($data['author'])) {
            unset($data['author']);
        }
        if (empty($data['meta_key'])) {
            unset($data['meta_key']);
        }
        if (empty($data['meta_value'])) {
            unset($data['meta_value']);
        }

        if (isset($data['has_password'])) {
            if ($data['has_password'] == 'true') {
                $data['has_password'] = true;
            } else if ($data['has_password'] == 'false') {
                $data['has_password'] = false;
            } else {
                unset($data['has_password']);
            }
        }

        if (isset($data['taxonomy']) && isset($data['terms'])) {
            $data['tax_query'] = array(array(
                'taxonomy' => $data['taxonomy'],
                'terms' => $data['terms']
            ));
            unset($data['taxonomy']);
            unset($data['terms']);
        }
        if (isset($data['author'])) {
            $data['author'] = join(",", $data['author']);
        }

        return $data;
    }

    private static function get_taxonomies()
    {
        $result = array();
        $post_types = self::get_post_types(true);
        foreach ($post_types as $post_type) {

                $t = get_object_taxonomies($post_type->name, 'objects');
                $taxonomies = array("" => __("Select an option", UDESLY_TEXT_DOMAIN));
                foreach ($t as $tax) {
                    if ($tax->name != "post_format" && $tax->public) {
                        $taxonomies[$tax->name] = $tax->label;
                        $taxonomies["udy_current_{$tax->name}"] = "Current {$tax->labels->singular_name}";
                    }
                }
                if ($taxonomies) {
                    $result[$post_type->name] = $taxonomies;
                }


        }
        return $result;
    }

    private static function get_posts_preview($args)
    {

        $args = self::clean_query_post_content($args);
        $query = new PostsQueryBuilder("test", $args);

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

    private static function get_post_statuses()
    {
        return array(
            "publish" => __("Published", UDESLY_TEXT_DOMAIN),
            "pending" => __("Pending review", UDESLY_TEXT_DOMAIN),
            "draft" => __("Draft", UDESLY_TEXT_DOMAIN),
            "auto-draft" => __("Auto Draft", UDESLY_TEXT_DOMAIN),
            "future" => __("Future", UDESLY_TEXT_DOMAIN),
            "private" => __("Private", UDESLY_TEXT_DOMAIN),
            "thrash" => __("Trash", UDESLY_TEXT_DOMAIN),
            "any" => __("Any status (not Trash and Auto Draft)", UDESLY_TEXT_DOMAIN),
        );
    }


    private static function get_order()
    {
        return array(
            "DESC" => __("Descending", UDESLY_TEXT_DOMAIN),
            "ASC" => __("Ascending", UDESLY_TEXT_DOMAIN),
        );
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

    private static function get_has_password_options() {
        return array(
          "both" => "With and Without Password",
          "false" => "Without Password",
          "true" => "With Password"
        );
    }

    public static function public_hooks()
    {
        add_action('init', array(self::class, 'register_type'));
    }

    public static function register_type()
    {

        $labels = array(
            'name' => _x('Posts Query', 'post type general name', UDESLY_TEXT_DOMAIN),
            'singular_name' => _x('Posts Query', 'post type singular name', UDESLY_TEXT_DOMAIN),
            'menu_name' => _x('Posts Queries', 'admin menu', UDESLY_TEXT_DOMAIN),
            'name_admin_bar' => _x('Posts Query', 'add new on admin bar', UDESLY_TEXT_DOMAIN),
            'add_new' => _x('Add New Posts Query', 'book', UDESLY_TEXT_DOMAIN),
            'add_new_item' => __('Add New Posts Query', UDESLY_TEXT_DOMAIN),
            'new_item' => __('New Posts Query', UDESLY_TEXT_DOMAIN),
            'edit_item' => __('Edit Posts Query', UDESLY_TEXT_DOMAIN),
            'view_item' => __('View Posts Query', UDESLY_TEXT_DOMAIN),
            'all_items' => __('All Posts Queries', UDESLY_TEXT_DOMAIN),
            'search_items' => __('Search Posts Queries', UDESLY_TEXT_DOMAIN),
            'parent_item_colon' => __('Parent Posts Queries:', UDESLY_TEXT_DOMAIN),
            'not_found' => __('No posts queries found.', UDESLY_TEXT_DOMAIN),
            'not_found_in_trash' => __('No posts queries found in Trash.', UDESLY_TEXT_DOMAIN)
        );

        register_post_type(self::TYPE_NAME, array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'supports' => array('title'),
            'show_in_menu' => 'edit.php?post_type=' . self::TYPE_NAME,
            'publicly_queryable' => false
        ));
    }

}
