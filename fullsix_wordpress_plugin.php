<?php
/*
Plugin Name: FullSIX Twig Integration inside WordPress
Plugin URI: http://www.fullsix.es/
Description: Used to interpret Twig within articles and pages content/title/...
Version: 0.1
Author: FullSIX España SLU
Author URI: http://www.fullsix.es/
License: Copyright (c) 2012 FullSIX España SLU
*/

function fullsix_wordpress_plugin_init() {
    //add_filter('the_title', 'f6twig_filter_user_content', 1);
    //add_filter('the_title_rss', 'f6twig_filter_user_content', 1);
    add_filter('the_content', 'fullsix_wordpress_plugin_filter_user_content', 1);
    add_filter('the_content_rss', 'fullsix_wordpress_plugin_filter_user_content', 1);
    add_filter('the_excerpt', 'fullsix_wordpress_plugin_filter_user_content', 1);
    add_filter('the_excerpt_rss', 'fullsix_wordpress_plugin_filter_user_content', 1);
}
add_action('init', 'fullsix_wordpress_plugin_init');

function fullsix_wordpress_plugin_filter_user_content($content) {
    global $container, $response;
    if ($container === null) return $content;
    $tags = get_the_tags();
    if ($tags == null) return $content;
    $found = false;
    foreach ($tags as $tag) {
        $tagName = $tag->name;
        if (strlen($tagName) >= 4 && substr($tagName, 0, 4) == 'twig') {
            $found = true;
            break;
        }
    }
    if (!$found) return $content;
    $twig = $container->get("twig");
    return $twig->render($content, $response->getParams());
}

//Pages Tags & Category Meta boxes
function add_pages_meta_boxes() {
    add_meta_box('tagsdiv-post_tag', __('Page Tags'), 'post_tags_meta_box', 'page', 'side', 'low');
    add_meta_box('categorydiv', __('Categories'), 'post_categories_meta_box', 'page', 'normal', 'core');
}
add_action('add_meta_boxes', 'add_pages_meta_boxes');

add_action('init','attach_category_to_page');
function attach_category_to_page() {
    register_taxonomy_for_object_type('category','page');
}
//end

// This is used to disable wordpress canonical redirects (find the best suited page where url is not completely correct)
add_action('template_redirect', 'remove_404_redirect', 1);
function remove_404_redirect(){
    if (is_404()){
        $id = max(get_query_var('p'), get_query_var('page_id'), get_query_var('attachment_id'));
        $redirect_url = false;
        if ($id && $redirect_post = get_post($id)) {
            $post_type_obj = get_post_type_object($redirect_post->post_type);
            if ($post_type_obj->public)
                $redirect_url = get_permalink($redirect_post);
        }
        if (!$redirect_url)
            remove_filter('template_redirect', 'redirect_canonical');
    }
}


// Anoying redirection
remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
