<?php


namespace Udesly\Dashboard\Views;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

use Udesly\Assets\Libraries;
use Udesly\Theme\UdeslyThemeData;

class CustomPostTypes
{

    public static function get_all_custom_post_types() {
        $cpts = UdeslyThemeData::getInstance()->custom_post_types;
        return self::get_all_cpt_settings($cpts);
    }

    public static function show()
    {
        $cpts = UdeslyThemeData::getInstance()->custom_post_types;

        ?>
        <style>
            [v-cloak] {
                display: none;
            }
        </style>
        <?php if (count($cpts)) : ?>
        <div id="udesly-settings" v-cloak="">
            <App>
                <h2 class="title"><?php _e('Custom Post Types', UDESLY_TEXT_DOMAIN); ?></h2>
                <p class="settings-description"><?php _e('Here you can find settings related to all custom post types defined in Webflow and not managed by third party plugins', UDESLY_TEXT_DOMAIN); ?></p>
               <Tabs>
                    <?php
                    self::cpt_tab( $cpts );
                    ?>
                </Tabs>

            </App>
        </div>
        <?php else : ?>
        <h3><?php _e('There are no custom post types to define in your theme', UDESLY_TEXT_DOMAIN); ?></h3>
        <?php endif; ?>
        <?php
        // wp_enqueue_script("udesly-settings", "http://localhost:8080/app.js", array(), UDESLY_ADAPTER_VERSION, true);
        Libraries::enqueue_vue_library("udesly-settings", "udeslySettings", json_encode(array(
            "settings" => self::get_all_cpt_settings($cpts),
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

    public static function get_cpt_setting( $cpt )
    {
        return self::get_settings($cpt, array(
            "enabled" => false,
            "singular" => $cpt,
            "plural" => $cpt . "s",
            "archive_rewrite" => "all-". $cpt . "s",
            "taxonomies" => "",
            "icon" => "dashicons-admin-generic",
            "archive_image" => ""
        ));
    }

    private static function get_all_cpt_settings( $cpts ) {
        $data = array();
        foreach ( $cpts as $cpt ) {
            $settings = self::get_cpt_setting($cpt);
            if (post_type_exists($cpt) && !$settings["enabled"]) {
                $settings["third_party"] = true;
            }
            $data["cpt_" . $cpt] = $settings;
        }
        return $data;
    }

    private static function cpt_tab( $cpts )
    {
        $options = array(
            'dashicons-menu'                    => 'menu',
            'dashicons-admin-site'              => 'site',
            'dashicons-dashboard'               => 'dashboard',
            'dashicons-admin-post'              => 'post',
            'dashicons-admin-media'             => 'media',
            'dashicons-admin-links'             => 'links',
            'dashicons-admin-page'              => 'page',
            'dashicons-admin-comments'          => 'comments',
            'dashicons-admin-appearance'        => 'appearance',
            'dashicons-admin-plugins'           => 'plugins',
            'dashicons-admin-users'             => 'users',
            'dashicons-admin-tools'             => 'tools',
            'dashicons-admin-settings'          => 'settings',
            'dashicons-admin-network'           => 'network',
            'dashicons-admin-home'              => 'home',
            'dashicons-admin-generic'           => 'generic',
            'dashicons-admin-collapse'          => 'collapse',
            'dashicons-filter'                  => 'filter',
            'dashicons-admin-customizer'        => 'customizer',
            'dashicons-admin-multisite'         => 'multisite',
            'dashicons-welcome-write-blog'      => 'write blog',
            'dashicons-welcome-add-page'        => 'add page',
            'dashicons-welcome-view-site'       => 'view site',
            'dashicons-welcome-widgets-menus'   => 'widgets and menus',
            'dashicons-welcome-comments'        => 'comments',
            'dashicons-welcome-learn-more'      => 'learn more',
            'dashicons-format-aside'            => 'aside',
            'dashicons-format-image'            => 'image',
            'dashicons-format-gallery'          => 'gallery',
            'dashicons-format-video'            => 'video',
            'dashicons-format-status'           => 'status',
            'dashicons-format-quote'            => 'quote',
            'dashicons-format-chat'             => 'chat',
            'dashicons-format-audio'            => 'audio',
            'dashicons-camera'                  => 'camera',
            'dashicons-images-alt'              => 'images (alt)',
            'dashicons-images-alt2'             => 'images (alt 2)',
            'dashicons-video-alt'               => 'video (alt)',
            'dashicons-video-alt2'              => 'video (alt 2)',
            'dashicons-video-alt3'              => 'video (alt 3)',
            'dashicons-media-archive'           => 'archive',
            'dashicons-media-audio'             => 'audio',
            'dashicons-media-code'              => 'code',
            'dashicons-media-default'           => 'default',
            'dashicons-media-document'          => 'document',
            'dashicons-media-interactive'       => 'interactive',
            'dashicons-media-spreadsheet'       => 'spreadsheet',
            'dashicons-media-text'              => 'text',
            'dashicons-media-video'             => 'video',
            'dashicons-playlist-audio'          => 'audio playlist',
            'dashicons-playlist-video'          => 'video playlist',
            'dashicons-controls-play'           => 'play player',
            'dashicons-controls-pause'          => 'player pause',
            'dashicons-controls-forward'        => 'player forward',
            'dashicons-controls-skipforward'    => 'player skip forward',
            'dashicons-controls-back'           => 'player back',
            'dashicons-controls-skipback'       => 'player skip back',
            'dashicons-controls-repeat'         => 'player repeat',
            'dashicons-controls-volumeon'       => 'player volume on',
            'dashicons-controls-volumeoff'      => 'player volume off',
            'dashicons-image-crop'              => 'crop',
            'dashicons-image-rotate'            => 'rotate',
            'dashicons-image-rotate-left'       => 'rotate left',
            'dashicons-image-rotate-right'      => 'rotate right',
            'dashicons-image-flip-vertical'     => 'flip vertical',
            'dashicons-image-flip-horizontal'   => 'flip horizontal',
            'dashicons-image-filter'            => 'filter',
            'dashicons-undo'                    => 'undo',
            'dashicons-redo'                    => 'redo',
            'dashicons-editor-bold'             => 'bold',
            'dashicons-editor-italic'           => 'italic',
            'dashicons-editor-ul'               => 'ul',
            'dashicons-editor-ol'               => 'ol',
            'dashicons-editor-quote'            => 'quote',
            'dashicons-editor-alignleft'        => 'alignleft',
            'dashicons-editor-aligncenter'      => 'aligncenter',
            'dashicons-editor-alignright'       => 'alignright',
            'dashicons-editor-insertmore'       => 'insertmore',
            'dashicons-editor-spellcheck'       => 'spellcheck',
            'dashicons-editor-expand'           => 'expand',
            'dashicons-editor-contract'         => 'contract',
            'dashicons-editor-kitchensink'      => 'kitchen sink',
            'dashicons-editor-underline'        => 'underline',
            'dashicons-editor-justify'          => 'justify',
            'dashicons-editor-textcolor'        => 'textcolor',
            'dashicons-editor-paste-word'       => 'paste',
            'dashicons-editor-paste-text'       => 'paste',
            'dashicons-editor-removeformatting' => 'remove formatting',
            'dashicons-editor-video'            => 'video',
            'dashicons-editor-customchar'       => 'custom character',
            'dashicons-editor-outdent'          => 'outdent',
            'dashicons-editor-indent'           => 'indent',
            'dashicons-editor-help'             => 'help',
            'dashicons-editor-strikethrough'    => 'strikethrough',
            'dashicons-editor-unlink'           => 'unlink',
            'dashicons-editor-rtl'              => 'rtl',
            'dashicons-editor-break'            => 'break',
            'dashicons-editor-code'             => 'code',
            'dashicons-editor-paragraph'        => 'paragraph',
            'dashicons-editor-table'            => 'table',
            'dashicons-align-left'              => 'align left',
            'dashicons-align-right'             => 'align right',
            'dashicons-align-center'            => 'align center',
            'dashicons-align-none'              => 'align none',
            'dashicons-lock'                    => 'lock',
            'dashicons-unlock'                  => 'unlock',
            'dashicons-calendar'                => 'calendar',
            'dashicons-calendar-alt'            => 'calendar',
            'dashicons-visibility'              => 'visibility',
            'dashicons-hidden'                  => 'hidden',
            'dashicons-post-status'             => 'post status',
            'dashicons-edit'                    => 'edit pencil',
            'dashicons-trash'                   => 'trash remove delete',
            'dashicons-sticky'                  => 'sticky',
            'dashicons-external'                => 'external',
            'dashicons-arrow-up'                => 'arrow-up',
            'dashicons-arrow-down'              => 'arrow-down',
            'dashicons-arrow-right'             => 'arrow-right',
            'dashicons-arrow-left'              => 'arrow-left',
            'dashicons-arrow-up-alt'            => 'arrow-up',
            'dashicons-arrow-down-alt'          => 'arrow-down',
            'dashicons-arrow-right-alt'         => 'arrow-right',
            'dashicons-arrow-left-alt'          => 'arrow-left',
            'dashicons-arrow-up-alt2'           => 'arrow-up',
            'dashicons-arrow-down-alt2'         => 'arrow-down',
            'dashicons-arrow-right-alt2'        => 'arrow-right',
            'dashicons-arrow-left-alt2'         => 'arrow-left',
            'dashicons-sort'                    => 'sort',
            'dashicons-leftright'               => 'left right',
            'dashicons-randomize'               => 'randomize shuffle',
            'dashicons-list-view'               => 'list view',
            'dashicons-exerpt-view'             => 'exerpt view',
            'dashicons-grid-view'               => 'grid view',
            'dashicons-move'                    => 'move',
            'dashicons-share'                   => 'share',
            'dashicons-share-alt'               => 'share',
            'dashicons-share-alt2'              => 'share',
            'dashicons-twitter'                 => 'twitter social',
            'dashicons-rss'                     => 'rss',
            'dashicons-email'                   => 'email',
            'dashicons-email-alt'               => 'email',
            'dashicons-facebook'                => 'facebook social',
            'dashicons-facebook-alt'            => 'facebook social',
            'dashicons-googleplus'              => 'googleplus social',
            'dashicons-networking'              => 'networking social',
            'dashicons-hammer'                  => 'hammer development',
            'dashicons-art'                     => 'art design',
            'dashicons-migrate'                 => 'migrate migration',
            'dashicons-performance'             => 'performance',
            'dashicons-universal-access'        => 'universal access accessibility',
            'dashicons-universal-access-alt'    => 'universal access accessibility',
            'dashicons-tickets'                 => 'tickets',
            'dashicons-nametag'                 => 'nametag',
            'dashicons-clipboard'               => 'clipboard',
            'dashicons-heart'                   => 'heart',
            'dashicons-megaphone'               => 'megaphone',
            'dashicons-schedule'                => 'schedule',
            'dashicons-wordpress'               => 'wordpress',
            'dashicons-wordpress-alt'           => 'wordpress',
            'dashicons-pressthis'               => 'press this',
            'dashicons-update'                  => 'update',
            'dashicons-screenoptions'           => 'screenoptions',
            'dashicons-info'                    => 'info',
            'dashicons-cart'                    => 'cart shopping',
            'dashicons-feedback'                => 'feedback form',
            'dashicons-cloud'                   => 'cloud',
            'dashicons-translation'             => 'translation language',
            'dashicons-tag'                     => 'tag',
            'dashicons-category'                => 'category',
            'dashicons-archive'                 => 'archive',
            'dashicons-tagcloud'                => 'tagcloud',
            'dashicons-text'                    => 'text',
            'dashicons-yes'                     => 'yes check checkmark',
            'dashicons-no'                      => 'no x',
            'dashicons-no-alt'                  => 'no x',
            'dashicons-plus'                    => 'plus add increase',
            'dashicons-plus-alt'                => 'plus add increase',
            'dashicons-minus'                   => 'minus decrease',
            'dashicons-dismiss'                 => 'dismiss',
            'dashicons-marker'                  => 'marker',
            'dashicons-star-filled'             => 'filled star',
            'dashicons-star-half'               => 'half star',
            'dashicons-star-empty'              => 'empty star',
            'dashicons-flag'                    => 'flag',
            'dashicons-warning'                 => 'warning',
            'dashicons-location'                => 'location pin',
            'dashicons-location-alt'            => 'location',
            'dashicons-vault'                   => 'vault safe',
            'dashicons-shield'                  => 'shield',
            'dashicons-shield-alt'              => 'shield',
            'dashicons-sos'                     => 'sos help',
            'dashicons-search'                  => 'search',
            'dashicons-slides'                  => 'slides',
            'dashicons-analytics'               => 'analytics',
            'dashicons-chart-pie'               => 'pie chart',
            'dashicons-chart-bar'               => 'bar chart',
            'dashicons-chart-line'              => 'line chart',
            'dashicons-chart-area'              => 'area chart',
            'dashicons-groups'                  => 'groups',
            'dashicons-businessman'             => 'businessman',
            'dashicons-id'                      => 'id',
            'dashicons-id-alt'                  => 'id',
            'dashicons-products'                => 'products',
            'dashicons-awards'                  => 'awards',
            'dashicons-forms'                   => 'forms',
            'dashicons-testimonial'             => 'testimonial',
            'dashicons-portfolio'               => 'portfolio',
            'dashicons-book'                    => 'book',
            'dashicons-book-alt'                => 'book',
            'dashicons-download'                => 'download',
            'dashicons-upload'                  => 'upload',
            'dashicons-backup'                  => 'backup',
            'dashicons-clock'                   => 'clock',
            'dashicons-lightbulb'               => 'lightbulb',
            'dashicons-microphone'              => 'microphone mic',
            'dashicons-desktop'                 => 'desktop monitor',
            'dashicons-laptop'                  => 'laptop',
            'dashicons-tablet'                  => 'tablet ipad',
            'dashicons-smartphone'              => 'smartphone iphone',
            'dashicons-phone'                   => 'phone',
            'dashicons-index-card'              => 'index card',
            'dashicons-carrot'                  => 'carrot food vendor',
            'dashicons-building'                => 'building',
            'dashicons-store'                   => 'store',
            'dashicons-album'                   => 'album',
            'dashicons-palmtree'                => 'palm tree',
            'dashicons-tickets-alt'             => 'tickets (alt)',
            'dashicons-money'                   => 'money',
            'dashicons-smiley'                  => 'smiley smile',
            'dashicons-thumbs-up'               => 'thumbs up',
            'dashicons-thumbs-down'             => 'thumbs down',
            'dashicons-layout'                  => 'layout',
            'dashicons-paperclip'               => 'paperclip',
        );

        wp_enqueue_media();

        ?>
        <Tab name="<?php _e('Custom Post Types', UDESLY_TEXT_DOMAIN); ?>" icon="fab fa-wordpress-simple">
            <?php foreach ($cpts as $cpt) : ?>
            <Expansion-Panel>
                <template v-slot:header>
                    <h3><?php echo ucfirst($cpt); ?></h3>
                </template>
                <?php if(isset($cpt['third_party']) && $cpt['third_party'] == true) : ?>
                <p><?php _e("This custom post type is already registered by another plugin", UDESLY_TEXT_DOMAIN); ?></p>
                <?php else : ?>
                <Help>
                    <template v-slot:help>
                        <?php _e("Enable custom post type", UDESLY_TEXT_DOMAIN); ?>
                    </template>
                    <Checkbox name="cpt_<?php echo $cpt; ?>.enabled"><?php _e("Enable", UDESLY_TEXT_DOMAIN); ?></Checkbox>
                </Help>

                        <Material-Input name="cpt_<?php echo $cpt; ?>.singular" required minlength="3" type="text">
                            <Help>
                                <template v-slot:help>
                                    <?php _e("Post type singular name", UDESLY_TEXT_DOMAIN); ?>
                                </template>
                                <?php _e("Post type singular name", UDESLY_TEXT_DOMAIN); ?>
                            </Help>
                        </Material-Input>
                    <Material-Input name="cpt_<?php echo $cpt; ?>.plural" required minlength="3" type="text">
                        <Help>
                            <template v-slot:help>
                                <?php _e("Post type plural name", UDESLY_TEXT_DOMAIN); ?>
                            </template>
                            <?php _e("Post type plural name", UDESLY_TEXT_DOMAIN); ?>
                        </Help>
                    </Material-Input>
                    <Material-Input name="cpt_<?php echo $cpt; ?>.archive_rewrite" required minlength="3" type="text">
                        <Help>
                            <template v-slot:help>
                                <?php _e("Archive url rewrite, this is the permalink of the archive page", UDESLY_TEXT_DOMAIN); ?>
                            </template>
                            <?php _e("Archive Permalink", UDESLY_TEXT_DOMAIN); ?>
                        </Help>
                    </Material-Input>

                    <Material-Input name="cpt_<?php echo $cpt; ?>.taxonomies" pattern="^[0-9a-zA-z]+(,[0-9a-zA-z]+){0,2}$" type="text">
                        <Help>
                            <template v-slot:help>
                                <?php _e("Taxonomies associated to this post type, separate them by comma, don't forget that to avoid collisions the actual name you will use in Webflow is {your custom post type}_{your taxonomy}", UDESLY_TEXT_DOMAIN); ?>
                            </template>
                            <?php _e("Taxonomies", UDESLY_TEXT_DOMAIN); ?>
                        </Help>
                    </Material-Input>
                    <Material-Select name="cpt_<?php echo $cpt; ?>.icon" required options='<?php echo json_encode($options); ?>'>
                        <Help>
                            <template v-slot:help>
                                <?php _e("Icon that will be used in the menu.", UDESLY_TEXT_DOMAIN); ?>
                            </template>
                            <?php _e('Icon', UDESLY_TEXT_DOMAIN); ?>
                        </Help>
                    </Material-Select>
                    <Material-Image-Picker name="cpt_<?php echo $cpt; ?>.archive_image" pattern="^[0-9a-zA-z]+(,[0-9a-zA-z]+){0,2}$" type="text">
                        <Help>
                            <template v-slot:help>
                                <?php _e("Archive Image", UDESLY_TEXT_DOMAIN); ?>
                            </template>
                            <?php _e("Archive Image", UDESLY_TEXT_DOMAIN); ?>
                        </Help>
                    </Material-Image-Picker>
                <?php endif; ?>
            </Expansion-Panel>
            <?php endforeach; ?>
        </Tab>
        <?php
    }

    private static function get_settings($context = "cpt", $default = array())
    {
        $options = get_option("udesly_adapter_settings_cpt_$context", array());
        return wp_parse_args($options, $default);
    }

    private static function update_settings($context, $data)
    {
        return update_option("udesly_adapter_settings_cpt_$context", $data);
    }

    public static function admin_hooks()
    {
        add_action("wp_ajax_save_udesly_cpt", array(self::class, "save_udesly_cpt"));
    }

    public static function save_udesly_cpt()
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
        wp_send_json_success("saved_successfully", 200);

        wp_die();
    }
}