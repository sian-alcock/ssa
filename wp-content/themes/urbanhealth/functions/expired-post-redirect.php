<?php

/**
 * @desc Urban Health uses plugin called Post Expirator to 'expire' posts on a specific date
 * Function below adds a redirect to expired posts to the root url if post is custom post type (based on rewrite slug)
 * And redirects regular posts/pages to Home page
 *
 *
 * CODE COMMENTED OUT FOR NOW AS NOT WORKING FOR USERS WHO ARE NOT LOGGED IN - REMOVED LINK FROM FUNCTIONS.PHP
 */

add_action( 'template_redirect', 'post_redirect_after_expiry', 10, 1 );
function post_redirect_after_expiry() {

  global $post;

  $args = array(
    'posts_per_page'   => -1,
    'post_type'   => 'any',
    'post_status' => array( 'draft', 'publish' ),
  );

  $our_posts = new WP_Query( $args );
  $url = $_SERVER['REQUEST_URI'];
  $path = parse_url($url, PHP_URL_PATH);
  $path_fragments = explode('/', rtrim($path, '/'));
  $end_url = end($path_fragments);

  if ( $our_posts->have_posts() ) :
    while( $our_posts->have_posts() ) :
      $our_posts->the_post();
      if (($end_url === $post->post_name) && $post->post_status === 'draft') :

        $has_expiry_date = get_post_meta($post->ID, '_expiration-date', true);
        $expired_date_is_in_the_past = $has_expiry_date < strtotime("now");
        $post_type = get_post_type( $post->ID );

        if($post_type != 'post' or $post_type != 'page'):
          $rewrite_slug = get_post_type_object( $post_type )->rewrite['slug'];
          $link = get_site_url() . '/' . $rewrite_slug;
        endif;

        if ( $post_type == 'post' or $post_type == 'page' ) :
          $link = get_site_url();
        endif;

        if(($has_expiry_date && $expired_date_is_in_the_past) ):
          wp_redirect(esc_url($link), 301);
          exit;
        endif;

      endif;
    endwhile;
    wp_reset_query();
  endif;

}
