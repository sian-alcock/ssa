<?php

function head() {

	if (function_exists('is_wpe') && is_wpe()) {
		wp_register_style( 'fontello', get_stylesheet_directory_uri() . '/assets/fontello/css/fontello.css', false, filemtime( get_stylesheet_directory() .  '/assets/fontello/css/fontello.css' ) );
		wp_register_style( 'main', get_stylesheet_directory_uri() . '/assets/dist/style.css', false, filemtime( get_stylesheet_directory() . '/assets/dist/style.css' ) );
	} else {
		wp_register_style( 'fontello', get_stylesheet_directory_uri() . '/assets/fontello/css/fontello.css', false );
		wp_register_style( 'main', get_stylesheet_directory_uri() . '/assets/dist/style.css', false );
	}

	wp_enqueue_style( 'fontello' );
	wp_enqueue_style( 'main' );
}

function footer() {

	wp_deregister_script( 'wp-embed' );

	if (function_exists('is_wpe') && is_wpe()) {
		// wp_enqueue_script( 'vendor-js', get_template_directory_uri() . '/assets/dist/vendor.min.js', false, filemtime( get_template_directory() . '/assets/dist/vendor.min.js' ), true );
		wp_enqueue_script( 'main-js', get_template_directory_uri() . '/assets/dist/scripts.min.js', array(), filemtime( get_template_directory() . '/assets/dist/scripts.min.js' ), true );
	} else {
		// wp_enqueue_script( 'vendor-js', get_template_directory_uri() . '/assets/dist/vendor.min.js', false, false, true );
		wp_enqueue_script( 'main-js', get_template_directory_uri() . '/assets/dist/scripts.min.js', array(), false, true);
	}


  wp_localize_script( 'main-js', 'main_js', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce('ajax-nonce')
	));
	
}

add_action( 'wp_enqueue_scripts', 'head' );
add_action( 'wp_enqueue_scripts', 'footer' );


/**
 * @desc add defer to scripts.min.js
 */

function add_defer_attribute( $tag, $handle ) {
	if ( 'main-js' !== $handle ) {
		return $tag;
	}

	return str_replace( ' src', ' defer src', $tag );
}

add_filter( 'script_loader_tag', 'add_defer_attribute', 10, 2 );
