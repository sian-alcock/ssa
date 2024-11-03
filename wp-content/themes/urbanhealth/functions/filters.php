<?php

function get_authors()
{
  $argsA  = array(
    'has_published_posts' => array('post'),
    // order results by display_name
    'orderby' => 'display_name'
  );
  // Create the WP_User_Query object
  $wp_user_query = new WP_User_Query($argsA);
  // Get the results
  $authors = $wp_user_query->get_results();
  return $authors;
}


/**
 * Documented in the filtering docs at root of repository
 */
function get_listings_posts($filters = array(), $post_types = array('post'), $pre_filters = null)
{

  $paged = isset($_REQUEST['cPage']) ? sanitize_text_field($_REQUEST['cPage']) : 1;
  $context = Timber::context();

  $posts_per_page = 9;
  $args = array('posts_per_page' =>  $posts_per_page, 'paged' => $paged, 'post_type' => $post_types, 'post_status' => 'publish');

  $topic_ids = null;
  $author_ids = null;
  $article_type_ids = null;
  $location_ids = null;
  $department_ids = null;

  // If pre filters exist then add to filters and request
  if ($pre_filters) {
    $filters = array_merge($filters, $pre_filters);
    foreach ($pre_filters as $pre_filter => $filter_value) {
      $_REQUEST[$pre_filter] = $filter_value;
    }
  }

  foreach ($filters as $filter => $label) {
    if ($filter === 'topic' || $filter === 'location' || $filter === 'department') {
      // Taxonomy query
      $topic_query = isset($_REQUEST['topic']) ? sanitize_text_field($_REQUEST['topic']) : null;
      $location_query = isset($_REQUEST['location']) ? sanitize_text_field($_REQUEST['location']) : null;
      $department_query = isset($_REQUEST['department']) ? sanitize_text_field($_REQUEST['department']) : null;
      // Topic
      $topic_ids = array();
      if ($topic_query) {
        $topic_ids =  explode(",", $topic_query);
      }

      // Location
      $location_ids = array();
      if ($location_query) {
        $location_ids =  explode(",", $location_query);
      }
      // Department
      $department_ids = array();
      if ($department_query) {
        $department_ids =  explode(",", $department_query);
      }
      $tax_query = array('relation' => 'AND');
      if (count($topic_ids)) {
        $tax_query[] = array(
          array(
            'taxonomy' => 'topic',
            'field' => 'term_id',
            'terms' => $topic_ids
          )
        );
      }

      if (count($location_ids)) {
        $tax_query[] = array(
          array(
            'taxonomy' => 'location',
            'field' => 'term_id',
            'terms' => $location_ids
          )
        );
      }
      if (count($department_ids)) {
        $tax_query[] = array(
          array(
            'taxonomy' => 'department',
            'field' => 'term_id',
            'terms' => $department_ids
          )
        );
      }

      $args['tax_query'] = $tax_query;
    }
    if ($filter === 'by-author') {
      $author_query = isset($_REQUEST['by-author']) ? sanitize_text_field($_REQUEST['by-author']) : null;
      if ($author_query) {
        $author_ids =  explode(",", $author_query);
        $args['author__in'] = $author_ids;
      }
    }

    if ($filter === 'article-type') {
      // Article type
      $article_type_query = isset($_REQUEST['article-type']) ? sanitize_text_field($_REQUEST['article-type']) : null;
      $article_type_ids = array();
      if ($article_type_query) {
        $article_type_ids =  explode(",", $article_type_query);
      }
      if (count($article_type_ids)) {
        $args['category__in'] = $article_type_ids;
      }
    }
  }

  // Content types defined explicitly currently
  // $content_type_names = array();
  // if($content_type_query){
  //   $content_type_names =  explode(",", $content_type_query);
  //   $args['post_type'] = $content_type_names ;
  // }
  // Search parked for now
  // if($search_query !== null() & strlen($search_query) > 3) {
  //   $search_ids = new SWP_Query(
  //     array(
  //       's' => $search_query,
  //       'fields'=> 'ids'
  //     )
  //   );

  //   $args = array('post__in' => $search_ids->get_posts());

  // }else {
  //   $args = array('posts_per_page'=> 10, 'page' => 1);
  // }

  // $posts = Timber::get_posts($args);

  $posts = Timber::get_posts($args);

  $context['posts'] = $posts;
  $context['authors'] = get_authors();
  $context['topics'] = get_terms('topic');
  $context['article_types'] = get_terms('category');
  $context['locations'] = get_terms('location');
  $context['departments'] = get_terms('department');
  $context['active_topics'] = $topic_ids;
  $context['active_authors'] = $author_ids;
  $context['active_article_types'] = $article_type_ids;
  $context['active_locations'] = $location_ids;
  $context['post_types'] = $post_types;
  $context['active_departments'] = $department_ids;

  $context['posts_per_page'] = $posts_per_page;
  $context['paged'] = $paged;

  return $context;
}

/**
 * To be called via JS
 *
 * When called via ajax wordpress requires the post ID passed and specified to find current page
 */
function return_listings_ajax()
{
  $nonce = $_REQUEST['nonce'];

  if (!wp_verify_nonce($nonce, 'ajax-nonce')) {
    throw new Exception('Invalid Nonce');
    wp_die();
  }


  $post_id = isset($_REQUEST['postId']) ? sanitize_text_field($_REQUEST['postId']) : null;
  $filters = isset($_REQUEST['filters']) ? json_decode(stripslashes($_REQUEST['filters']), true) : null;
  $post_types = isset($_REQUEST['postTypes']) ? json_decode(stripslashes($_REQUEST['postTypes']), true) : null;
  $pre_filters = isset($_REQUEST['preFilters']) ? json_decode(stripslashes($_REQUEST['preFilters']), true) : null;
  // Is sanitized when used in get_listings_posts
  $current_query_string = isset($_REQUEST['queryString']) ? stripslashes($_REQUEST['queryString']) : null;

  $context = get_listings_posts($filters, $post_types, $pre_filters);
  $context['post'] = Timber::get_post($post_id);
  $context['ajax_current_qs'] = $current_query_string;

  Timber::render('partials/listings-loop.twig', $context);

  wp_die();
}

/**
 * When pagination is generated from a call from wp-ajax the permalink assumption is incorrect
 *
 * if $ajax_query_string is present then this has been triggered via ajax. If not then it's first load
 * and all WP context is available
 */
function get_filtered_pagination_link($page, $post_link, $ajax_query_string = null)
{
  if ($ajax_query_string) {
    $url_with_query_string = $post_link . '?' . $ajax_query_string;
  } else {
    $url_with_query_string = get_pagenum_link();
  }

  // get_pagenum_link() not working (or points to wp-admin/ajax-admin.php) if filters are cleared via ajax
  // look for 'wp-admin' in the string and set to just use the post link
  if (preg_match('/wp-admin/', $url_with_query_string, $matches)) {
    $url_with_query_string = $post_link;
  };

  $fullURL = add_query_arg(array(
    'cPage' => $page,
  ), $url_with_query_string);
  return $fullURL;
}

add_action('wp_ajax_nopriv_return_listings_ajax', 'return_listings_ajax');
add_action('wp_ajax_return_listings_ajax', 'return_listings_ajax');
