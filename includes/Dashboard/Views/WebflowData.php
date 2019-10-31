<?php


namespace Udesly\Dashboard\Views;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

use Udesly\Assets\Libraries;
use Udesly\Theme\UdeslyThemeData;


class WebflowData
{

    public static function show()
    {
        $data = UdeslyThemeData::getInstance();
        $missing_pages = $data->get_missing_pages();

        Libraries::enqueue_vue_library("udesly-webflow-data", "udeslyThemeData", json_encode(array(
            "ajaxurl" => admin_url('admin-ajax.php'),
            "nonce" => wp_create_nonce("udesly_import_missing_data"),
            "missingPages" => count($missing_pages),
        )));

        ?>
        <style>
            [v-cloak] {
                display: none;
            }
        </style>
        <div id="udesly-webflow-data" v-cloak>
            <App>
                <h2 class="title"><?php _e('Theme Data', UDESLY_TEXT_DOMAIN); ?></h2>
                <p class="settings-description"><?php _e('Here you can import pages and data of your Webflow template and get an overview of elements used inside pages', UDESLY_TEXT_DOMAIN); ?></p>
                <Tabs>
                    <?php
                    self::import_webflow_tab();
                    self::theme_report_tab();
                    ?>
                </Tabs>
            </App>
        </div>
        <?php
    }

    private static function import_webflow_tab()
    {
        $data = UdeslyThemeData::getInstance();
        if ($data->frontend_editor_enabled) {
            $title = __('Import pages and data', UDESLY_TEXT_DOMAIN);
            $description = __('To make the theme fully functional you need to import pages and frontend editor data. Don\'t forget to set up your front page in <strong>Settings > Reading</strong>', UDESLY_TEXT_DOMAIN);
        } else {
            $title = __('Import pages', UDESLY_TEXT_DOMAIN);
            $description = __('To make the theme fully functional you need to import pages created in Webflow. Don\'t forget to set up your front page in Settings > Reading', UDESLY_TEXT_DOMAIN);
        }
        ?>
        <Tab name="<?php _e('Webflow Data', UDESLY_TEXT_DOMAIN); ?>" icon="fas fa-file-import">
            <Responsive-Grid style="align-items: center; grid-gap: 12px;">
                <div class="udesly-card" style="background-color: white;  box-shadow: none;">
                    <img src="<?php echo UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . "assets/images/webflow.svg"; ?>" style="width: 35px; height: auto;"/>
                    <h2><?php echo $title; ?></h2>
                    <p><?php echo $description; ?></p>
                    <p><?php _e('Missing pages:', UDESLY_TEXT_DOMAIN); ?>
                        <State-Variable name="missingPages"></State-Variable>
                    </p>
                    <Udesly-Button idle="<?php _e('Import', UDESLY_TEXT_DOMAIN); ?>"
                                   loading="<?php _e('Importing...', UDESLY_TEXT_DOMAIN); ?>"
                                   success="<?php _e('Imported successfully!', UDESLY_TEXT_DOMAIN); ?>"
                                   failed="<?php _e('Failed to import data...', UDESLY_TEXT_DOMAIN); ?>"
                                   action="importMissingData"></Udesly-Button>
                </div>
                <div class="udesly-card" style="background-color: white;  box-shadow: none;">
                    <img src="<?php echo UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . "assets/images/delete.svg"; ?>" style="width: 35px; height: auto;"/>
                    <h2><?php _e('Delete pages', UDESLY_TEXT_DOMAIN); ?></h2>
                    <p><?php _e('By clicking here you will delete all pages imported from Webflow', UDESLY_TEXT_DOMAIN); ?></p>
                    <Udesly-Button idle="<?php _e('Delete', UDESLY_TEXT_DOMAIN); ?>"
                                   loading="<?php _e('Deleting...', UDESLY_TEXT_DOMAIN); ?>"
                                   success="<?php _e('Deleted successfully!', UDESLY_TEXT_DOMAIN); ?>"
                                   failed="<?php _e('Failed to delete...', UDESLY_TEXT_DOMAIN); ?>"
                                   action="deletePages"></Udesly-Button>
                </div>
                <?php if ($data->frontend_editor_enabled) : ?>
                    <div class="udesly-card" style="background-color: white;  box-shadow: none;">
                        <img src="<?php echo UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . "assets/images/delete-frontend-editor.svg"; ?>" style="width: 35px; height: auto;"/>
                        <h2><?php _e('Delete frontend editor data', UDESLY_TEXT_DOMAIN); ?></h2>
                        <p><?php _e('Delete all frontend editor imported data. Useful if you want to restart from the webflow data. Don\'t forget to reimport data after deleting, otherwise your theme won\'t work', UDESLY_TEXT_DOMAIN); ?></p>
                        <Udesly-Button idle="<?php _e('Delete', UDESLY_TEXT_DOMAIN); ?>"
                                       loading="<?php _e('Deleting...', UDESLY_TEXT_DOMAIN); ?>"
                                       success="<?php _e('Deleted successfully!', UDESLY_TEXT_DOMAIN); ?>"
                                       failed="<?php _e('Failed to delete data...', UDESLY_TEXT_DOMAIN); ?>"
                                       action="deleteFrontendEditorData"></Udesly-Button>
                    </div>
                    <div class="udesly-card" style="background-color: white; box-shadow: none;">
                        <img src="<?php echo UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . "assets/images/delete-cache.svg"; ?>" style="width: 35px; height: auto;"/>
                        <h2><?php _e('Clear frontend editor cache', UDESLY_TEXT_DOMAIN); ?></h2>
                        <p><?php _e('Clear frontend editor transients that are used to cache data, they will be recreated automatically when someone navigates your pages', UDESLY_TEXT_DOMAIN); ?></p>
                        <Udesly-Button idle="<?php _e('Clear', UDESLY_TEXT_DOMAIN); ?>"
                                       loading="<?php _e('Clearing...', UDESLY_TEXT_DOMAIN); ?>"
                                       success="<?php _e('Cleared successfully!', UDESLY_TEXT_DOMAIN); ?>"
                                       failed="<?php _e('Failed to clear data...', UDESLY_TEXT_DOMAIN); ?>"
                                       action="clearFrontendEditor"></Udesly-Button>
                    </div>
                <?php endif; ?>
            </Responsive-Grid>


        </Tab>
        <?php
    }

    private static function theme_report_tab()
    {

        $data = UdeslyThemeData::getInstance();
        $reports_count = count($data->page_reports);

        if (!$reports_count) {
            return;
        }
        $missing_data_report = $data->adapter_data->get_missing_data();
        $missing_data = $missing_data_report["data"];
        $missing_data_count = $missing_data_report["count"];

        ?>
        <Tab name="<?php _e('Theme Report', UDESLY_TEXT_DOMAIN); ?>" icon="fas fa-database">
            <?php if ($missing_data_count > 0) : ?>
                <h2><?php _e('Missing Elements', UDESLY_TEXT_DOMAIN); ?></h2>
                <p><?php _e('Here you can find a list of elements like queries, boxes and menus you used in your theme and are not present in the WordPress database', UDESLY_TEXT_DOMAIN); ?></p>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; grid-auto-columns: 1fr 1fr 1fr; grid-gap: 8px; align-items: flex-start;">
                    <?php if ($missing_data->queries) : ?>

                        <Expansion-Panel>
                            <template v-slot:header>
                                <h3><?php _e('Post Queries', UDESLY_TEXT_DOMAIN); ?></h3>
                            </template>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <?php foreach ($missing_data->queries as $query) : ?>
                                    <li> <?php echo $query; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </Expansion-Panel>

                    <?php endif; ?>
                    <?php if ($missing_data->lists) : ?>

                        <Expansion-Panel>
                            <template v-slot:header>
                                <h3><?php _e('Taxonomies Queries', UDESLY_TEXT_DOMAIN); ?></h3>
                            </template>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <?php foreach ($missing_data->lists as $list) : ?>
                                    <li> <?php echo $list; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </Expansion-Panel>

                    <?php endif; ?>
                    <?php if ($missing_data->menus) : ?>

                        <Expansion-Panel>
                            <template v-slot:header>
                                <h3><?php _e('Menus', UDESLY_TEXT_DOMAIN); ?></h3>
                            </template>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <?php foreach ($missing_data->menus as $menu) : ?>
                                    <li> <?php echo $menu; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </Expansion-Panel>

                    <?php endif; ?>
                    <?php if ($missing_data->rules) : ?>

                        <Expansion-Panel>
                            <template v-slot:header>
                                <h3><?php _e('Rules', UDESLY_TEXT_DOMAIN); ?></h3>
                            </template>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <?php foreach ($missing_data->rules as $rule) : ?>
                                    <li> <?php echo $rule; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </Expansion-Panel>

                    <?php endif; ?>
                    <?php if ($missing_data->boxes) : ?>

                        <Expansion-Panel>
                            <template v-slot:header>
                                <h3><?php _e('Boxes', UDESLY_TEXT_DOMAIN); ?></h3>
                            </template>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <?php foreach ($missing_data->boxes as $boxes) : ?>
                                    <li> <?php echo $boxes; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </Expansion-Panel>

                    <?php endif; ?>
                </div>
            <p></p>
                <hr>
            <?php endif; ?>
            <h2><?php _e('Pages reports', UDESLY_TEXT_DOMAIN); ?></h2>
            <p></p>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; grid-auto-columns: 1fr 1fr 1fr; grid-gap: 8px; align-items: flex-start;">
                <?php foreach ($data->page_reports as $page_slug => $page_report) : ?>
                    <?php $page_title = str_replace("_", " ", $page_slug);
                    $page_title = ucwords(str_replace("-", " ", $page_title)); ?>
                    <Expansion-Panel>
                        <template v-slot:header>
                            <h3><?php echo $page_title; ?></h3>
                        </template>
                        <p><?php _e("In this page you used: ", UDESLY_TEXT_DOMAIN); ?></p>
                        <?php if ($page_report->queries) : ?>
                            <p><strong><?php _e('Post Queries', UDESLY_TEXT_DOMAIN); ?></strong></p>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <?php foreach ($page_report->queries as $query) : ?>
                                    <li> <?php echo $query; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($page_report->lists) : ?>

                            <p><strong><?php _e('Taxonomies Queries', UDESLY_TEXT_DOMAIN); ?></strong></p>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <?php foreach ($page_report->lists as $list) : ?>
                                    <li> <?php echo $list; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($page_report->rules) : ?>
                            <p><strong><?php _e('Rules', UDESLY_TEXT_DOMAIN); ?></strong></p>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <?php foreach ($page_report->rules as $rule) : ?>
                                    <li> <?php echo $rule; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($page_report->boxes) : ?>
                            <p><strong><?php _e('Boxes', UDESLY_TEXT_DOMAIN); ?></strong></p>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <?php foreach ($page_report->boxes as $box) : ?>
                                    <li> <?php echo $box; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($page_report->custom_fields) : ?>
                            <p><strong><?php _e('Custom Fields', UDESLY_TEXT_DOMAIN); ?></strong></p>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <?php foreach ($page_report->custom_fields as $cf) : ?>
                                    <li> <?php echo $cf; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($page_report->menus) : ?>
                            <p><strong><?php _e('Menus', UDESLY_TEXT_DOMAIN); ?></strong></p>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <?php foreach ($page_report->menus as $menu) : ?>
                                    <li> <?php echo $menu; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </Expansion-Panel>
                <?php endforeach; ?>
            </div>
        </Tab>
        <?php
    }

}