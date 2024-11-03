<?php

/**
 * @desc Hide add media button from content editor
 */

// Removes menu items from wp-admin, uncomment to remove

function remove_menus()
{

    //remove_menu_page( 'index.php' );                  //Dashboard
    //remove_menu_page( 'edit.php' );                   //Posts
    //remove_menu_page( 'upload.php' );                 //Media
    //remove_menu_page( 'edit.php?post_type=page' );    //Pages
    //remove_menu_page( 'themes.php' );                 //Appearance
    //remove_menu_page( 'plugins.php' );                //Plugins
    //remove_menu_page( 'users.php' );                  //Users
    //remove_menu_page( 'tools.php' );                  //Tools
    //remove_menu_page( 'options-general.php' );        //Settings

}

add_action('admin_menu', 'remove_menus');

// remove media button from tinyMCE

function RemoveAddMediaButton()
{
    remove_action('media_buttons', 'media_buttons');
}

//add_action( 'admin_head', 'RemoveAddMediaButton' );


/**
 * @desc Remove version info from head and feeds for added security
 */

add_filter('the_generator', '__return_false');

/**
 * @desc remove wp version param from any enqueued scripts apart from style.css which includes the cache busting filemtime
 */

function vc_remove_wp_ver_css_js($src)
{

    if (strpos($src, 'ver=') && !strpos($src, 'style.css') && !strpos($src, 'fontello.css') && !strpos($src, 'scripts.min.js') && !strpos($src, 'vendor.min.js')) {
        $src = remove_query_arg('ver', $src);
    }

    return $src;
}
add_filter('style_loader_src', 'vc_remove_wp_ver_css_js', 100);
add_filter('script_loader_src', 'vc_remove_wp_ver_css_js', 100);

/**
 * @desc Remove Gutenberg Block Library CSS from loading on the frontend
 */
function remove_wp_block_library_css(){

  wp_dequeue_style( 'wp-block-library' );
  wp_dequeue_style( 'wp-block-library-theme' );
  wp_dequeue_style( 'wc-block-style' ); // Remove WooCommerce block CSS

 }
 add_action( 'wp_enqueue_scripts', 'remove_wp_block_library_css', 100 );

/**
 * @desc Hide visual and text tabs to stop users switching
 */

function hide_editor_tabs()
{
    global $pagenow;
    // Only output the CSS if we're on the edit post or add new post screens.
    if (!('post.php' == $pagenow || 'post-new.php' == $pagenow)) {
        return;
    }
    ?>
  <style>
    .wp-editor-tabs {
      display: none;
    }
  </style>
  <?php
}

// add_action('admin_head', 'hide_editor_tabs');

/**
 * @desc Default to visual tab as user won\'t be able to switch
 */

function force_default_editor()
{
    return 'tinymce';
}

add_filter('wp_default_editor', 'force_default_editor');

/**
 * Remove unnecessary dashboard widgets
 *
 * @link https://www.deluxeblogtips.com/2011/01/remove-dashboard-widgets-in-wordpress.html
 */
function remove_dashboard_widgets()
{
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal'); // right now
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal'); // recent comments
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal'); // incoming links
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal'); // plugins

    remove_meta_box('dashboard_quick_press', 'dashboard', 'normal'); // quick press
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'normal'); // recent drafts
    remove_meta_box('dashboard_primary', 'dashboard', 'normal'); // wordpress blog
    remove_meta_box('dashboard_secondary', 'dashboard', 'normal'); // other wordpress news
}

add_action('admin_init', 'remove_dashboard_widgets');

/**
 * @desc Remove comments support and remove from sidebar
 */

function remove_admin_menus()
{
    remove_menu_page('edit-comments.php');
}

add_action('admin_menu', 'remove_admin_menus');

function remove_comment_support()
{
    remove_post_type_support('post', 'comments');
    remove_post_type_support('page', 'comments');
}

add_action('init', 'remove_comment_support', 100);

function my_admin_bar_render()
{
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
}

add_action('wp_before_admin_bar_render', 'my_admin_bar_render');

/**
 * @desc Remove Emojis
 */

function disable_wp_emojicons()
{
    // all actions related to emojis
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    // filter to remove TinyMCE emojis
    add_filter('tiny_mce_plugins', 'disable_emojicons_tinymce');
}

add_action('init', 'disable_wp_emojicons');

function disable_emojicons_tinymce($plugins)
{
    if (is_array($plugins)) {
        return array_diff($plugins, array('wpemoji'));
    } else {
        return array();
    }
}

add_action('init', 'disable_emojicons_tinymce');

/**
 * @desc Clean up WP Head
 */

function head_cleanup()
{
    remove_action('wp_head', 'feed_links_extra', 3);
    add_action('wp_head', 'ob_start', 1, 0);
    add_action('wp_head', function () {
        $pattern = '/.*' . preg_quote(esc_url(get_feed_link('comments_' . get_default_feed())), '/') . '.*[\r\n]+/';
        echo preg_replace($pattern, '', ob_get_clean());
    }, 3, 0);
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wp_shortlink_wp_head', 10);
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('use_default_gallery_style', '__return_false');

    global $wp_widget_factory;

    if (isset($wp_widget_factory->widgets['WP_Widget_Recent_Comments'])) {
        remove_action('wp_head', [
            $wp_widget_factory->widgets['WP_Widget_Recent_Comments'],
            'recent_comments_style',
        ]);
    }
}

add_action('init', 'head_cleanup');

/**
 * @desc  Clean up output of stylesheet <link> tags
 */
function clean_style_tag($input)
{
    preg_match_all("!<link rel='stylesheet'\s?(id='[^']+')?\s+href='(.*)' type='text/css' media='(.*)' />!", $input, $matches);
    if (empty($matches[2])) {
        return $input;
    }
    // Only display media if it is meaningful
    $media = $matches[3][0] !== '' && $matches[3][0] !== 'all' ? ' media="' . $matches[3][0] . '"' : '';

    return '<link rel="stylesheet" href="' . $matches[2][0] . '"' . $media . '>' . "\n";

}

add_filter('style_loader_tag', 'clean_style_tag');

/**
 * @desc Clean up output of <script> tags
 */
function clean_script_tag($input)
{
    $input = str_replace("type='text/javascript' ", '', $input);

    return str_replace("'", '"', $input);
}

add_filter('script_loader_tag', 'clean_script_tag');

/**
 * @desc Remove unnecessary self-closing tags
 */
function remove_self_closing_tags($input)
{
    return str_replace(' />', '>', $input);
}

add_filter('get_avatar', 'remove_self_closing_tags'); // <img />
add_filter('comment_id_fields', 'remove_self_closing_tags'); // <input />
add_filter('post_thumbnail_html', 'remove_self_closing_tags'); // <img />

/**
 * @desc Don't return the default description in the RSS feed if it hasn't been changed
 */
function remove_default_description($bloginfo)
{
    $default_tagline = 'Just another WordPress site';

    return ($bloginfo === $default_tagline) ? '' : $bloginfo;
}

add_filter('get_bloginfo_rss', 'remove_default_description');

/**
 * @desc allow editors access to menus
 */
$role_object = get_role('editor');
$role_object->add_cap('edit_theme_options');

/**
 * @desc Hide widgets
 */
function hide_menu()
{
    remove_submenu_page('themes.php', 'widgets.php'); // hide the widgets submenu
}

add_action('admin_head', 'hide_menu');

/**
 * @desc Remove the additional CSS section, introduced in 4.7, from the Customizer.
 *
 * @param $wp_customize WP_Customize_Manager
 */
function prefix_remove_css_section($wp_customize)
{
    $wp_customize->remove_section('custom_css');
}

add_action('customize_register', 'prefix_remove_css_section', 15);

/**
 * @desc Remove access to Plugin or Theme editor in WP admin
 */
define( 'DISALLOW_FILE_EDIT', true );

/**
 * @desc Disable Yoast's Schema in favour of a hardcoded version in html-header.twig
 */
add_filter( 'wpseo_json_ld_output', '__return_false' );
