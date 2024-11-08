<?php

/**
 * Template Name: Projects listings
 *
 * Automatically shows the projects listings below any page builder modules
 */



if (!class_exists('acf')) {
  echo '<p>Advanced Custom Fields not activated. Make sure you activate the plugin in <a href="/wordpress/wp-admin/plugins.php#advanced-custom-fields-pro">/wp-admin/plugins.php</a>';
  return;
}
$filters = array("topic" => 'Topic', "location" => 'Location');

$context = get_listings_posts($filters, array('project'));
$context = page_builder_add_to_context($context);
$post = Timber::get_post();
$context['post'] = $post;
$context['page_theme'] = get_post_theme($post->id);
$context['filters'] = $filters;
$context['title'] = get_field('listings_heading', $post->id);
Timber::render('listing.twig', $context);;
