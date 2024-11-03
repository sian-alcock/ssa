<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This file creates any custom taxonomies we need for our theme. Get started
//  by un-commenting everything below, and customizing the taxonomy to suit.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

function create_custom_taxonomies() {
	
	flush_rewrite_rules();
}

add_action( 'init', 'create_custom_taxonomies' );
