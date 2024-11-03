<?php

function extend_robots( $robots ) {
  $robots .= "\nSitemap: " . get_site_url() . "/sitemap_index.xml";

  return $robots;
}

add_filter( 'robots_txt', 'extend_robots' );