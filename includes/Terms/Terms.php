<?php

namespace Udesly\Terms;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class Terms
{

    public static function admin_hooks()
    {

        add_action('admin_init', function() {
            $terms = apply_filters('udesly_attach_featured_image_terms', array('category', 'tag', 'post_tag'));

            foreach ($terms as $term) {

                add_action("edit_" . $term . "_form_fields", array(self::class, 'extra_term_fields'));
                add_action("edited_$term", array(self::class, 'save_extra_term_fields'));
                add_action("edited_term", array(self::class, 'save_extra_term_fields'));
            }
        });

        add_action('add_meta_boxes_post',array(self::class,'main_category_add_meta_box'));
        add_action('add_meta_boxes_product',array(self::class,'main_category_add_meta_box'));
        add_action('add_meta_boxes_download',array(self::class,'main_category_add_meta_box'));
        add_action( 'save_post', array(self::class,'main_category_save_meta_boxes_data'), 10, 2 );

    }

    // Adds extra term field
    static function extra_term_fields( $tag ) {    //check for existing featured ID
        $t_id     = $tag->term_id;
        $cat_meta = get_term_meta($t_id, '_featured_image', true);
        wp_enqueue_media();
        ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="cat_Image_url"><?php _e( 'Featured Image' ); ?></label></th>
            <td>
                <?php echo self::image_uploader_field('featured_image', $cat_meta); ?>
            </td>
        </tr>
        <script>
            jQuery(document).ready( function( $ ) {

                $('body').on('click', '.udesly_upload_image_button', function () {

                    var button = $(this),
                        custom_uploader = wp.media({
                            title: 'Insert image',
                            library: {
                                // uncomment the next line if you want to attach image to the current post
                                // uploadedTo : wp.media.view.settings.post.id,
                                type: 'image'
                            },
                            button: {
                                text: 'Use this image' // button label text
                            },
                            multiple: false // for multiple image selection set to true
                        }).on('select', function () { // it also has "open" and "close" events
                            var attachment = custom_uploader.state().get('selection').first().toJSON();
                            $(button).removeClass('button').html('<img class="true_pre_image" src="' + attachment.url + '" style="max-width:95%;display:block;" />').next().val(attachment.id).next().show();

                        })
                            .open();
                });
                $('body').on('click', '.udesly_remove_image_button', function(){
                    $(this).hide().prev().val('').prev().addClass('button').html('Upload image');
                    return false;
                });
            });
        </script>
        <?php

    }

    static function save_extra_term_fields($term_id) {
        if ( isset( $_POST['featured_image'] ) ) {
            update_term_meta(sanitize_key($_POST['tag_ID']), '_featured_image', sanitize_text_field($_POST['featured_image']));
        }
    }

    static function image_uploader_field( $name, $value = '') {
        $image = ' button">Upload image';
        $image_size = 'full'; // it would be better to use thumbnail size here (150x150 or so)
        $display = 'none'; // display state ot the "Remove image" button

        if( $image_attributes = wp_get_attachment_image_src( $value, $image_size ) ) {

            // $image_attributes[0] - image URL
            // $image_attributes[1] - image width
            // $image_attributes[2] - image height

            $image = '"><img src="' . $image_attributes[0] . '" style="max-width:95%;display:block;" />';
            $display = 'inline-block';

        }

        return '
	<div>
		<a href="#" class="udesly_upload_image_button' . $image . '</a>
		<input type="hidden" name="' . $name . '" id="' . $name . '" value="' . $value . '" />
		<a href="#" class="udesly_remove_image_button" style="display:inline-block;display:' . $display . '">Remove image</a>
	</div>';
    }

    public static function main_category_add_meta_box( $post ) {
        add_meta_box( 'main_category_meta_box', __( 'Main Category', 'udesly' ), array(
            self::class,
            'main_category_build_meta_box'
        ), $post->post_type, 'side', 'high' );
    }

    public static function main_category_build_meta_box( $post ) {
        wp_nonce_field( basename( __FILE__ ), 'main_category_meta_box_nonce' );
        $current_main_category = get_post_meta( $post->ID, '_udesly_main_category', true );

        $post_type = $post->post_type;
        switch ($post_type) {
            case "post":
                $tax = "category";
                break;
            case "product":
                $tax = "product_cat";
                break;
            case "download":
                $tax = "download_category";
                break;
        }
        if (isset($tax)) {
            wp_dropdown_categories( array(
                'show_option_none' => __( 'No Main Category', 'udesly' ),
                'include'          => wp_get_post_categories( $post->ID ),
                'selected'         => $current_main_category,
                'name'             => 'udesly_main_cat',
                'taxonomy'         => $tax
            ) );
        }



    }

    public static function main_category_save_meta_boxes_data( $post_id ) {
        if ( ! isset( $_POST['main_category_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['main_category_meta_box_nonce'], basename( __FILE__ ) ) ) {
            return;
        }
        if ( isset( $_REQUEST['udesly_main_cat'] ) ) {
            update_post_meta( $post_id, '_udesly_main_category', sanitize_key( $_REQUEST['udesly_main_cat'] ) );

        }

    }
}