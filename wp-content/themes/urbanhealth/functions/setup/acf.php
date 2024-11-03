<?php

	add_filter( 'acf/settings/load_json', 'my_acf_json_load_point' );

	function my_acf_json_load_point( $paths ) {

		unset( $paths[0] );

		$paths[] = get_template_directory() . '/acf-json';

		return $paths;

	}

	function my_acf_init() {

		acf_update_setting('google_api_key', '' /* update with custom map api for acf maps */);
	}

	add_action('acf/init', 'my_acf_init');

if( function_exists('acf_add_options_page') ) {
	acf_add_options_page();
}

// https://www.advancedcustomfields.com/resources/known-issues/#acf-5711
add_filter('acf/settings/remove_wp_meta_box', '__return_false');
