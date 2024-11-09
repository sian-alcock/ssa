<?php

// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';

// Initialize Timber.
Timber\Timber::init();



/**
 * default functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package wppt
 */


/**
 * @desc Adding Timber functions
 */

include 'functions/timber-functions.php';

/**
 * @desc Initial set up of scripts and styles
 */

include 'functions/setup/scripts.php';

/**
 * @desc Adding robots function to allow sitemap.xml to work properly across multisite
 */

include 'functions/setup/robots.php';

/**
 * @desc Clean up WordPress extras
 */
include 'functions/setup/restrictions.php';

/**
 * @desc Setup image sizes
 */
include 'functions/setup/images.php';

/**
 * @desc Setup custom posts
 */
include 'functions/setup/settings.php';


/**
 * @desc ACF Fields Config - For Child Theming
 */

include 'functions/setup/acf.php';

/**
 * @desc Admin alterations
 */

include 'functions/setup/admin_experience.php';

/**
 * @desc Adding registering of custom post types
 */

include 'functions/register/custom-post-types.php';

/**
 * @desc Adding registering of custom taxonomies
 */

include 'functions/register/custom-taxonomies.php';

/**
 * @desc Adding registering of menus
 */

include 'functions/register/menus.php';

/**
 * @desc Adding registering of options pages
 */

include 'functions/register/options-pages.php';

// /**
//  * @desc Altering Yoast SEO breadcrumbs
//  */
// include 'functions/breadcrumbs.php';

/**
 * @desc Adds endpoints to WP Rest API
 */
include 'functions/custom-endpoints.php';

/**
 * @desc Adds custom filtering
 */
include 'functions/filters.php';

/**
 * @desc Customizing gravity forms
 */

include 'functions/gravity-forms-custom-settings.php';

/**
 * @desc Function to get a person/people page for a user based on ID
 */
include 'functions/get_person_page.php';

/**
 * @desc Handle the multi-choice question component loading
 */
include 'functions/questions.php';

/**
 * @desc Custom functions
 */
include 'functions/custom-functions.php';

/**
 * @desc Youtube settings
 */
include 'functions/youtube-params.php';

/**
 * @desc Youtube settings
 */
include 'functions/setup/search.php';


