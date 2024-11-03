<?php

/**
 * Template Name: Latest updates listings
 *
 * Automatically shows the latest updates listings below any page builder modules
 */

if (!class_exists('acf')) {
  echo '<p>Advanced Custom Fields not activated. Make sure you activate the plugin in <a href="/wordpress/wp-admin/plugins.php#advanced-custom-fields-pro">/wp-admin/plugins.php</a>';
  return;
}
$pre_filter_article_type = get_field('pre_filter_article_type');
if ($pre_filter_article_type) {
  $pre_filters = array('article-type' => $pre_filter_article_type);
  // If article type is prefiltered then we don't want that filter option
  $filters = array("topic" => 'Topic', "author" => 'Author');
} else {
  $pre_filters = null;
  $filters = array("topic" => 'Topic', "article-type" => "Article Type", "author" => 'Author');
}

$context = get_listings_posts($filters, array('latest-update'), $pre_filters);
$context = page_builder_add_to_context($context);
$post =  Timber::get_post();
$context['post'] = $post;
$context['page_theme'] = get_post_theme($post->id);
$context['filters'] = $filters;
$context['pre_filters'] = $pre_filters;


Timber::render('listing.twig', $context);
