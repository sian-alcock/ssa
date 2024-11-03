<?php


add_filter( 'wpseo_breadcrumb_output', 'custom_wpseo_breadcrumb_output' );
function custom_wpseo_breadcrumb_output( $output ){

        $from = '<span>';
        $to     = '';
        $output = str_replace( $from, $to, $output );

        $from2 = '</span>';
        $to2     = '';
        $output = str_replace( $from2, $to2, $output );


    return $output;
}

add_filter('wpseo_breadcrumb_separator', 'filter_wpseo_breadcrumb_separator', 10, 1);
function filter_wpseo_breadcrumb_separator($this_options_breadcrumbs_sep) {
    return '<i class="breadcrumb-separator icon-angle-right"></i>' ;
};

// If projects of people post type then hard-code parent links
add_filter( 'wpseo_breadcrumb_links', 'unbox_yoast_seo_breadcrumb_append_link' );
 function unbox_yoast_seo_breadcrumb_append_link( $links ) {
    global $post;
    $post_type = $post->post_type;

    if($post_type == 'people'){
        $home = $links[0];
        $post_link = $links[1];
        $breadcrumb = array(
            $home,
            array(
                'url' => get_page_by_path('who-we-are') ? site_url( '/who-we-are' ) : null,
                'text' => 'Who we are',
            ), array(
                'url' => get_page_by_path('who-we-are/our-team') ? site_url( '/who-we-are/our-team' ) : null,
                'text' => 'Our team',
            ),
            $post_link
        );
        $links = $breadcrumb;
     } else if ($post_type == 'project'){
        $home = $links[0];
        $post_link = $links[1];
        $breadcrumb = array(
            $home,
            array(
                'url' => get_page_by_path('partnerships') ? site_url( '/partnerships' ) : null,
                'text' => 'Partnerships',
            ), array(
                'url' =>  get_page_by_path('partnerships/current-partnerships') ? site_url( '/partnerships/current-partnerships' ) : null,
                'text' => 'Current partnerships',
            ),
            $post_link
        );
        $links = $breadcrumb;
      } else if ($post_type == 'latest-update'){
        $home = $links[0];
        $post_link = $links[1];
        $breadcrumb = array(
            $home,
            array(
                'url' => get_page_by_path('partnerships') ? site_url( '/partnerships' ) : null,
                'text' => 'Partnerships',
            ), array(
                'url' =>  get_page_by_path('partnerships/latest-updates') ? site_url( '/partnerships/latest-updates' ) : null,
                'text' => 'Latest updates',
            ),
            $post_link
        );
        $links = $breadcrumb;
     } else if($post_type == 'post') {
        $home = $links[0];
        $post_link = $links[1];
        $article_type = get_the_terms($post->id, 'category') ? get_the_terms($post->id, 'category')[0] : null;
        $breadcrumb = array(
            $home,
            array(
                'url' => get_page_by_path('insights') ? site_url( '/insights' ) : null,
                'text' => 'Insights',
            )
        );

        if($article_type ) {
            array_push($breadcrumb, array(
                'url' => get_page_by_path('/insights/' . $article_type->slug) ? site_url( '/insights/' . $article_type->slug ) : null,
                'text' => $article_type->name,
            ));
        };
        array_push($breadcrumb, $post_link);


        $links = $breadcrumb;
     } else if ($post_type == 'job'){
      $home = $links[0];

      $post_link = $links[1];

      //remove id numbers from post title
      $post_link['text'] = preg_replace('/[0-9]+/', '', $post_link['text']);

      $breadcrumb = array(
          $home,
          array(
            'url' => get_page_by_path('who-we-are') ? site_url( '/who-we-are' ) : null,
            'text' => 'Who we are',
          ), array(
              'url' =>  get_page_by_path('who-we-are/vacancies') ? site_url( '/who-we-are/vacancies' ) : null,
              'text' => 'Vacancies',
          ),
          $post_link
      );
      $links = $breadcrumb;
    }

     return $links;
 }
