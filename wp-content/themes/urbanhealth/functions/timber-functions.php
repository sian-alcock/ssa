<?php


add_filter('timber/context', function ($context) {
  // $context['menu'] = Timber::get_menu('primary-menu');
  $context['navigation_primary'] = Timber::get_menu('navigation_primary');
  $context['footer_menu_1'] = Timber::get_menu('footer_menu_1');
  $context['footer_menu_2'] = Timber::get_menu('footer_menu_2');
  $context['footer_menu_3'] = Timber::get_menu('footer_menu_3');
  $context['footer_menu_terms'] = Timber::get_menu('footer_menu_terms');
  $context['options'] = get_fields('option');

  // Query the number of 'live' vacancies (to present in main menu)
  $args = array(
    'post_type' => 'job',
    'post_status' => 'publish',
  );
  $the_query = new WP_Query($args);
  $total_vacancies = $the_query->found_posts;
  $context['total_vacancies'] = $total_vacancies;
  return $context;
});



// use Timber\Twig_Function;
// class CustomTimber extends TimberSite
// {

//   function __construct() {
//     add_filter('timber_context', array($this, 'add_to_context'));
//     add_filter('get_twig', array($this, 'add_to_twig'));
//     add_filter( 'wpseo_metabox_prio', function() { return 'low';});
//     parent::__construct();
//   }

//   function add_to_context($context) {

//     if (has_nav_menu('navigation_primary')) {
//       $context['navigation_primary'] = new TimberMenu('navigation_primary');
//     }
//     if (has_nav_menu('footer_menu_1')){
//       $context['footer_menu_1'] = new TimberMenu('footer_menu_1');
//     }
//     if (has_nav_menu('footer_menu_2')){
//       $context['footer_menu_2'] = new TimberMenu('footer_menu_2');
//     }
//     if (has_nav_menu('footer_menu_3')){
//       $context['footer_menu_3'] = new TimberMenu('footer_menu_3');
//     }
//     if (has_nav_menu('footer_menu_terms')){
//       $context['footer_menu_terms'] = new TimberMenu('footer_menu_terms');
//     }
//     $context['options'] = get_fields('option');

//     // Query the number of 'live' vacancies (to present in main menu)
//     $args = array(
//       'post_type' => 'job',
//       'post_status' => 'publish',
//     );
//     $the_query = new WP_Query( $args );
//     $total_vacancies = $the_query->found_posts;

//     $context['total_vacancies'] = $total_vacancies;


//     $context['site'] = $this;
//     return $context;
//   }

//   function add_to_twig($twig) {
//     $twig->addExtension(new Twig_Extension_StringLoader());
//     $twig->addFunction('uniqId', new Twig_Function( 'uniqId', function() {
//       $theId = hrtime(true);
//       return $theId;
//     }));
//     return $twig;
//   }
// }

// new CustomTimber();
