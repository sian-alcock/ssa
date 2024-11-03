<?php

/* Replace the gf progress indicator if specific form type = multistep
*/

add_filter( 'gform_progress_bar', 'custom_progress_indicator', 10, 3 );
function custom_progress_indicator( $progress_bar, $form, $confirmation_message ) {

    $current_page = GFFormDisplay::get_current_page( $form['id'] );
    $page_count = GFFormDisplay::get_max_page_number( $form );

    $progress_bar_items = '';
    for ($page = 1; $page <= $page_count; $page++) {
      $page == $current_page ? $current = ' current' : $current = '';
      $page < $page_count ? $divider = '<div class="gf-page-item__divider"></div>' : $divider = '';
      $progress_bar_items .= '<li class="gf-page-item' . $current . '">' . $page . '</li>' . $divider;
      $progress_bar = '<ul class="gf-page-item__container">' . $progress_bar_items . '</ul>';
    }

    return $progress_bar;
}

add_filter( 'gfic_enqueue_core_css', '__return_false' );


/* Enable the confirmation anchor functionality that automatically scrolls the page to the confirmation text or validation message upon form submission
@link - https://docs.gravityforms.com/gform_confirmation_anchor/
*/

add_filter( 'gform_confirmation_anchor', '__return_true' );

// Changes Gravity Forms Ajax Spinner (next, back, submit) to a transparent image
// this allows you to target the css and create a pure css spinner
add_filter( 'gform_ajax_spinner_url', 'spinner_url', 10, 2 );
function spinner_url( $image_src, $form ) {
    return  'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'; // relative to you theme images folder

}

function stepMarkup($label, $number, $page) {
  $activeClass = $number == $page ? 'c-stepper--active' : '';
  $firstClass = $number == 1 ? 'c-stepper--first' : '';
  $nextClass = $number == ($page + 1) ? 'c-stepper--next' : '';
  $completedClass = $number < $page ? 'c-stepper--completed' : '';

  $markup = '<div id="c-stepper__step_1_' . $number . '" class="c-stepper__step ' .$completedClass . ' ' . $activeClass . ' ' . $firstClass . ' ' . $nextClass .'" >';
  $markup .= '<span class="c-stepper__number">' . $number . '</span>';
  $markup .= '<span class="c-stepper__label">' . $label . '</span>';
  $markup .= '</div>';
  return $markup;
}

function customStepsMarkup($form, $page, $prependEligibility = false) {

    $pagination = $form['pagination'];
    $steps = $pagination['pages'];
    // Add our eligibility step 1st that's not actually part of the form
    if($prependEligibility){
      array_unshift($steps, 'Eligibility' );
    }

    $progress_steps = '<div id="gf_page_steps_1" class="c-stepper">';
     foreach ($steps as $key=>$value) {
        $progress_steps .= stepMarkup($value, $key + 1, $page + 1);
    }
    // $progress_steps .= '<div class="gf_step_clear"></div>';
    $progress_steps .=  '</div>';
    return $progress_steps;
}

add_filter( 'gform_progress_steps', 'progress_steps_markup', 10, 3 );
function progress_steps_markup( $progress_steps, $form, $page ) {

  // If our multistep form we want prepend with our eligibility step that lives outside gforms
  $prependEligibility = strtolower($form['title']) == 'multistep';

  $progress_steps = customStepsMarkup($form, $page, $prependEligibility);


    return $progress_steps;
}


/**
 * Ensure gravity forms (including when ajax is enabled) scripts are loaded as low as possible
 */
add_filter( 'gform_init_scripts_footer', '__return_true' );
add_filter( 'gform_cdata_open', 'wrap_gform_cdata_open', 1 );
add_filter( 'gform_cdata_close', 'wrap_gform_cdata_close', 99 );

function wrap_gform_cdata_open( $content = '' ) {
	if ( ! do_wrap_gform_cdata() ) {
		return $content;
	}
	$content = 'document.addEventListener( "DOMContentLoaded", function() { ' . $content;
	return $content;
}

function wrap_gform_cdata_close( $content = '' ) {
	if ( ! do_wrap_gform_cdata() ) {
		return $content;
	}
	$content .= ' }, false );';
	return $content;
}

function do_wrap_gform_cdata() {
	if (
		is_admin()
		|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		|| isset( $_POST['gform_ajax'] )
		|| isset( $_GET['gf_page'] ) // Admin page (eg. form preview).
		|| doing_action( 'wp_footer' )
		|| did_action( 'wp_footer' )
	) {
		return false;
	}
	return true;
}

add_action( 'gform_enqueue_scripts', 'enqueue_custom_script', 10, 2 );
function enqueue_custom_script( $form, $is_ajax ) {
    if ( $is_ajax ) {
        wp_enqueue_script( 'tracking', '/wp-content/themes/urbanhealth/assets/form/tracking.js' );
    }
}


// Remove tab index to fix issues with "Skip to contnent"
add_filter( 'gform_tabindex', '__return_false' );
