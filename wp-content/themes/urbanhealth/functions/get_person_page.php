<?php

/* Use this function to return the url of the person page based on a userID
Note:  If more than one page exists and is published, the first one is selected
*/

function get_person_page_url($user_id) {
  $posts = get_posts(array(
      'numberposts'	=> 1,
      'post_type'		=> 'people',
      'post_status' => 'publish',
      'meta_key'		=> 'user',
      'meta_value'	=> $user_id
    ));

  if (!empty($posts)) {
  $link = get_post_permalink($posts[0]);
  } else {
    $link = '';
  }
  return $link;
}
