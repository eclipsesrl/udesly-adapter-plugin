<?php


namespace Udesly\Dashboard\Views;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

use Udesly\Assets\Libraries;
use Udesly\Theme\DataManager;

class Settings
{

    public static function show()
    {

        ?>
        <style>
            [v-cloak] {
                display: none;
            }
        </style>
        <div id="udesly-settings" v-cloak="">
            <App>
                <h2 class="title"><?php _e('Settings', UDESLY_TEXT_DOMAIN); ?></h2>
                <p class="settings-description"><?php _e('Here you can find settings related to your site and to elements inserted in your theme', UDESLY_TEXT_DOMAIN); ?></p>
                <Tabs>
                    <?php
                    self::blog_tab();
                    self::woocommerce_tab();
                    self::search_tab();
                    self::email_tab();
                    self::tools_tab();
                    self::comments_tab();
                    self::js_tab();
                    ?>
                </Tabs>
            </App>
        </div>
        <?php
        Libraries::enqueue_vue_library("udesly-settings", "udeslySettings", json_encode(array(
            "settings" => array(
                "tools" => self::get_tools_settings(),
                "blog" => self::get_blog_settings(),
                "wc" => self::get_wc_settings(),
                "search" => self::get_search_settings(),
                "email" => self::get_email_settings(),
                "comments" => self::get_comments_settings(),
                "js" => self::get_js_settings(),
            ),
            "ajaxurl" => admin_url('admin-ajax.php'),
            "nonce" => wp_create_nonce("udesly_settings"),
            "labels" => array(
                "save" => __("Save", UDESLY_TEXT_DOMAIN),
                "saving" => __("Saving...", UDESLY_TEXT_DOMAIN),
                "save_success" => __("Saved Successfully", UDESLY_TEXT_DOMAIN),
                "save_failed" => __("Saving Data failed", UDESLY_TEXT_DOMAIN)
            )
        )));

    }



    private static function blog_tab()
    {

        $categories_options = array(
                "all" => __('Show All', UDESLY_TEXT_DOMAIN),
                "child_of" => __('Only childrens', UDESLY_TEXT_DOMAIN),
                "parent" => __('Only direct childrens', UDESLY_TEXT_DOMAIN)
        )

        ?>
        <Tab name="<?php _e('Blog', UDESLY_TEXT_DOMAIN); ?>" icon="fab fa-wordpress-simple">
            <Expansion-Panel open="true">
                <template v-slot:header>
                    <h3><?php _e("Archive Title", UDESLY_TEXT_DOMAIN); ?></h3>
                    <p> <?php _e("Customize your blog archive title", UDESLY_TEXT_DOMAIN); ?></p>
                </template>

                <Material-Input name="blog.archive_title" required minlength="3" type="text">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Customise the title you will see on archive page if you used the element Archive Title.", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('Archive Title', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
                <Material-Input name="blog.general_archive_title" required minlength="3" type="text">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Customise the title you will see on general archive title.", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('General Archive Title', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
                <template v-slot:header>
                    <h3><?php _e("Archive Description", UDESLY_TEXT_DOMAIN); ?></h3>
                    <p> <?php _e("Customize your blog archive description", UDESLY_TEXT_DOMAIN); ?></p>
                </template>

                <Material-Input name="blog.archive_description" minlength="" type="text">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Here you can set the description for main Blog page that will appear if you used the element Archive Description.", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('Archive Description', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
                <Material-Input name="blog.category_title" required minlength="3" type="text">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Customise the title you will see on a category page if you used the element Archive Title. Don't remove %s if you want to see category name", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('Category Title', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
                <Material-Input name="blog.tag_title" required minlength="3" type="text">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Customise the title you will see on a tag page if you used the element Archive Title. Don't remove %s if you want to see tag name", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('Tag Title', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
                <Material-Input name="blog.author_title" required minlength="3" type="text">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Customise the title you will see on a author page if you used the element Archive Title. Don't remove %s if you want to see author name.", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('Author Title', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
                <Checkbox
                        name="blog.hide_password_protected"><?php _e("Hide Password Protected posts from Archive", UDESLY_TEXT_DOMAIN); ?></Checkbox>
            </Expansion-Panel>
            <Expansion-Panel>
                <template v-slot:header>
                    <h3><?php _e("Excerpt", UDESLY_TEXT_DOMAIN); ?></h3>
                    <p> <?php _e("Customize excerpt behaviour", UDESLY_TEXT_DOMAIN); ?></p>
                </template>
                <template v-slot:description>
                    <p><?php _e("Excerpt settings will be ignored if you set a custom excerpt inside a post", UDESLY_TEXT_DOMAIN); ?></p>
                </template>
                <Material-Input name="blog.excerpt_length" required min="0" type="number">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Number of words of your excerpt.", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('Excerpt Length', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
                <Material-Input name="blog.excerpt_more" required type="text">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Last word of the post excerpt", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('Last Word', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
            </Expansion-Panel>
            <Expansion-Panel>
                <template v-slot:header>
                    <h3><?php _e("Categories", UDESLY_TEXT_DOMAIN); ?></h3>
                    <p> <?php _e("Customize categories behaviour", UDESLY_TEXT_DOMAIN); ?></p>
                </template>
                <template v-slot:description>
                    <p><?php _e("These settings are related to elements Blog Categories", UDESLY_TEXT_DOMAIN); ?></p>
                </template>
                <Material-Select name="blog.show_categories" required options='<?php echo json_encode($categories_options); ?>'>
                    <Help>
                        <template v-slot:help>
                            <?php _e("Decide which categories to show.", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('Categories to show', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Select>
            </Expansion-Panel>
        </Tab>
        <?php
    }

    private static function search_tab() {
        ?>
        <Tab name="<?php _e('Search', UDESLY_TEXT_DOMAIN); ?>" icon="fas fa-search">
            <Expansion-Panel>
                <template v-slot:header>
                    <h3><?php _e("Excerpt", UDESLY_TEXT_DOMAIN); ?></h3>
                    <p> <?php _e("Customize excerpt behaviour", UDESLY_TEXT_DOMAIN); ?></p>
                </template>
                <template v-slot:description>
                    <p><?php _e("Excerpt settings will be ignored if you set a custom excerpt inside a post", UDESLY_TEXT_DOMAIN); ?></p>
                </template>
                <Material-Input name="search.excerpt_length" required min="0" type="number">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Number of words of your excerpt.", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('Excerpt Length', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
                <Material-Input name="search.excerpt_more" required type="text">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Last word of the post excerpt.", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('Last Word', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
            </Expansion-Panel>
            <Expansion-Panel>
                <template v-slot:header>
                    <h3><?php _e("Posts per Page", UDESLY_TEXT_DOMAIN); ?></h3>
                    <p> <?php _e("Customize search page", UDESLY_TEXT_DOMAIN); ?></p>
                </template>
                <template v-slot:description>
                    <p><?php _e("These settings are related to elements search=results", UDESLY_TEXT_DOMAIN); ?></p>
                </template>
                <Material-Input name="search.posts_per_page" required type="number">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Number of posts.", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('Posts per Page', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
                <Help>
                    <template v-slot:help>
                        <?php _e("Redirect directly to the page if there is only one match", UDESLY_TEXT_DOMAIN); ?>
                    </template>
                    <Checkbox
                            name="search.one_match_redirect"><?php _e("One match redirect", UDESLY_TEXT_DOMAIN); ?></Checkbox>
                </Help>
            </Expansion-Panel>
        </Tab>
        <?php
    }

    private static function woocommerce_tab()
    {

        $categories_options = array(
            "all" => __('Show All', UDESLY_TEXT_DOMAIN),
            "child_of" => __('Only childrens', UDESLY_TEXT_DOMAIN),
            "parent" => __('Only direct childrens', UDESLY_TEXT_DOMAIN)
        );

        $product_images_options = array(
            "thumbnail" => __('Thumbnail', UDESLY_TEXT_DOMAIN),
            "medium" => __('Medium', UDESLY_TEXT_DOMAIN),
            "large" => __('Large', UDESLY_TEXT_DOMAIN),
            "full" => __('Full', UDESLY_TEXT_DOMAIN)
        );

        if (is_plugin_active('woocommerce/woocommerce.php')) : ?>
            <Tab name="<?php _e('WooCommerce', UDESLY_TEXT_DOMAIN); ?>" icon="fas fa-shopping-cart">
                <Expansion-Panel open="true">
                    <template v-slot:header>
                        <h3><?php _e("WooCommerce Styles", UDESLY_TEXT_DOMAIN); ?></h3>
                    </template>

                    <Help>
                        <template v-slot:help>
                            <?php _e("Enqueue WooCommerce General CSS, it can conflict with your Webflow style if you used class 'button'", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <Checkbox
                                name="wc.general_styles"><?php _e("General css", UDESLY_TEXT_DOMAIN); ?></Checkbox>
                    </Help>
                    <Help>
                        <template v-slot:help>
                            <?php _e("Enqueue WooCommerce Layout CSS", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <Checkbox
                                name="wc.layout_styles"><?php _e("Layout css", UDESLY_TEXT_DOMAIN); ?></Checkbox>
                    </Help>
                    <Help>
                        <template v-slot:help>
                            <?php _e("Enqueue WooCommerce Smallscreen CSS", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <Checkbox
                                name="wc.smallscreen_styles"><?php _e("Smallscreen css", UDESLY_TEXT_DOMAIN); ?></Checkbox>
                    </Help>
                    <Help>
                        <template v-slot:help>
                            <?php _e("Disable Javascript Select 2 and use default selects", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <Checkbox
                                name="wc.disable_select_woo"><?php _e("Disable Select 2", UDESLY_TEXT_DOMAIN); ?></Checkbox>
                    </Help>
                    <Material-Select name="wc.product_images_size" required options='<?php echo json_encode($product_images_options); ?>'>
                        <Help>
                            <template v-slot:help>
                                <?php _e("Size of WooCommerce product images", UDESLY_TEXT_DOMAIN); ?>
                            </template>
                            <?php _e('Product Images size', UDESLY_TEXT_DOMAIN); ?>
                        </Help>
                    </Material-Select>
                </Expansion-Panel>
                <Expansion-Panel>
                    <template v-slot:header>
                        <h3><?php _e("Categories", UDESLY_TEXT_DOMAIN); ?></h3>
                        <p> <?php _e("Customize categories behaviour", UDESLY_TEXT_DOMAIN); ?></p>
                    </template>
                    <template v-slot:description>
                        <p><?php _e("These settings are related to elements WC Categories", UDESLY_TEXT_DOMAIN); ?></p>
                    </template>
                    <Material-Select name="wc.show_categories" required options='<?php echo json_encode($categories_options); ?>'>
                        <Help>
                            <template v-slot:help>
                                <?php _e("Type of Categories to show.", UDESLY_TEXT_DOMAIN); ?>
                            </template>
                            <?php _e('Categories to show', UDESLY_TEXT_DOMAIN); ?>
                        </Help>
                    </Material-Select>
                </Expansion-Panel>
                <Expansion-Panel>
                    <template v-slot:header>
                        <h3><?php _e("Related and Upsells", UDESLY_TEXT_DOMAIN); ?></h3>
                        <p> <?php _e("Customize related and upsells", UDESLY_TEXT_DOMAIN); ?></p>
                    </template>
                    <template v-slot:description>
                        <p><?php _e("These settings are related to elements wc=upsells and wc=related", UDESLY_TEXT_DOMAIN); ?></p>
                    </template>
                    <Material-Input name="wc.upsells_limit" required type="number">
                        <Help>
                            <template v-slot:help>
                                <?php _e("Maximum number of Upsells to show.", UDESLY_TEXT_DOMAIN); ?>
                            </template>
                            <?php _e('Upsells Limit', UDESLY_TEXT_DOMAIN); ?>
                        </Help>
                    </Material-Input>

                    <Material-Input name="wc.related_limit" required type="number">
                        <Help>
                            <template v-slot:help>
                                <?php _e("Maximum number of Related to show.", UDESLY_TEXT_DOMAIN); ?>
                            </template>
                            <?php _e('Related Limit', UDESLY_TEXT_DOMAIN); ?>
                        </Help>
                    </Material-Input>
                    <Material-Input name="wc.cart_cross_sells_limit" required type="number">
                        <Help>
                            <template v-slot:help>
                                <?php _e("Maximum number of Cross Sells to show in the cart page.", UDESLY_TEXT_DOMAIN); ?>
                            </template>
                            <?php _e('Cart Cross Sells Limit', UDESLY_TEXT_DOMAIN); ?>
                        </Help>
                    </Material-Input>
                </Expansion-Panel>
            </Tab>
        <?php endif;
    }

    private static function tools_tab()
    {
        $tempOptions = array(
            "coming-soon" => __('Coming soon', UDESLY_TEXT_DOMAIN),
            "maintenance" => __('Maintenance', UDESLY_TEXT_DOMAIN),
        );
        ?>
        <Tab name="<?php _e('Tools', UDESLY_TEXT_DOMAIN); ?>" icon="fas fa-tools">
            <Expansion-Panel open="true">
                <template v-slot:header>
                    <h3><?php _e("Temporary Mode", UDESLY_TEXT_DOMAIN); ?></h3>
                </template>

                <Help>
                    <template v-slot:help>
                        <?php _e("Temporary mode will redirect users to page you set as \"Temporary Page\" inside the Udesly Adapter, or 404 if you don't have one. Admin users will still be able to navigate the site as usual.", UDESLY_TEXT_DOMAIN); ?>
                    </template>
                    <Checkbox
                            name="tools.temporary_mode_enabled"><?php _e("Enable temporary mode", UDESLY_TEXT_DOMAIN); ?></Checkbox>
                </Help>

                <p>
                    <Material-Select options='<?php echo json_encode($tempOptions); ?>'
                                     name="tools.temporary_mode_type">
                        <Help>
                            <template v-slot:help>
                                <?php _e('Temporary page mode will change the status code sent by the server', UDESLY_TEXT_DOMAIN); ?>
                                <ul>
                                    <li><?php _e('Coming Soon: 307', UDESLY_TEXT_DOMAIN); ?></li>
                                    <li><?php _e('Maintenance: 503', UDESLY_TEXT_DOMAIN); ?></li>
                                </ul>
                            </template>
                            <?php _e('Temporary Page Mode', UDESLY_TEXT_DOMAIN); ?>
                        </Help>
                    </Material-Select>
                </p>
            </Expansion-Panel>
        </Tab>
        <?php

    }

    private static function comments_tab()
    {
        ?>
        <Tab name="<?php _e('Comments', UDESLY_TEXT_DOMAIN); ?>" icon="fas fa-comments">
            <Expansion-Panel open="true">
                <template v-slot:header>
                    <h3><?php _e("Comments Texts", UDESLY_TEXT_DOMAIN); ?></h3>
                </template>
                <Material-Input name="comments.reply_text" required minlength="3" type="text">

                    <?php _e('Reply:', UDESLY_TEXT_DOMAIN); ?>

                </Material-Input>
                <Material-Input name="comments.reply_to_text" required minlength="3" type="text">

                        <?php _e('Reply to:', UDESLY_TEXT_DOMAIN); ?>

                </Material-Input>
                <Material-Input name="comments.login_text" required minlength="3" type="text">

                    <?php _e('Login:', UDESLY_TEXT_DOMAIN); ?>

                </Material-Input>
                <Material-Input name="comments.label_submit" required minlength="3" type="text">

                    <?php _e('Submit:', UDESLY_TEXT_DOMAIN); ?>

                </Material-Input>
                <Material-Input name="comments.title_reply" required minlength="3" type="text">

                    <?php _e('Reply Title:', UDESLY_TEXT_DOMAIN); ?>

                </Material-Input>
                <Material-Input name="comments.title_reply_to" required minlength="3" type="text">

                    <?php _e('Reply To Title:', UDESLY_TEXT_DOMAIN); ?>

                </Material-Input>
            </Expansion-Panel>
        </Tab>
        <?php

    }

    private static function email_tab()
    {
        ?>
        <Tab name="<?php _e('Email', UDESLY_TEXT_DOMAIN); ?>" icon="fas fa-envelope">
        <Expansion-Panel open="true">
            <template v-slot:header>
                <h3><?php _e("General Form", UDESLY_TEXT_DOMAIN); ?></h3>
            </template>

            <Material-Input name="email.to" required type="text" autocomplete="off">
                <Help>
                    <template v-slot:help>
                        <?php _e("Email used as receiver of the Forms.", UDESLY_TEXT_DOMAIN); ?>
                    </template>
                    <?php _e('To', UDESLY_TEXT_DOMAIN); ?>
                </Help>
            </Material-Input>
            <Material-Input name="email.ccs" type="email" multiple pattern="^([\w+-.%]+@[\w-.]+\.[A-Za-z]{2,4},*[\W]*)+$" autocomplete="off">
                <Help>
                    <template v-slot:help>
                        <?php _e("CCs emails that will receive Forms, separated by comma.", UDESLY_TEXT_DOMAIN); ?>
                    </template>
                    <?php _e('CCs', UDESLY_TEXT_DOMAIN); ?>
                </Help>
            </Material-Input>
            <Material-Input name="email.subject" required type="text" autocomplete="off">
                <Help>
                    <template v-slot:help>
                        <?php _e("Subject of the email sent for the Forms.", UDESLY_TEXT_DOMAIN); ?>
                    </template>
                    <?php _e('Email Subject', UDESLY_TEXT_DOMAIN); ?>
                </Help>
            </Material-Input>
        </Expansion-Panel>
        </Tab>
        <?php
    }

    private static function js_tab()
    {
        ?>
        <Tab name="<?php _e('JS', UDESLY_TEXT_DOMAIN); ?>" icon="fab fa-js-square">
            <Expansion-Panel open="true">
                <template v-slot:header>
                    <h3><?php _e("Scripts Settings", UDESLY_TEXT_DOMAIN); ?></h3>
                </template>
                <Help>
                    <template v-slot:help>
                        <?php _e("If this option is enabled forms redirect will append a cache invalidation get attribute e.g: ?cb=123123.", UDESLY_TEXT_DOMAIN); ?>
                    </template>
                    <Checkbox
                            name="js.break_cache_forms"><?php _e("Break Cache on Forms redirect", UDESLY_TEXT_DOMAIN); ?></Checkbox>
                </Help>

                <Help>
                    <template v-slot:help>
                        <?php _e("If this option is enabled on page load a script will make an extra request to refetch nonce, enable this if you have cache issues", UDESLY_TEXT_DOMAIN); ?>
                    </template>
                    <Checkbox
                            name="js.refetch_nonce"><?php _e("Refetch nonce through Ajax", UDESLY_TEXT_DOMAIN); ?></Checkbox>
                </Help>

                <Material-Input name="js.wc_notification_duration" type="number" min="0">
                    <Help>
                        <template v-slot:help>
                            <?php _e("Duration in seconds of the notification", UDESLY_TEXT_DOMAIN); ?>
                        </template>
                        <?php _e('WooCommerce Notifications duration', UDESLY_TEXT_DOMAIN); ?>
                    </Help>
                </Material-Input>
            </Expansion-Panel>
        </Tab>
        <?php
    }

    private static function get_settings($context = "blog", $default = array())
    {
        $options = get_option("udesly_adapter_settings_$context", array());
        return wp_parse_args($options, $default);
    }

    private static function update_settings($context, $data)
    {
        return update_option("udesly_adapter_settings_$context", $data);
    }

    public static function get_wc_settings() {
        return self::get_settings("wc", array(
           "general_styles" => true,
           "layout_styles" => true,
           "smallscreen_styles" => true,
            "show_categories" => "all",
            "upsells_limit" => 4,
            "related_limit" => 4,
            "disable_select_woo" => false,
            'cart_cross_sells_limit' => 2,
            "product_images_size" => "full"
        ));
    }

    public static function get_search_settings() {
        return self::get_settings("search", array(
            "excerpt_length" => 20,
            "excerpt_more" => "...",
            "posts_per_page" => 6,
            "one_match_redirect" => false
        ));
    }

    public static function get_comments_settings() {
        return self::get_settings("comments", array(
            "reply_text" => "Reply",
            "reply_to_text" => 'Reply to %s',
            "login_text" => 'Log in to Reply',
            "label_submit" => 'Post Comment',
            'title_reply' => "Leave a Reply",
            "title_reply_to" => "Write a reply to %s"
        ));
    }

    public static function get_js_settings() {
        return self::get_settings("js", array(
            "break_cache_forms" => false,
            "wc_notification_duration" => 3,
            "refetch_nonce" => false
        ));
    }

    public static function get_tools_settings()
    {
        return self::get_settings("tools", array(
            "temporary_mode_type" => "maintenance",
            "temporary_mode_enabled" => false
        ));
    }

    public static function get_email_settings()
    {
        return self::get_settings("email", array(
            "to" => get_bloginfo("admin_email"),
            "ccs" => "",
            "subject" => __( "New message from " ) . get_bloginfo( 'name' ),
        ));
    }

    public static function get_blog_settings()
    {
        return self::get_settings("blog", array(
            "archive_title" => "Archive",
            "archive_description" => "Blog page description",
            "author_title" => "Author: %s",
            "category_title" => "Category: %s",
            "tag_title" => "Tag: %s",
            "excerpt_length" => 20,
            "excerpt_more" => "...",
            "show_categories" => "all",
            "general_archive_title" => "Archives: %s",
            "hide_password_protected" => false,
        ));
    }

    public static function admin_hooks()
    {
        add_action("wp_ajax_save_udesly_settings", array(self::class, "save_udesly_settings"));
    }

    public static function save_udesly_settings()
    {

        if (!wp_verify_nonce($_POST['security'], 'udesly_settings')) {
            wp_send_json_error("security_check", 403);
            wp_die();
        }
        if (!isset($_POST['data'])) {
            wp_send_json_error("Missing Data", 400);
            wp_die();
        }

        $data = json_decode(stripslashes($_POST['data']), true);

        foreach ($data as $context => $value) {
            self::update_settings($context, $value);
        }

        DataManager::udesly_delete_check_data();

        wp_send_json_success("saved_successfully", 200);

        wp_die();
    }
}