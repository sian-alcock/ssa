<?php

function format_filesize_units($bytes) {
  if ($bytes >= 1073741824)
  {
      $bytes = number_format($bytes / 1073741824, 2) . ' GB';
  }
  elseif ($bytes >= 1048576)
  {
      $bytes = number_format($bytes / 1048576, 2) . ' MB';
  }
  elseif ($bytes >= 1024)
  {
      $bytes = number_format($bytes / 1024, 0) . ' kB';
  }
  elseif ($bytes > 1)
  {
      $bytes = $bytes . ' bytes';
  }
  elseif ($bytes == 1)
  {
      $bytes = $bytes . ' byte';
  }
  else
  {
      $bytes = '0 bytes';
  }

  return $bytes;
}

function remove_script_from_embed($html_string){
  return  preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html_string);
}

function page_builder_add_to_context($context) {
  // Add yoast breadcrumbs
  if ( !is_front_page() && function_exists('yoast_breadcrumb') ) {
    $breadcrumbs = yoast_breadcrumb("<nav aria-label='Breadcrumb'>","</nav>",false);
    $context['breadcrumbs'] = $breadcrumbs;
  }

  // Check if page builder is being used
  if (have_rows('page_modules') ) :

    $page_navigation_used = get_field('in_page_navigation');
    $text_modules_labels = array();

    while( have_rows('page_modules') ) : the_row();

      if(get_row_layout() == 'media' or get_row_layout() == 'text_media' ) :
        $embed_code = get_sub_field('embed_code');

        // We remove the script tag from flourish embed in twig so we can ensure it's only loaded once per page

        if($embed_code){
          $context['flourish_used'] = true;
        }
      endif;

      if(get_row_layout() == 'text_media' ) :
        $page_label = get_sub_field('in_page_navigation_label');
        if($page_label && $page_navigation_used){
          array_push($text_modules_labels, $page_label);
        }

      endif;

    endwhile; // page builder loop

    if(count($text_modules_labels)) :
      $context['page_navigation_used'] = true;
      $context['page_navigation'] = $text_modules_labels;
    endif;

  endif;

  return $context;

}

class AvailableTheme {
  public $name;
  public $bgColour;
  public $textColour;

   public function  __construct($name, $bgColour, $textColour) {
    $this->name = $name;
    $this->bgColour = $bgColour;
    $this->textColour = $textColour;
  }
}

class PostTheme{
  public $topic;
  public $name;
  public $bgColour;
  public $textColour;
  public $default;
}

function get_post_theme($postID) {
    if ( !isset( $postID) ) {
			throw new Exception( 'Requires a post ID' );
    }

    $terms = get_the_terms($postID, 'topic');

    $featured_topic_id = get_field('featured_topic', $postID);

    // Initially set the first as the topic to use
    // $topic_term = is_wp_error($terms) ? null : $terms[0];

    // Fix above "Error Trying to access array offset on value of type bool"
    $topic_term = '';
    if (is_wp_error($terms)) {
      $topic_term = null;
    } elseif (is_array($terms)) (
      $topic_term = $terms[0]
    );

    // If there is a featured topic chosen and is also selected as a term us that term
    if($topic_term && $featured_topic_id){
      foreach($terms as $term) {
        if($featured_topic_id == $term->term_taxonomy_id){
          $topic_term = $term;
        }
      }
    }


    $chosen_topic_colour = get_field('topic_colour', $topic_term, $postID);

    $default_theme = new AvailableTheme('teal', '#00939d', '#1c1c1c');
    $available_themes = array(
      new AvailableTheme('purple', '#5f4b8b', '#ffffff'),
      new AvailableTheme('yellow', '#FFE964', '#1c1c1c'),
      new AvailableTheme('orange', '#E6680C', '#1c1c1c'),
      new AvailableTheme('green', '#335525', '#ffffff'),
    );

    $theme_exists = null;
    $postThemeToReturn = new PostTheme();

    foreach($available_themes as $available_theme) {
        if ($available_theme->name == $chosen_topic_colour) {
            $postThemeToReturn->name          = $chosen_topic_colour;
            $postThemeToReturn->topic     = $topic_term->name;
            $postThemeToReturn->bgColour      = $available_theme->bgColour;
            $postThemeToReturn->textColour    = $available_theme->textColour;
            $postThemeToReturn->default       = false;

            $theme_exists = true;
            break;
        }
    }

    if(!$theme_exists) {
      // Default
        $postThemeToReturn->name          = $default_theme->name;
        $postThemeToReturn->topic     = null;
        $postThemeToReturn->bgColour      = $default_theme->bgColour;
        $postThemeToReturn->textColour    = $default_theme->textColour;
        $postThemeToReturn->default       = true;
    }

    return $postThemeToReturn;
}

function get_form_steps_with_eligibility($form){
  $steps = null;

  if($form){
    $steps = customStepsMarkup($form, 0, true);
  }

  return $steps;
}

function get_post_article_tpye($post) {
  $terms = get_the_terms( $post, 'category' );
  if($terms && count($terms)) {
    return $terms[0]->name;
  }
  return '';

}
