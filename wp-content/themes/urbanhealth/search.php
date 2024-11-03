<?php

if ( ! class_exists( 'acf' ) ) {
	echo '<p>Advanced Custom Fields not activated. Make sure you activate the plugin in <a href="/wordpress/wp-admin/plugins.php#advanced-custom-fields-pro">/wp-admin/plugins.php</a>';
	return;
}


$context = Timber::context();

// Add yoast breadcrumbs
if ( !is_front_page() && function_exists('yoast_breadcrumb') ) {
	$breadcrumbs = yoast_breadcrumb("<nav aria-label='Breadcrumb'>","</nav>",false);
	$context['breadcrumbs'] = $breadcrumbs;
}
$context['posts'] = Timber::get_posts();
$context['title'] = 'Search results for '. get_search_query();
$context['posts_per_page'] = get_option('posts_per_page');
$context['paged'] = get_query_var('paged') ? get_query_var('paged') : 1;
$context['query_terms'] = isset($_GET["s"]) ? htmlspecialchars($_GET["s"]) : null;
$context['order_terms'] = isset($_GET["order"]) ? htmlspecialchars($_GET["order"]) : null;

$templates = array( 'search.twig' );
Timber::render( $templates, $context );