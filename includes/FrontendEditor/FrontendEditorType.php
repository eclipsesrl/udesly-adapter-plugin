<?php


namespace Udesly\FrontendEditor;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

use Udesly\Theme\UdeslyThemeData;

class FrontendEditorType
{
    public static function register_frontend_editor_type()
    {
        register_post_type('udesly_fe_data', array(
                "label" => "Udesly Frontend Editor Data",
                "public" => false,
            )
        );
    }

    public static function admin_hooks()
    {

    }

    public static function public_hooks()
    {
        add_action('init', '\Udesly\FrontendEditor\FrontendEditorType::register_frontend_editor_type');
        add_action('init', array(self::class, 'init_frontend_editor'));
    }

    public static function init_frontend_editor() {
        $data = UdeslyThemeData::getInstance();
        if ($data->frontend_editor_enabled && self::can_use_frontend_editor()) {
            add_action('wp_enqueue_scripts', function() {
                $jsPath = UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . 'externals/udesly-frontend-editor/udesly-frontend-editor.js';
                $cssPath = UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . 'externals/udesly-frontend-editor/udesly-frontend-editor.css';
                wp_enqueue_media();

                wp_enqueue_script('udesly-frontend-editor', $jsPath, array(), UDESLY_ADAPTER_VERSION, true );
                wp_enqueue_style('udesly-frontend-editor', $cssPath);


                wp_enqueue_style("font-awesome-udesly", "https://use.fontawesome.com/releases/v5.8.1/css/all.css", array(), "5.8.1", 'all');
                wp_localize_script('udesly-frontend-editor', 'udesly_fe', array('ajax_url' => admin_url('admin-ajax.php'), 'ajax_nonce' => wp_create_nonce('udesly-fe-security')));
            });
            add_action('wp_ajax_save_frontend_editor_content_editable', array(self::class, 'save_frontend_editor_content_editable'));
            add_action('wp_ajax_nopriv_save_frontend_editor_content_editable', array(self::class, 'save_frontend_editor_content_editable'));
            add_action('wp_footer', function() {
                echo "<div id='udesly-frontend-editor-root'></div>";
            });
        }
    }

    public static function save_frontend_editor_content_editable()
    {
        check_ajax_referer('udesly-fe-security', 'security');
        if (self::can_use_frontend_editor()) {
            if (!isset($_REQUEST['page_name'])) {
                wp_send_json(
                    array(
                        'error' => 'missing page name parameter'
                    )
                );
                wp_die();
            }
            $page_name = $_REQUEST['page_name'];

            $data = $_POST;

            $text_results = 0;
            $link_results = 0;
            $img_results = 0;
            $bg_image_results = 0;
            $iframe_results = 0;
            foreach ($data as $key => $value) {
                if (udesly_string_starts_with($key, 'text_')) {
                        $text_results++;
                } elseif (udesly_string_starts_with($key, 'link_')) {
                        $link_results++;
                } elseif (udesly_string_starts_with($key, 'image_')) {
                       $data[$key] = json_encode(array('id' => $value));
                       $img_results++;
                } elseif (udesly_string_starts_with($key, 'bg_image_')) {
                    $bg_image_results++;
                } elseif (udesly_string_starts_with($key, 'iframe_')) {
                    $iframe_results++;
                } elseif (udesly_string_starts_with($key, 'video_')) {
                    $data[$key] = (object) array(
                        "videos" => [$value]
                    );
                    $iframe_results++;
                }
            }

            self::upsert_frontend_editor_page_data($page_name, $data, true);
            wp_send_json(array(  // send JSON back
                'text_results' => $text_results,
                'link_results' => $link_results,
                'bg_image_results' => $bg_image_results,
                'image_results' => $img_results,
                'iframe_results' => $iframe_results
            ));
        } else {
            wp_send_json(
                array(
                    'error' => 'you are not allowed to do this operation'
                )
            );
            wp_die();
        }


        wp_die();
    }

    public static function can_use_frontend_editor() {
        if (current_user_can('administrator')) {
            $res = true;
        } else {
            $res = false;
        }
        return apply_filters('udesly_user_can_use_frontend_editor', $res);
    }

    public static function delete_fe_transients() {
        global $wpdb;
        $fe_transients = $wpdb->get_results(
            "SELECT option_name AS name FROM $wpdb->options WHERE option_name LIKE '_transient_udesly_fe_data_%'"
        );
        foreach ($fe_transients as $transient) {
            delete_transient(str_replace('_transient_', '', $transient->name));
        }
    }


    public static function upsert_frontend_editor_page_data($page_name, $data, $invert = false)
    {
        $page_slug = "$page_name" . "_udesly_fe_data";
        delete_transient("udesly_fe_data_$page_name");
        $fe_page = udesly_get_post_by_slug($page_slug, OBJECT, "udesly_fe_data");
        if ($fe_page) {
            // Merge and update Data
            $id = $fe_page->ID;
            $page_saved_data = udesly_mb_unserialize($fe_page->post_content);


            $merged_data = $invert ? self::merge_objects($page_saved_data, $data) : self::merge_objects($data, $page_saved_data);

            $post_id = wp_update_post(
                array(
                    "ID" => $id,
                    "post_content" => serialize($merged_data)
                ),
                true
            );
            if (is_wp_error($post_id)) {
                return false;
            } else {
                return true;
            }
        } else {
            // Insert Data
            $post_id = wp_insert_post(array(
                'post_title' => $page_slug,
                'post_name' => $page_slug,
                "post_content" => serialize($data),
                'post_type' => 'udesly_fe_data',
                'post_status' => 'publish',
            ));
            return $post_id !== 0;
        }
    }

    private static function merge_objects($obj1, $obj2)
    {
        $arr1 = (array) $obj1;
        $arr2 = (array) $obj2;
        $merged = array_intersect_key( $arr2, $arr1) + $arr1;
        return (object) $merged;
    }

}