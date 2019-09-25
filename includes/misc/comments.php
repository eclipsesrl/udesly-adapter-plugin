<?php

/**
 * Gets next comments page link
 *
 * @param int $max_page
 * @return bool|string
 */
function udesly_get_next_comments_link($max_page = 0)
{
    global $wp_query;

    if (!is_singular()) {
        return false;
    }

    $page = get_query_var('cpage');

    if (!$page) {
        $page = 1;
    }

    $nextpage = intval($page) + 1;

    if (empty($max_page)) {
        $max_page = $wp_query->max_num_comment_pages;
    }

    if (empty($max_page)) {
        $max_page = get_comment_pages_count();
    }

    if ($nextpage > $max_page) {
        return false;
    }
    return esc_url(get_comments_pagenum_link($nextpage, $max_page));
}

/**
 * Gets previous comments page link
 * @param int $max_page
 * @return bool|string
 */
function udesly_get_previous_comments_link($max_page = 0)
{
    if (!is_singular()) {
        return false;
    }

    $page = get_query_var('cpage');

    if (intval($page) <= 1) {
        return false;
    }

    $prevpage = intval($page) - 1;
    return esc_url(esc_url(get_comments_pagenum_link($prevpage)));
}

/**
 * Lists comments with correct args
 * @param $randomId
 */
function udesly_list_comments($randomId)
{
    wp_list_comments(array(
        'callback' => "udesly_comment_callback_$randomId",
        'end-callback' => "udesly_end_comment_callback_$randomId",
        'style' => 'ul',
        'type' => 'comment'
    ));
}

/**
 * Gets reply comment
 *
 * @param string $classLink
 * @param array $args
 * @param null $comment
 * @param null $post
 * @return mixed|string|void
 */
function udesly_get_comment_reply_link($classLink = '', $args = array(), $comment = null, $post = null)
{

    $settings = \Udesly\Dashboard\Views\Settings::get_comments_settings();

    global $comment_args, $comment_depth;
    $defaults = array(
        'add_below' => 'comment',
        'respond_id' => 'respond',
        'reply_text' => __($settings['reply_text']),
        'reply_to_text' => __($settings['reply_to_text']),
        'login_text' => __($settings['login_text']),
        'max_depth' => $comment_args['max_depth'],
        'depth' => $comment_depth,
        'before' => '',
        'after' => '',
    );

    $args = wp_parse_args($args, $defaults);

    if (0 == $args['depth'] || $args['max_depth'] <= $args['depth']) {
        return '';
    }

    $comment = get_comment($comment);

    if (empty($post)) {
        $post = $comment->comment_post_ID;
    }

    $post = get_post($post);

    if (!comments_open($post->ID)) {
        return '';
    }

    $args = apply_filters('comment_reply_link_args', $args, $comment, $post);

    if (get_option('comment_registration') && !is_user_logged_in()) {
        $link = sprintf(
            '<a rel="nofollow" class="comment-reply-login ' . $classLink . '" href="%s">%s</a>',
            esc_url(wp_login_url(get_permalink())),
            $args['login_text']
        );
    } else {
        $data_attributes = array(
            'commentid' => $comment->comment_ID,
            'postid' => $post->ID,
            'belowelement' => $args['add_below'] . '-' . $comment->comment_ID,
            'respondelement' => $args['respond_id'],
        );

        $data_attribute_string = '';

        foreach ($data_attributes as $name => $value) {
            $data_attribute_string .= " data-${name}=\"" . esc_attr($value) . '"';
        }

        $data_attribute_string = trim($data_attribute_string);

        $link = sprintf(
            "<a rel='nofollow' class='comment-reply-link $classLink' href='%s' %s aria-label='%s'>%s</a>",
            esc_url(
                add_query_arg(
                    array(
                        'replytocom' => $comment->comment_ID,
                        'unapproved' => false,
                        'moderation-hash' => false,
                    )
                )
            ) . '#' . $args['respond_id'],
            $data_attribute_string,
            esc_attr(sprintf($args['reply_to_text'], $comment->comment_author)),
            $args['reply_text']
        );
    }


    return apply_filters('comment_reply_link', $args['before'] . $link . $args['after'], $args, $comment, $post);
}


/**
 * Outputs the form class
 *
 * @param $formClass
 * @param $submitClass
 * @param $textAreaClass
 * @param $inputClass
 */
function udesly_comments_form($formClass, $submitClass, $textAreaClass, $inputClass) {

    $settings = \Udesly\Dashboard\Views\Settings::get_comments_settings();

    global $commenter, $req;
    comment_form(array(
        "class_form" => $formClass,
        "label_submit" => $settings['label_submit'],
        "title_reply" => $settings['title_reply'],
        "title_reply_to" => $settings['title_reply_to'],
        "class_submit" => $submitClass,
        "comment_field" => '<p class="comment-form-comment"><label for="comment" class="${labelClass}">' . _x( 'Comment', 'noun' ) .
            '</label><textarea id="comment" name="comment" class="'. $textAreaClass . '" cols="45" rows="8" aria-required="true">' .
            '</textarea></p>',
        "fields" => array(
            'author' =>
                '<p class="comment-form-author"><label for="author" class="${labelClass}">' . __( 'Name' ) .
                ( $req ? '<span class="required">*</span>' : '' ) . '</label>' .
                '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) .
                '" class=" '. $inputClass . '" /></p>',
            'email' =>
                '<p class="comment-form-email"><label for="email" class="${labelClass}">' . __( 'Email' ) .
                ( $req ? '<span class="required">*</span>' : '' ) . '</label>' .
                '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) .
                '" class=" '. $inputClass . '" /></p>',
            'url' =>
                '<p class="comment-form-url"><label for="url" class="${labelClass}">' . __( 'Website' ) . '</label>' .
                '<input id="url" name="url" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) .
                '" class=" '. $inputClass . '" /></p>',
        ),
    ));
}