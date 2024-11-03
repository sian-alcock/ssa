<?php


if ( ! class_exists( 'acf' ) ) {
	echo '<p>Advanced Custom Fields not activated. Make sure you activate the plugin in <a href="/wordpress/wp-admin/plugins.php#advanced-custom-fields-pro">/wp-admin/plugins.php</a>';
	return;
}


$context          = Timber::context();
$timber_post     = Timber::get_post();
$context['post'] = $timber_post;

$context = page_builder_add_to_context($context);

// $context['page_theme'] = get_post_theme($post->id);

if ( post_password_required( $post->ID ) ) {
  Timber::render( 'single-password.twig', $context );
} else {
  $templates = array( 'index.twig' );
  Timber::render( $templates, $context );
};


