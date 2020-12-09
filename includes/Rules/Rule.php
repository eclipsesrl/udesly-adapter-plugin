<?php

namespace Udesly\Rules;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

use Udesly\Assets\Libraries;

class Rule
{

    const TYPE_NAME = "udesly_rule_content";


    public static function rule_configuration($post)
    {
        ?>
        <style>
            [v-cloak] {
                display: none;
            }
        </style>
        <p><?php _e('Start writing "if" and get inspired by autocomplete', UDESLY_TEXT_DOMAIN); ?></p>
        <div id="udesly-rules"></div>
        <script>
            jQuery(document).ready(function () {
                jQuery(window).off('beforeunload');
                setInterval(function () {
                    jQuery(window).off('beforeunload');
                }, 500);
            });
        </script>
        <?php

        $data = maybe_unserialize($post->post_content);

        if (!is_array($data)) {
            $data = array(
                "values" => array(),
                "combinations" => array()
            );
        }

        Libraries::enqueue_vue_library("udesly-rules", 'udeslyRules', array(
                "nonce" => wp_create_nonce("udesly_rules_actions"),
                "ajaxurl" => admin_url('admin-ajax.php'),
                "post_id" => $post->ID,
                "data" => $data,
                "predicate" => "if",
                "subjects" => self::get_subjects(),
                "saveLabels" => array(
                    "idle" => __("Save", UDESLY_TEXT_DOMAIN),
                    "load" => __("Saving...", UDESLY_TEXT_DOMAIN),
                    "success" => __("Success", UDESLY_TEXT_DOMAIN),
                    "fail" => __("Failed...", UDESLY_TEXT_DOMAIN),
                )
            )
        );
    }

    public static function udesly_save_rule_content()
    {
        if (!wp_verify_nonce($_POST['security'], 'udesly_rules_actions')) {
            wp_send_json_error("security_check", 403);
            wp_die();
        }
        if (!isset($_POST['args'])) {
            wp_send_json_error("Missing Data", 400);
            wp_die();
        }

        $data = json_decode(stripslashes($_POST['args']), true);

        if (!isset($data['post_id'])) {
            wp_send_json_error("Missing Post Id", 400);
            wp_die();
        }

        $content = array(
            "values" => $data['values'],
            "combinations" => $data['combinations']
        );

        $post = wp_update_post(
            array(
                "ID" => $data['post_id'],
                "post_content" => serialize($content),
                "post_status" => "publish"
            ), true
        );
        if (!is_wp_error($post)) {
            wp_send_json_success();
        } else {
            wp_send_json_error("Failed saving post", 500);
        }
        wp_die();
    }

    public static function get_subjects()
    {

        $subjects = array();
        $subjects_key = apply_filters("udesly_rules_subject_keys", ["page", "user", "archive"]);

        foreach ($subjects_key as $s) {
            $subjects[$s] = apply_filters("udesly_rules_options_$s", array());
        }

        return $subjects;

    }

    public static function add_rules_for_page($options)
    {
        $options['is home page'] = ["."];
        $options['is blog page'] = ["."];
        $options['is category'] = [" {slug}", " {id}"];
        $options['is archive'] = ["."];
        $options['is single'] = ["."];

        $post_t = [];
        $post_types = get_post_types(array('public' => true));
        foreach ($post_types as $post_type) {
            $type_obj = get_post_type_object($post_type);
            if ($type_obj->publicly_queryable) {
                $post_t[] = " " . $post_type;
            }

        }

        if (count($post_t)) {
            $options['is archive of'] = $post_t;
            $options['is single of'] = $post_t;
        }

        $taxonomies = get_taxonomies(
            array(
                "public" => true,
            )
        );

        $tax_t = [];


        foreach ($taxonomies as $key => $taxonomy) {
            $tax_t[] = " " . $taxonomy;
        }

        if (count($tax_t)) {
            $options['is taxonomy of'] = $tax_t;
        }

        return $options;
    }

    public static function add_rules_for_edd_user($options)
    {
        $options['has purchased edd'] = [" {slug}", " {id}"];
        return $options;
    }

    public static function add_rules_for_rcp_user($options)
    {
        $options['can access rcp'] = ["."];
        $options['is active rcp'] = ["."];
        $options['is expired rcp'] = ["."];
        $options['is trialing rcp'] = ["."];
        $options['is pending verification rcp'] = ["."];
        $options['has used trial rcp'] = ["."];

        $levels = new \RCP_Levels();

        $l = [];

        foreach ($levels->get_levels(array(
            "status" => "active"
        )) as $level) {
            $l[] = " " . $level->name;
        }

        if (count($l)) {
            $options['has subscription rcp'] = $l;
        }

        return $options;
    }

    public static function add_rules_for_users($options)
    {
        $roles = [];
        global $wp_roles;
        foreach ($wp_roles->roles as $key => $value) {
            $roles[] = " " . $value['name'];
        }
        $options['is role'] = $roles;
        $options['is logged in'] = ["."];
        $options['is not logged in'] = ["."];

        return $options;
    }

    public static function add_rules_for_archive($options)
    {

        $options['has subcategories'] = ["."];
        $options['do not have subcategories'] = ["."];

        return $options;
    }

    public static function eval_rule($rule_slug)
    {

        if (is_numeric($rule_slug)) {
            $content = unserialize(get_post_field('post_content', intval($rule_slug)));
        } else {
            $rule = udesly_get_post_by_slug($rule_slug, OBJECT, self::TYPE_NAME);
            if (!$rule) {
                return false;
            }
            $content = unserialize($rule->post_content);
        }

        if (!$content) {
            return false;
        } else {

            $rules = $content['values'];
            $combinations = $content['combinations'];

            $result = null;

            foreach ($rules as $index => $rule) {
                if ($index == 0) {
                    $result = self::_get_rule_value($rule);
                } else {
                    if (isset($combinations[$index - 1])) {
                        $result = self::_merge_rule_results($result, self::_get_rule_value($rule), $combinations[$index - 1]);
                    } else {
                        $result = self::_merge_rule_results($result, self::_get_rule_value($rule));
                    }
                }
            }
            return $result;
        }
    }

    private static function _merge_rule_results($current_result, $new_result, $combination = "and")
    {
        if (strtolower($combination) == "and") {
            return $current_result && $new_result;
        } else {
            return $current_result || $new_result;
        }
    }

    private static function _get_rule_value($rule)
    {
        if (!is_array($rule) && count($rule) < 1) {
            return false;
        }
        if (strtolower($rule[0]) == "always") {
            return true;
        } else {
            try {

                $subject = strtolower($rule[0]);
                $option = strtolower($rule[1]);

                $function_name = "udesly_rule_evaluator_$subject" . "_$option";

                if (function_exists($function_name)) {
                    if (isset($rule[2])) {
                        return call_user_func($function_name, $rule[2]);
                    }
                    return $function_name();
                } else {
                    write_error_log('function missing ' . $function_name);
                    return false;
                }

            } catch (\Exception $exception) {
                return false;
            }
        }
    }

    public static function add_subject_post($subjects)
    {
        if (!in_array("post", $subjects)) {
            $subjects[] = "post";
        }
        return $subjects;
    }

    public static function add_rules_for_rcp_post($options)
    {
        $options['is paid content rcp'] = ["."];
        $options['is restricted content rcp'] = ["."];
        return $options;
    }

    public static function admin_hooks()
    {

        add_filter("udesly_rules_options_page", array(self::class, "add_rules_for_page"));
        add_filter("udesly_rules_options_user", array(self::class, "add_rules_for_users"));

        add_action("plugins_loaded", function () {
            if (defined('EDD_VERSION')) {
                add_filter("udesly_rules_options_user", array(self::class, "add_rules_for_edd_user"));
            }

            if (defined('RCP_PLUGIN_VERSION')) {
                add_filter("udesly_rules_options_user", array(self::class, "add_rules_for_rcp_user"));
                add_filter("udesly_rules_subject_keys", array(self::class, "add_subject_post"));
                add_filter("udesly_rules_options_post", array(self::class, "add_rules_for_rcp_post"));
            }
        });

        add_filter("udesly_rules_options_archive", array(self::class, "add_rules_for_archive"));

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
                'udesly-rules-configuration',
                __('Rule Configuration', UDESLY_TEXT_DOMAIN),
                array(self::class, 'rule_configuration')
            );
        });

        add_action('add_meta_boxes', function () {
            add_meta_box(
                'udesly-rules-page-configuration',
                __('Redirect Rule', UDESLY_TEXT_DOMAIN),
                array(self::class, 'redirect_rule_metabox'),
                array('page', 'post', 'product'),
                'side',
                'default'
            );
        });

        add_action('save_post', function ($post_id) {
            if (isset($_POST['udesly_rule_redirect'])) {
                $rule_id = intval($_POST['udesly_rule_redirect']);
            } else {
                $rule_id = 0;
            }
            if (isset($_POST['udesly_rule_redirect_where'])) {
                $where = sanitize_text_field($_POST['udesly_rule_redirect_where']);
            } else {
                $where = "";
            }

            $negative = "false";
            if (isset($_POST['udesly_rule_redirect_negative'])) {
                $negative = "true";
            }

            update_post_meta($post_id, 'udesly_rule_redirect_options', array(
                "rule" => $rule_id,
                "where" => $where,
                "negative" => $negative
            ));
        });

        add_action('admin_menu', function () {
            remove_meta_box('submitdiv', self::TYPE_NAME, 'core');
        });

        add_filter('get_user_option_screen_layout_udesly_posts_query', '__return_true');

        add_action('wp_ajax_udesly_save_rule_content', array(self::class, 'udesly_save_rule_content'));
    }

    public static function public_hooks()
    {
        add_action('init', array(self::class, 'register_type'));
        add_action('template_redirect', array(self::class, 'redirect_on_rule_satisfied'));
    }

    public static function redirect_on_rule_satisfied($template)
    {

        $saved_rule = maybe_unserialize(get_post_meta(get_queried_object_id(), 'udesly_rule_redirect_options', true));
        if (!is_array($saved_rule)) {
            return $template;
        }
        $id = $saved_rule['rule'];
        if (!$id || $id === 0) {
            return $template;
        } else {
            $res = self::eval_rule($id);
            if ($saved_rule['negative'] === "true") {
                $res = !$res;
            }
            if ($res) {
                $where = $saved_rule['where'];
                if ("" === $where) {
                    wp_redirect(get_site_url());
                    exit;
                } else {
                    wp_redirect(esc_url($where));
                    exit;
                }
            }
        }

        return $template;
    }

    public static function check_redirect()
    {

    }

    public static function get_saved_rules()
    {
        return get_posts(array(
            'posts_per_page' => -1,
            'post_type' => self::TYPE_NAME
        ));
    }

    public static function redirect_rule_metabox($post)
    {

        $rules = self::get_saved_rules();


        $saved_option = maybe_unserialize(get_post_meta($post->ID, 'udesly_rule_redirect_options', true));

        if (!is_array($saved_option)) {
            $saved_redirect = 0;

            $saved_where = "";
            $saved_negative = "false";
        } else {
            $saved_redirect = $saved_option['rule'];
            $saved_where = $saved_option['where'];
            $saved_negative = $saved_option['negative'];
        }

        $rules_options = array();
        $rules_options[0] = __('No redirect', UDESLY_TEXT_DOMAIN);
        foreach ($rules as $rule) {
            $rules_options[$rule->ID] = $rule->post_title;
        }

        ?>
        <label for="udesly_rule_redirect">
            <?php _e("Redirect Rule", UDESLY_TEXT_DOMAIN); ?>
            <select id="udesly_rule_redirect" name="udesly_rule_redirect">
                <?php foreach ($rules_options as $key => $value) : ?>
                    <option value="<?php echo $key; ?>" <?php echo $key == $saved_redirect ? "selected" : ""; ?>><?php echo $value; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <p></p>
        <label for="udesly_rule_redirect_where">
            <?php _e("Where to redirect", UDESLY_TEXT_DOMAIN); ?>
            <input placeholder="<?php _e("https://www.home.com", UDESLY_TEXT_DOMAIN); ?>" type="url"
                   name="udesly_rule_redirect_where" id="udesly_rule_redirect_where"
                   value="<?php echo $saved_where ? $saved_where : ""; ?>">
        </label>
        <p></p>
        <label for="udesly_rule_redirect_negative">
            <?php _e("Evaluate rule as negative", UDESLY_TEXT_DOMAIN); ?>
            <input type="checkbox" name="udesly_rule_redirect_negative"
                   id="udesly_rule_redirect_negative" <?php checked($saved_negative === "true"); ?>>
        </label>
        <?php
    }

    public static function register_type()
    {

        $labels = array(
            'name' => _x('Rule', 'post type general name', UDESLY_TEXT_DOMAIN),
            'singular_name' => _x('Rule', 'post type singular name', UDESLY_TEXT_DOMAIN),
            'menu_name' => _x('Rules', 'admin menu', UDESLY_TEXT_DOMAIN),
            'name_admin_bar' => _x('Rule', 'add new on admin bar', UDESLY_TEXT_DOMAIN),
            'add_new' => _x('Add New Rule', 'book', UDESLY_TEXT_DOMAIN),
            'add_new_item' => __('Add New Rule', UDESLY_TEXT_DOMAIN),
            'new_item' => __('New Rule', UDESLY_TEXT_DOMAIN),
            'edit_item' => __('Edit Rule', UDESLY_TEXT_DOMAIN),
            'view_item' => __('View Rule', UDESLY_TEXT_DOMAIN),
            'all_items' => __('All Rules', UDESLY_TEXT_DOMAIN),
            'search_items' => __('Search Rules', UDESLY_TEXT_DOMAIN),
            'parent_item_colon' => __('Parent Rules:', UDESLY_TEXT_DOMAIN),
            'not_found' => __('No rules found.', UDESLY_TEXT_DOMAIN),
            'not_found_in_trash' => __('No rules found in Trash.', UDESLY_TEXT_DOMAIN)
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