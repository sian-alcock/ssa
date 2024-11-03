<?php


/**
 * To be called via JS returns markup of next question
 *
 */
function return_next_question() {

  $nonce = $_REQUEST['nonce'];

  if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
    throw new Exception( 'Invalid Nonce' );
    wp_die();  
  }

  $question_id = isset($_REQUEST['questionId']) ? sanitize_text_field($_REQUEST['questionId']) : null;
  $question = get_fields($question_id);
  $context['question'] = $question;
  $context['question_id'] = $question_id;

  Timber::render( 'partials/question.twig', $context );
  wp_die();

}

add_action( 'wp_ajax_nopriv_return_next_question', 'return_next_question' );
add_action( 'wp_ajax_return_next_question', 'return_next_question' );
