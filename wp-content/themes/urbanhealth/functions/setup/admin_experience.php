<?php

/**
 * Remove the default post/page editor
 */
function remove_editor() {
  remove_post_type_support('page', 'editor');
  remove_post_type_support('post', 'editor');
}
add_action('admin_init', 'remove_editor');


/**
 * Push Yoast to the bottom
 */
add_filter( 'wpseo_metabox_prio', function() { return 'low';});

// Add css file to change acf css
function my_acf_admin_enqueue_scripts() {
   wp_enqueue_script( 'admin js', get_template_directory_uri() . '/assets/admin/admin.js', array('jquery'), '1.0.0', true );
}
add_action( 'acf/input/admin_enqueue_scripts', 'my_acf_admin_enqueue_scripts' );

// Remove excerpt from default hidden meta boxes and hide categories
add_filter( 'default_hidden_meta_boxes', 'my_hidden_meta_boxes', 10, 2 );
function my_hidden_meta_boxes( $hidden, $screen ) {
    $excerpt_key = array_search('postexcerpt', $hidden);
    if ($excerpt_key !== false) {
      unset($hidden[$excerpt_key]);
    }
    array_push($hidden, 'categorydiv');
    return $hidden;
}
// Move excerpt up
add_action('add_meta_boxes', function() {
  add_meta_box('postexcerpt', __('Excerpt'), 'post_excerpt_meta_box', array('post', 'page', 'project'), 'normal', 'high');
});

/**
 * @desc Add colours to admin for acf colour choices - eg Quote module
 */

/**
 * @desc Add colours to admin for acf colour choices - eg Quote module
 */

function acf_radio_choices() {
  echo '<style>
    .acf-radio-color {
      text-align: center;
      display: inline-block;
      padding: 1em 1.5em;
      margin-left: 6px;
      margin-bottom: 15px;
      line-height: 1;
    }
    .acf-radio-color--purple {
      background-color: #5f4b8b;
      color: #FFFFFF;
    }
    .acf-radio-color--teal {
      background-color: #00939d;
      color: #1c1c1c;
    }
    .acf-radio-color--yellow {
      background-color: #FFE964;
    }
    .acf-radio-color--green {
      background-color: #335525;
      color: #FFFFFF;
    }
    .acf-radio-color--orange {
      background-color: #E6680C;
    }
    .acf-radio-color--white-cta {
      background-color: #ffffff;
      border: 2px solid #1c1c1c;
    }
  </style>';
}

add_action('admin_head', 'acf_radio_choices');

/**
 * @desc Only show menu ACF fields on the top level (menu-item-depth-0) items
 */

function acf_menu_fields() {
  echo '<style>
    .nav-menus-php .menu .acf-menu-item-fields .js-top-level{
      display: none;
    }
    .nav-menus-php .menu .menu-item-depth-0 .js-top-level {
      display: block;
    }
    .nav-menus-php .menu .acf-menu-item-fields .js-second-level{
      display: none;
    }
    .nav-menus-php .menu .menu-item-depth-1 .js-second-level {
      display: block;
    }
  </style>';
}

add_action('admin_head', 'acf_menu_fields');

/**
 * @desc Hide donation ask from plugin
 */

function acf_hide_donation() {
  echo '<style>
    #plugins-by-dreihochzwo {
      display: none;
    }
  </style>';
};

add_action('admin_head', 'acf_hide_donation');