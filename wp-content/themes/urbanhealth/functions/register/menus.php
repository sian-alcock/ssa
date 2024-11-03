<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Register navigation menus for our theme.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

function register_prop_menus()
{

    register_nav_menus(

        array(
            'navigation_primary' => __('Primary Navigation'),
            'footer_menu_1' => __( 'Footer menu 1' ),
            'footer_menu_2' => __( 'Footer menu 2' ),
            'footer_menu_3' => __( 'Footer menu 3' ),    
            'footer_menu_terms' => __( 'Footer menu terms' ),    
        )
    );

}

add_action('init', 'register_prop_menus');
