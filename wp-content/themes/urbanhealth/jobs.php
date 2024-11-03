<?php

/**
 * Template Name: Jobs listings
 *
 * Automatically shows the job listings below any page builder modules
 */

if (!class_exists('Timber')) {
  echo 'Timber not activated. Make sure you activate the plugin in <a href="/wordpress/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
  return;
}

if (!class_exists('acf')) {
  echo '<p>Advanced Custom Fields not activated. Make sure you activate the plugin in <a href="/wordpress/wp-admin/plugins.php#advanced-custom-fields-pro">/wp-admin/plugins.php</a>';
  return;
}

$filters = array("department" => 'Department');
$context = get_listings_posts($filters, array('job'));
$context = page_builder_add_to_context($context);



$post =  Timber::get_post();
$context['post'] = $post;


$context['page_theme'] = get_post_theme($post->id);
$context['filters'] = $filters;
$context['title'] = get_field('listings_heading', $post->id);
Timber::render('listing.twig', $context);
