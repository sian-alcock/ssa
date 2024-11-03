<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This file creates any custom taxonomies we need for our theme. Get started
//  by un-commenting everything below, and customizing the taxonomy to suit.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

function create_custom_taxonomies() {
		$labels = array(
			'name'              => _x( 'Topics', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Topic', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Topics', 'textdomain' ),
			'all_items'         => __( 'All Topics', 'textdomain' ),
			'parent_item'       => null,
			'parent_item_colon' => null,
			'edit_item'         => __( 'Edit Topic', 'textdomain' ),
			'update_item'       => __( 'Update Topic', 'textdomain' ),
			'add_new_item'      => __( 'Add New Topic', 'textdomain' ),
			'new_item_name'     => __( 'New Topic Name', 'textdomain' ),
			'menu_name'         => __( 'Topics', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'meta_box_cb' 			=> false,
			'public' 			      => false,
			'rewrite'           => array( 'slug' => 'topic' ),
		);
		register_taxonomy( 'topic', array('post','page', 'project', 'latest-update'), $args );

		$labels = array(
			'name'              => _x( 'Article Types', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Article Type', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Article Types', 'textdomain' ),
			'all_items'         => __( 'All Article Types', 'textdomain' ),
			'parent_item'       => null,
			'parent_item_colon' => null,
			'edit_item'         => __( 'Edit Article Type', 'textdomain' ),
			'update_item'       => __( 'Update Article Type', 'textdomain' ),
			'add_new_item'      => __( 'Add New Article Type', 'textdomain' ),
			'new_item_name'     => __( 'New Article Type Name', 'textdomain' ),
			'menu_name'         => __( 'Article Types', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => false,
			'meta_box_cb'       => false,
			'rewrite'           => false,
			'public'						=> false
		);

		register_taxonomy( 'category', array('post', 'latest-update'), $args );

		$labels = array(
			'name'              => _x( 'Article Types - old', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Article Type', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Article Types - old', 'textdomain' ),
			'all_items'         => __( 'All Article Types - old', 'textdomain' ),
			'parent_item'       => null,
			'parent_item_colon' => null,
			'edit_item'         => __( 'Edit Article Type', 'textdomain' ),
			'update_item'       => __( 'Update Article Type', 'textdomain' ),
			'add_new_item'      => __( 'Add New Article Type', 'textdomain' ),
			'new_item_name'     => __( 'New Article Type Name', 'textdomain' ),
			'menu_name'         => __( 'Article Types - old', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => false,
			'meta_box_cb'       => false,
			'rewrite'           => false,
			'public'            => false
		);

		register_taxonomy( 'article-type', array('post', 'latest-update'), $args );

		$labels = array(
			'name'              => _x( 'Locations', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Location', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Locations', 'textdomain' ),
			'all_items'         => __( 'All Locations', 'textdomain' ),
			'parent_item'       => null,
			'parent_item_colon' => null,
			'edit_item'         => __( 'Edit Location', 'textdomain' ),
			'update_item'       => __( 'Update Location', 'textdomain' ),
			'add_new_item'      => __( 'Add New Location', 'textdomain' ),
			'new_item_name'     => __( 'New Location Name', 'textdomain' ),
			'menu_name'         => __( 'Locations', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'meta_box_cb' 			=> false,
			'public' 			      => false,
			'rewrite'           => array( 'slug' => 'location' ),
		);
		register_taxonomy( 'location', array('project'), $args );


		$labels = array(
			'name'              => _x( 'Departments', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Department', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Departments', 'textdomain' ),
			'all_items'         => __( 'All Departments', 'textdomain' ),
			'parent_item'       => null,
			'parent_item_colon' => null,
			'edit_item'         => __( 'Edit Department', 'textdomain' ),
			'update_item'       => __( 'Update Department', 'textdomain' ),
			'add_new_item'      => __( 'Add New Department', 'textdomain' ),
			'new_item_name'     => __( 'New Department Name', 'textdomain' ),
			'menu_name'         => __( 'Departments', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'meta_box_cb' 			=> false,
			'public' 			      => false,
			'rewrite'           => array( 'slug' => 'department' ),
		);
		register_taxonomy( 'department', array('people', 'job'), $args );

	flush_rewrite_rules();
}

add_action( 'init', 'create_custom_taxonomies' );
