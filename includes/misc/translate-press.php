<?php


function udesly_translate_press_get_url_for_language($language_code){

    $trp = TRP_Translate_Press::get_trp_instance();
    $url_converter = $trp->get_component('url_converter');
    $url = $url_converter->get_url_for_language($language_code);

    return $url;
}

function udesly_translate_press_is_current_language($language_code) {
    global $TRP_LANGUAGE;
    return $TRP_LANGUAGE == $language_code;
}