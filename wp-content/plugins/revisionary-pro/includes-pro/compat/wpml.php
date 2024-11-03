<?php
if (!empty($_SERVER['SCRIPT_FILENAME']) && basename(__FILE__) == basename(esc_url_raw($_SERVER['SCRIPT_FILENAME'])) )
	die();

$script_name = (!empty($_SERVER['REQUEST_URI']) && !is_null($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';

if (!strpos($script_name, 'p-admin/edit.php') 
&& !strpos($script_name, 'p-admin/plugins.php') 
&& !strpos($script_name, 'p-admin/plugin-install.php') 
&& !strpos($script_name, 'p-admin/plugin-editor.php') 
) {
	// TODO: further limit URIs?
	add_filter('posts_request', array('RevisionaryWPML', 'flt_wpml_posts_request'), 50 );
	add_filter('query', array('RevisionaryWPML', 'flt_wpml_query'), 50 );
	add_filter('revisionary_queue_vars', ['RevisionaryWPML', 'fltDisableLangFilter']);

	add_action('pre_get_posts', ['RevisionaryWPML', 'actParseQuery'], 50);
}

if (isset($_SERVER['REQUEST_URI']) && !empty($_REQUEST['action'])) {
	if (strpos(esc_url_raw($_SERVER['REQUEST_URI']), 'p-admin/revision.php') || (strpos(esc_url_raw($_SERVER['REQUEST_URI']), 'p-admin/admin-ajax.php') && ('get-revision-diffs' == $_REQUEST['action']))) {
		add_action('wpml_before_init', ['RevisionaryWPML', 'actCompareRevsNoLangFilter']);
		add_filter('revisionary_compare_vars', ['RevisionaryWPML', 'fltDisableLangFilter']);
	}
}

if (defined('WPML_TM_VERSION')) {
	require_once(dirname(__FILE__).'/wpml-translation-management.php');
	new RevisionaryWPMLTM();
}

class RevisionaryWPML {
	public static function actParseQuery(WP_Query $_query) {
		$preview_arg = (defined('RVY_PREVIEW_ARG')) ? sanitize_key(constant('RVY_PREVIEW_ARG')) : 'rv_preview';

		if (!is_admin() && (!empty($_REQUEST[$preview_arg]) || !empty($_GET['preview'])) && (!defined('REST_REQUEST') || ! REST_REQUEST) && (!defined('DOING_AJAX') || ! DOING_AJAX)) {
			if (isset($_REQUEST['page_id'])) {
				$post_id = (int) $_REQUEST['page_id'];
			} else {
				$post_id = rvy_detect_post_id();
			}

			if ($post = get_post($post_id)) {
				if (rvy_in_revision_workflow($post)) {
					$_query->query['suppress_wpml_where_and_join_filter'] = true;
				}
			}
		}
	}

	public static function actCompareRevsNoLangFilter() {
		$_GET['lang'] = 'all';
	}

	public static function fltDisableLangFilter($query_vars) {
		$query_vars['suppress_wpml_where_and_join_filter'] = true;
		return $query_vars;
	}

	public static function flt_wpml_posts_request($query) {
		global $wpdb;
		
		// Require current user to be a site-wide editor due to complexity of applying scoped roles to revisions
		if ( strpos($query, "FROM $wpdb->posts") && ( strpos($query, "'draft-revision'") || strpos($query, "'pending-revision'") || strpos($query, "'future-revision'") ) ) {
			
			if ( strpos($query, 'ELECT') ) {
				$wpml_post_types = array_diff_key( apply_filters( 'wpml_translatable_documents', array() ), array( 'revision' => true ) );
				$wpml_post_types = array_keys( $wpml_post_types );
				
				$icl_translations = $wpdb->prefix . 'icl_translations';
				$wpml_join_match = "/LEFT\sJOIN\s+$icl_translations\s+wpml_translations\s+ON\s+$wpdb->posts.ID\s+=\s+wpml_translations.element_id\s+AND\s+wpml_translations.element_type\s+=\s+CONCAT\('post_', $wpdb->posts.post_type\)/";
				
				if ( preg_match(
					$wpml_join_match,
					$query 
					) 
				){
					$post_type = (!empty($_REQUEST['post_type'])) ? 'post_' . sanitize_key($_REQUEST['post_type']) : 'post_post';
					
					$wpml_join = $wpdb->prepare( "LEFT JOIN $icl_translations wpml_translations ON $wpdb->posts.ID = wpml_translations.element_id AND wpml_translations.element_type = %s", $post_type );
				
					$parent_join = '';
					foreach ( $wpml_post_types as $type ) {
						$parent_join .= " LEFT JOIN $icl_translations wpml_translations_parent_{$type} ON $wpdb->posts.comment_count = wpml_translations_parent_{$type}.element_id AND wpml_translations_parent_{$type}.element_type = 'post_{$type}'";
					}
					
					$query = preg_replace( $wpml_join_match, "$wpml_join $parent_join", $query );
					
					$parent_where = '(';
					foreach ( $wpml_post_types as $type ) {
						$type = sanitize_key($type);
						$parent_where .= " wpml_translations_parent_{$type}.language_code = '$1' OR";
					}
					$parent_where .= ' 0 )';
					
					$query = preg_replace( 
						"/wpml_translations.language_code\s+=\s+'([a-z]+)'/", 
						"wpml_translations.language_code = '$1' OR ( $wpdb->posts.post_mime_type IN ('draft-revision', 'pending-revision', 'future-revision') AND $parent_where )",
						$query
					);	
				}
				
			} // endif SELECT query
		}
		
		return $query;
	}

	/*
	 * Filter WPML Query:
	 *	SELECT language_code, COUNT(p.ID) AS c
	 *	FROM wp_icl_translations t
	 *	JOIN wp_posts p
	 * 		ON t.element_id=p.ID
	 *			AND t.element_type = CONCAT('post_', p.post_type)
	 * 	WHERE p.post_type='page'  AND post_status <> 'trash' AND post_status <> 'auto-draft'  AND t.language_code IN ('en','fr','de','es','all')
	 *		GROUP BY language_code
	 */
	public static function flt_wpml_query($query) {
		global $wpdb;

		if ( strpos($query, "ELECT language_code, COUNT(p.ID)" ) && ! empty($_REQUEST['post_type']) ) {
			$query = str_replace( "ON t.element_id=p.ID", "ON (t.element_id=p.ID OR (t.element_id=p.comment_count AND p.post_mime_type IN ('draft-revision', 'pending-revision','future-revision')))", $query );
		} // endif query pertains in any way to pending status and/or revisions

		return $query;
	}
}
