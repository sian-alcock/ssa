<?php

class RevisionaryPro {
    private static $instance = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new RevisionaryPro();
        }

        return self::$instance;
    }

    private function __construct() {
        add_filter('default_options_rvy', [$this, 'fltDefaultOptions']);
        add_filter('options_sitewide_rvy', [$this, 'fltDefaultOptionScope']);
        add_filter('wp_revisions_to_keep', [$this, 'fltMaybeSkipRevisionCreation'], 10, 2);

        add_filter('revisionary_main_post_statuses', [$this, 'fltMainPostStatuses'], 5, 2);
        add_filter('revisionary_preview_compare_view_caption', [$this, 'fltPreviewCompareViewCaption'], 10, 2);
        add_filter('revisionary_preview_view_caption', [$this, 'fltPreviewCompareViewCaption'], 10, 2);

        add_action('revisionary_front_init', [$this, 'loadACFtaxonomyPreviewFilters']);

        add_filter('revisionary_apply_revision_fields', [$this, 'fltApplyRevisionFields'], 10, 4);
    }

    function fltApplyRevisionFields($update_fields, $revision, $published, $actual_revision_status) {
        if ($published_status = get_post_status_object($published->post_status)) {
            if (empty($published_status->public) && empty($published_status->private) && (('future-revision' == $actual_revision_status) || rvy_get_option('publish_by_revision'))) {
                $update_fields['post_status'] = 'publish';
            }
        }

        return $update_fields;
    }

    function deleteSubposts($parent_post_id, $subpost_type, $args = []) {
        global $wpdb;

        $keep_clause = (!empty($args['keep_ids'])) ? "AND ID NOT IN ('" . implode("','", $args['keep_ids']) . "')" : '';

        $subposts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d $keep_clause",
                $subpost_type,
                $parent_post_id
            )
        );

        if (!$subposts) {
            return;
        }

        foreach ($subposts as $subpost) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $wpdb->postmeta WHERE post_id = %d",
                    $subpost->ID
                )
            );

            wp_delete_post($subpost->ID);
        }
    }

    function copySubposts($source_parent_id, $target_parent_id, $subpost_type, $args=[]) {
        global $wpdb;

        $subposts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_parent = %d",
                $subpost_type,
                $source_parent_id
            )
        );

        if (!$subposts) {
            return [];
        }

        $copied_ids = [];

        $source_is_revision = rvy_in_revision_workflow($source_parent_id) || ('inherit' == get_post_field('post_status', $source_parent_id));

        foreach ($subposts as $subpost) {
			if (empty($subpost)) {
				continue;
			}
			
            $data = array_intersect_key(
                (array) $subpost, 
                array_fill_keys( 
                    ['post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'comment_status', 'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count'], 
                    true
                )
            );

            $data['post_parent'] = $target_parent_id;

            $target_subpost_id = 0;

            if ($source_is_revision) {
                if ($subpost_original_source_id = get_post_meta($subpost->ID, '_rvy_subpost_original_source_id', true)) {
                    if ($target_subpost = get_post($subpost_original_source_id)) {
                        if ($target_subpost->post_name == $subpost->post_name) {
                            $target_subpost_id = $target_subpost->ID;
                        }
                    }
                }
            }

            if (!empty($target_subpost_id)) {
                $wpdb->update($wpdb->posts, $data, ['ID' => $target_subpost_id]);
            } else {
                $wpdb->insert($wpdb->posts, $data);
                $target_subpost_id = (int)$wpdb->insert_id;
            }

            if ($target_subpost_id) {
                $copied_ids [$subpost->ID]= $target_subpost_id;

                revisionary_copy_postmeta($subpost->ID, $target_subpost_id, ['apply_deletions' => true]);

                if (!$source_is_revision) {
                    update_post_meta($target_subpost_id, '_rvy_subpost_original_source_id', $subpost->ID);
                }
            }
        }

        return $copied_ids;
    }

    function fltDefaultOptions($options) {
        $options['pending_revision_unpublished'] = 0;
        $options['publish_by_revision'] = 0;
        $options['prevent_rest_revisions'] = 0;
        return $options;
    }

    function fltDefaultOptionScope($options) {
        $options['pending_revision_unpublished'] = true;
        $options['publish_by_revision'] = true;
        $options['prevent_rest_revisions'] = true;
        return $options;
    }

    function fltMaybeSkipRevisionCreation($num, $post) {	
		if (class_exists('ACF') && rvy_get_option('prevent_rest_revisions')) {	
			$arr_url = wp_parse_url(get_option('siteurl'));
			
			if ($arr_url && isset($arr_url['path'])) {
				if (!empty($_SERVER['REQUEST_URI'])) {
                    if (0 === strpos(esc_url_raw($_SERVER['REQUEST_URI']), $arr_url['path'] . '/wp-json/wp/')) {
                        $num = 0;
                    }
                }
			}
		}

		return $num;
    }
    
    // @todo: Are these ACF filters still needed with Revisions 3.0 submission mechanism?
    
    function loadACFtaxonomyPreviewFilters() {
        // Some ACF implementations cause the current revision (post_status = 'inherit') to be loaded as queried object prior to taxonomy field value retrieval
		// However, don't force revision_id elsewhere because main post / current revision ID seems to be required for some other template rendering. 
		add_filter("acf/load_value", [$this, 'fltACFenablePostFilter'], 1);
		add_filter("acf/load_value", [$this, 'fltACFdisablePostFilter'], 9999);
    }

    public function fltACFenablePostFilter($val) {
		add_filter("acf/decode_post_id", [$this, 'fltACFdecodePostID'], 10, 2);
		return $val;
	}

	public function fltACFdisablePostFilter($val) {
		remove_filter("acf/decode_post_id", [$this, 'fltACFdecodePostID'], 10, 2);
		return $val;
	}

    public function fltACFdecodePostID($args, $post_id) {
        if ($args["type"] != "option") {
            $args['id'] = rvy_detect_post_id();
        }

        return $args;
    }

    function fltPreviewCompareViewCaption($caption, $revision) {
        $status_obj = get_post_status_object(get_post_field('post_status', rvy_post_id($revision->ID)));
        
        if ($status_obj && (empty($status_obj->public) && empty($status_obj->private))) {
            $caption = esc_html__("%sCompare%s%sView Current Draft%s", 'revisionary-pro');
        }

        $caption = str_replace( ' ', '&nbsp;', $caption);

        return $caption;
    }

    function fltPreviewViewCaption($caption, $revision) {

        $status_obj = get_post_status_object(get_post_field('post_status', rvy_post_id($revision->ID)));
        
        if ($status_obj && (empty($status_obj->public) && empty($status_obj->private))) {
            $caption = esc_html__("%sView Current Draft%s", 'revisionary-pro');
        }

        $caption = str_replace( ' ', '&nbsp;', $caption);

        return $caption;
    }

    function fltMainPostStatuses($statuses, $return = 'object') {
        if (rvy_get_option('pending_revision_unpublished')) {
            $statuses = get_post_stati( ['internal' => false], $return );
        }

        return $statuses;
    }
}
