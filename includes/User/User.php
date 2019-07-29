<?php

namespace Udesly\User;

use Udesly\Dashboard\Views\Settings;
if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class User
{
    public static function public_hooks()
    {

        add_action('user_contactmethods', array(self::class, 'change_profile_fields'));

        add_action('wp_ajax_udesly_wp_login', array(self::class, 'udesly_wp_login'));
        add_action('wp_ajax_nopriv_udesly_wp_login', array(self::class, 'udesly_wp_login'));

        add_action('wp_ajax_udesly_wp_register', array(self::class, 'udesly_wp_register'));
        add_action('wp_ajax_nopriv_udesly_wp_register', array(self::class, 'udesly_wp_register'));

        add_action('wp_ajax_udesly_wp_lost_password', array(self::class, 'udesly_wp_lost_password'));
        add_action('wp_ajax_nopriv_udesly_wp_lost_password', array(self::class, 'udesly_wp_lost_password'));

        add_action('wp_ajax_udesly_wp_reset_password', array(self::class, 'udesly_wp_reset_password'));
        add_action('wp_ajax_nopriv_udesly_wp_reset_password', array(self::class, 'udesly_wp_reset_password'));

        add_action('wp_ajax_udesly_wp_send_form', array(self::class, 'udesly_wp_send_form'));
        add_action('wp_ajax_nopriv_udesly_wp_send_form', array(self::class, 'udesly_wp_send_form'));
    }

    public static function udesly_wp_send_form() {

        if (!check_ajax_referer('udesly-ajax-action', 'security', false)) {
            wp_send_json_error(array(
                "code" => 403,
                "message" => "failed security check"
            ),403);
            wp_die();
        }

        if ( ! isset( $_POST['form_data'] ) ) {
            wp_send_json_error(array(
                "code" => 400,
                "message" => "Missing form data"
            ),400);
            wp_die();
        }

        $message = __( 'Someone sent a message from ', UDESLY_TEXT_DOMAIN )  . get_bloginfo( 'name' ) .  __( ':', UDESLY_TEXT_DOMAIN ) . "\r\n\r\n";

        $form_data = json_decode(stripslashes($_POST['form_data']), true);


        foreach ($form_data as $key => $value){
            $message .= sanitize_key($key) . ": " . sanitize_textarea_field($value) . "\r\n";
        }


        $email_settings = Settings::get_email_settings();

        $to = apply_filters('udesly_wp_send_form_to', $email_settings["to"]);

        $ccs = $email_settings["ccs"] === "" ? array() : explode(",", $email_settings["ccs"]);

        $ccs = apply_filters('udesly_wp_send_form_ccs', $ccs);

        $subject = apply_filters('udesly_wp_send_form_subject', $email_settings["subject"]);

        $message = apply_filters('udesly_wp_send_form_message', $message, $form_data);

        if(count($ccs) > 0 ){
            $headers = array();
            $headers[] = 'From: ' . get_bloginfo( "admin_email" );
            foreach ($ccs as $email_cc) {
                $email_cc = trim($email_cc);
                $headers[] = 'Cc: ' . $email_cc;
            }
        }else{
            $headers = '';
        }

        $headers = apply_filters('udesly_wp_send_form_headers', $headers);

        if ( wp_mail( $to, $subject, $message, $headers ) ) {
            wp_send_json_success( array( 'form_sent' => true ) );
            wp_die();
        }

        wp_send_json_error( array( 'form_sent' => false, 'message' => __("Server couldn't send email") ), 500 );

        wp_die();
    }

    public static function udesly_wp_reset_password() {

        $errors = new \WP_Error();


        if (!check_ajax_referer('udesly-ajax-action', 'security', false)) {
            wp_send_json_error(array(
                "code" => 403,
                "message" => "failed security check"
            ), 403);
            wp_die();
        }

        $pass1 	= sanitize_text_field($_POST['password']);
        $pass2 	= sanitize_text_field($_POST['password_repeat']);
        $key 	= sanitize_text_field($_POST['user_key']);
        $login 	= sanitize_text_field($_POST['user_login']);

        $user = check_password_reset_key( $key, $login );

        // check to see if user added some string
        if( empty( $pass1 ) || empty( $pass2 ) )
            $errors->add( 'password_required', __( 'Password is required field' ) );

        // is pass1 and pass2 match?
        if ( isset( $pass1 ) && $pass1 != $pass2 )
            $errors->add( 'password_reset_mismatch', __( 'The passwords do not match.' ) );

        // is pass too short?
        if ( isset( $pass1 ) && $pass1 == $pass2 && strlen($pass1) < 6) {
            $errors->add( 'password_too_short', __( 'The passwords is too short, min. 6 characters.' ) );
        }

        do_action( 'validate_password_reset', $errors, $user );

        if ( ( ! $errors->get_error_code() ) && isset( $pass1 ) && !empty( $pass1 ) ) {
            reset_password($user, $pass1);
            echo json_encode( array( 'error' => false ) );
            wp_die();
        }

        // display error message
        if ( $errors->get_error_code() )  {
           wp_send_json_error( array( 'error' => true, 'message' => $errors->get_error_message() ), 400 );
            wp_die();
        }
        wp_send_json_success( array( 'error' => false ) );
        wp_die();
    }

    public static function udesly_wp_lost_password() {
        global $wpdb, $wp_hasher;

        if (!check_ajax_referer('udesly-ajax-action', 'security', false)) {
            wp_send_json_error(array(
                "code" => 403,
                "message" => "failed security check"
            ),403);
            wp_die();
        }

        $user_login = sanitize_text_field( $_POST['user_login'] );

        $errors = new \WP_Error();

        if ( empty( $user_login ) ) {
            $errors->add( 'empty_username', __( 'Enter a username or e-mail address.' ) );
        } else if ( strpos( $user_login, '@' ) ) {
            $user_data = get_user_by( 'email', trim( $user_login ) );
            if ( empty( $user_data ) ) {
                $errors->add( 'invalid_email', apply_filters('udesly_lost_password_invalid_email_message', __( 'There is no user registered with that email address.' )) );
            }
        } else {
            $login     = trim( $user_login );
            $user_data = get_user_by( 'login', $login );
        }

        do_action( 'lostpassword_post', $errors );

        if ( $errors->get_error_code() ) {
            wp_send_json_error( array( 'error' => true, 'message' => $errors->get_error_message() ), 400 );
            wp_die();
        }

        if ( ! $user_data ) {
            $errors->add( 'invalidcombo', __( 'Invalid username or email.' ) );
            wp_send_json_error( array( 'error' => true, 'message' => $errors->get_error_message() ), 400 );
            wp_die();
        }

        // Redefining user_login ensures we return the right case in the email.
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
        $key        = get_password_reset_key( $user_data );

        if ( is_wp_error( $key ) ) {
            wp_send_json_error( array( 'error' => true, 'message' => $key->get_error_message() ), 400 );
            wp_die();
        }

        $message = __( 'Someone requested that the password be reset for the following account:' ) . "\r\n\r\n";
        $message .= network_home_url( '/' ) . "\r\n\r\n";
        $message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
        $message .= __( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "\r\n\r\n";
        $message .= __( 'To reset your password, visit the following address:' ) . "\r\n\r\n";

        $message .= esc_url_raw( udesly_get_permalink_by_slug( $_POST['page_slug'] ) . "?action=rp&key=$key&login=" . urlencode( $user_login ) ) . "\r\n";

        if ( is_multisite() ) {
            $blogname = $GLOBALS['current_site']->site_name;
        } else /*
			 * The blogname option is escaped with esc_html on the way into the database
			 * in sanitize_option we want to reverse this for the plain text arena of emails.
			 */ {
            $blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
        }

        $title = sprintf( __( '%s - Password Reset' ), $blogname );
        $title = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );

        $message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );

        if ( wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) ) {
            wp_send_json_success( array( 'error' => false ) );
            wp_die();
        } else {
            $errors->add( 'could_not_sent', __( 'The e-mail could not be sent.' ), 'message' );
        }


        // display error message
        if ( $errors->get_error_code() ) {
            wp_send_json_error( array( 'error' => true, 'message' => $errors->get_error_message() ), 400 );
            wp_die();
        }

        // return proper result
        wp_die();
    }

    static function udesly_wp_register()
    {
        if (!check_ajax_referer('udesly-ajax-action', 'security', false)) {
            wp_send_json_error(array(
                "code" => 403,
                "message" => "failed security check"
            ),403);
            wp_die();
        }
        if ( !get_option( 'users_can_register' ) ) {
            wp_send_json_error( array( 'status' => false, 'message' => __( 'Sorry, registration is not currently allowed' ) ), 400 );
            wp_die();
        }

        if ( ! isset( $_POST['mail'] ) ) {
            wp_send_json_error( array( 'status' => false, 'message' => 'Mail not set' ), 400 );
            wp_die();
        }
        // Post values
        $username  = sanitize_text_field($_POST['username']);
        $password  = sanitize_text_field($_POST['password']);
        $password_confirm  = sanitize_text_field($_POST['password_repeat']);
        $email     = sanitize_email($_POST['mail']);
        $name      = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);


        $userdata = array(
            'user_login' => $username,
            'user_pass'  => $password,
            'user_email' => $email,
            'first_name' => $name,
            'last_name'  => $last_name,
        );


        // check to see if user added some string
        if( empty( $password ) || empty( $password_confirm ) ) {
            wp_send_json_error( array( 'status' => false, 'message' => __( 'Password is required field' ) ), 400 );
            wp_die();
        }

        // is pass1 and pass2 match?
        if ( isset( $password ) && $password != $password_confirm ) {
            wp_send_json_error( array( 'status' => false, 'message' => __( 'The passwords do not match.' )), 400);
            wp_die();
        }

        // is pass too short?
        if ( isset( $password ) && $password == $password_confirm && strlen($password) < 6) {
            wp_send_json_error( array( 'status' => false, 'message' => __( 'The passwords is too short, min. 6 characters.' ) ), 400 );
            wp_die();
        }

        $valid = apply_filters('udesly_wp_register_password_strength_check', true, $password);

        if(!$valid) {
            wp_send_json_error( array( 'status' => false, 'message' => apply_filters('udesly_wp_register_password_strength_check_message', __( 'The password is invalid' ) ) ), 400 );
            wp_die();
        }

        $user_id = wp_insert_user( $userdata );

        // Return
        if ( ! is_wp_error( $user_id ) ) {
            echo json_encode( array( 'status' => true ) );

            $info                  = array();
            $info['user_login']    = $username;
            $info['user_password'] = $password;
            $info['remember']      = true;

            wp_signon( $info, false );

            do_action('udesly_wp_registration_success', $user_id);

        } else {
            wp_send_json_error( array( 'status' => false, 'message' => $user_id->get_error_message() ), 400 );
        }
        wp_die();
    }

    static function udesly_wp_login() {
        if(!check_ajax_referer('udesly-ajax-action', 'security', false)) {
            wp_send_json_error(array(
                "code" => 403,
                "message" => "failed security check"
            ),403);
            wp_die();
        }
        $info                  = array();
        $info['user_login']    = sanitize_text_field($_POST['username']);
        $info['user_password'] = sanitize_text_field($_POST['password']);
        $info['remember']      = $_POST['rememberme'] ? true : false;

        $user_signon = wp_signon( $info, apply_filters('udesly_wp_signon_secure_cookie', false, $info['user_login']) );
        if ( is_wp_error( $user_signon ) ) {
            wp_send_json_error( array( 'loggedin' => false, 'message' => apply_filters('udesly_wp_login_error_message', $user_signon->get_error_message() ) ), 403 );
        } else {
            wp_send_json_success( array( 'loggedin' => true ) );
        }

        wp_die();
    }

    static function change_profile_fields($contactmethods)
    {
        unset($contactmethods['aim']);
        unset($contactmethods['jabber']);
        unset($contactmethods['yim']);

        $contactmethods['twitter'] = 'Twitter';
        $contactmethods['facebook'] = 'Facebook';
        $contactmethods['linkedin'] = 'LinkedIn';
        $contactmethods['youtube'] = 'Youtube';
        $contactmethods['dribble'] = 'Dribble';
        $contactmethods['instagram'] = 'Instagram';
        $contactmethods['reddit'] = 'Reddit';
        $contactmethods['phonenumber'] = 'Phone Number';

        return $contactmethods;
    }
}