<?php // Add query to youtube urls to remove information
function addYoutubeQuery( $string ) {
  $iframe = $string;
  // use preg_match to find iframe src
  preg_match('/src="(.+?)"/', $iframe, $matches);

  $src = $matches[1];

  // add extra params to iframe src
  $params = array(
    'rel'         => 0,
    'iv_load_policy' => 3,
    'modestbranding' => 1
  );

  $new_src = add_query_arg($params, $src);

  $iframe = str_replace($src, $new_src, $iframe);

  return $iframe;
}
?>
