<?php
//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //
//  This file creates any custom post types we need for our theme. Get started
//  by un-commenting everything below, and customizing the cpt to suit.
//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //
function create_prop_post_types()
{
  $labels = array(
    'name'                  => _x( 'Projects', 'Post Type General Name', 'text_domain' ),
    'singular_name'         => _x( 'Project', 'Post Type Singular Name', 'text_domain' ),
    'menu_name'             => __( 'Projects', 'text_domain' ),
    'name_admin_bar'        => __( 'Projects', 'text_domain' ),
    'archives'              => __( 'Item Archives', 'text_domain' ),
    'parent_item_colon'     => __( 'Parent Project:', 'text_domain' ),
    'all_items'             => __( 'All Project', 'text_domain' ),
    'add_new_item'          => __( 'Add New Project', 'text_domain' ),
    'add_new'               => __( 'Add New Project', 'text_domain' ),
    'new_item'              => __( 'New Project', 'text_domain' ),
    'edit_item'             => __( 'Edit Project', 'text_domain' ),
    'update_item'           => __( 'Update Project', 'text_domain' ),
    'view_item'             => __( 'View Project', 'text_domain' ),
    'search_items'          => __( 'Search Project', 'text_domain' ),
    'not_found'             => __( 'Not found', 'text_domain' ),
    'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
    'featured_image'        => __( 'Project Featured Image', 'text_domain' ),
    'set_featured_image'    => __( 'Set Project Featured image', 'text_domain' ),
    'remove_featured_image' => __( 'Remove Project Featured image', 'text_domain' ),
    'use_featured_image'    => __( 'Use as Project Featured image', 'text_domain' ),
    'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
    'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
    'items_list'            => __( 'Project list', 'text_domain' ),
    'items_list_navigation' => __( 'Project list navigation', 'text_domain' ),
    'filter_items_list'     => __( 'Filter Project list', 'text_domain' ),
  );
  $args = array(
    'label'                 => __( 'Project', 'text_domain' ),
    'description'           => __( 'Project', 'text_domain' ),
    'labels'                => $labels,
    'supports'              => array('thumbnail', 'title', 'revisions', 'excerpt'),
    'taxonomies'            => array( ),
    'hierarchical'          => false,
    'public'                => true,
    'show_ui'               => true,
    'show_in_menu'          => true,
    'menu_position'         => 25,
    'menu_icon'             => 'dashicons-hammer',
    'show_in_admin_bar'     => true,
    'show_in_nav_menus'     => true,
    'can_export'            => true,
    'has_archive'           => false,
    'exclude_from_search'   => false,
    'publicly_queryable'    => true,
    'capability_type'       => 'post',
  );

  register_post_type( 'project', $args );

      //  Register any additional CPTs here  //
    flush_rewrite_rules();
}
add_action( 'init', 'create_prop_post_types' );
