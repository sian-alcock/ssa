<?php

class RevisionaryCompat {
    private $saved_meta_keys = [];
    private $rest_buffer_controller = [];
    private $rest_method = '';
    private $rest_params = false;

    private $polylang_descripts = [];
    private $polylang_post_terms = [];

    function __construct() {
        if (defined('FL_BUILDER_VERSION')) {
            add_action('rvy_init', function($revisionary) {
                require_once(dirname(__FILE__).'/compat/beaver-builder.php');
                new RevisionaryBeaverBuilder();
            });
			
			add_action('fl_builder_after_save_layout', [$this, 'flt_after_save_layout'], 10, 4);
        }

        if (defined('ET_BUILDER_PLUGIN_VERSION') || (false !== stripos(get_template(), 'divi'))) {
            add_action('rvy_init', function($revisionary) {
                global $current_user;

                if ((!defined('REST_REQUEST') || ! REST_REQUEST) && !empty($current_user->ID)) {
					require_once(dirname(__FILE__).'/compat/divi.php');
                	new RevisionaryDivi($revisionary);
                }
            });
        }

        if (defined('ELEMENTOR_VERSION') && !defined('RVY_DISABLE_ELEMENTOR_INTEGRATION')) {
            require_once(dirname(__FILE__).'/compat/elementor.php');
            new RevisionaryElementor();
        }

        // WPML
        if ( defined('ICL_SITEPRESS_VERSION') ) {
            require_once(REVISIONARY_PRO_ABSPATH . '/includes-pro/compat/wpml.php');
        }

        // WooCommerce (Product Variations)
        if (class_exists('WooCommerce')) {
            require_once(dirname(__FILE__).'/compat/woocommerce.php');
            new RevisionaryWooCommerce();
        }

        // NitroPack cache
        if (defined('NITROPACK_VERSION')) {
            add_filter(
                'revisionary_create_revision_redirect',
                function($redirect) {
                    do_action('nitropack_integration_purge_all');
                    return $redirect;
                },
                5
            );

            add_action(
                'revision_applied', 
                function($post_id) {
                    if (function_exists('nitropack_sdk_invalidate')) {
                        if ($url = get_permalink($post_id)) {
                            nitropack_sdk_invalidate($url);
                        }
                    }
                }
            );
        }

        if (defined('LEARNDASH_VERSION')) {
            add_filter(
                'revisionary_use_autodraft_meta',
                function ($use_autodraft, $revision_data) {
                    $post_type = (!empty($revision_data['post_type'])) ? $revision_data['post_type'] : '';

                    if (in_array($post_type, ['sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-question', 'sfwd-certificates', 'sfwd-assignment', 'ld-exam', 'ld-achievement', 'ld-notification'])) {
                        $use_autodraft = false;
                    }

                    return $use_autodraft;
                },
                10, 2
            );
        }
        
        // Page Links To
        if (class_exists('CWS_PageLinksTo')) {
            $preview_arg = (defined('RVY_PREVIEW_ARG')) ? sanitize_key(constant('RVY_PREVIEW_ARG')) : 'rv_preview';

            if (
                (
                    (!is_admin() && (!empty($_REQUEST[$preview_arg]) || !empty($_REQUEST['preview'])) && !empty($_REQUEST['nc']))
                    || (!empty($_REQUEST['action']) && in_array(sanitize_key($_REQUEST['action']), ['create_revision', 'submit_revision', 'approve_revision', 'publish_revision']))
                ) && empty($_GET['customize_theme'])
            ) {
                add_action('template_redirect', function() {
                    $_GET['customize_theme'] = true;
                }, 9);

                add_action('template_redirect', function() {
                    unset($_GET['customize_theme']);
                }, 11);
            }

            if (!empty($_REQUEST['page']) && in_array(sanitize_key($_REQUEST['page']), ['revisionary-q'])) {
                add_action(
                    'pp_revisions_get_post_link',
                    function($post_id) {
                        global $pp_revisions_link_id;
                        $pp_revisions_link_id = $post_id;
                    }
                );

                add_filter(
                    'page_links_to_link', 
                    function ($meta_link, $post, $link) {
                        global $pp_revisions_link_id;

                        if (!empty($pp_revisions_link_id) && rvy_in_revision_workflow($pp_revisions_link_id)) {
                            return $link;
                        }

                        return $meta_link;
                    },
                    99, 3
                );
            }
        }

        if (defined('POLYLANG_VERSION')) {
            // Classic Editor
            add_action( 'load-post.php', function() {
                if (!empty($_POST['post_lang_choice'])) {
                    if ($post_id = rvy_detect_post_id()) {
                        if (rvy_in_revision_workflow($post_id)) {
                            unset($_POST['post_lang_choice']);
                        }
                    }
                }
            }, 9);

            add_action('save_post', function($post_id, $_post, $args = []) {
                $this->bufferPolyLangData($post_id);
            }, 1, 2);

            add_action('save_post', function($post_id, $_post, $args = []) {
                global $wpdb;
                
                if (rvy_in_revision_workflow($post_id)) {
                    if (!empty($this->polylang_descripts)) {
                        foreach($this->polylang_descripts as $tt_id => $descript) {
                            $wpdb->update($wpdb->term_taxonomy, ['description' => $descript], ['term_taxonomy_id' => intval($tt_id)]);
                        }
                    }

                    if (!empty($this->polylang_post_terms)) {
                        if ($published_post_id = rvy_post_id($post_id)) {
                            $term_ids = [];
                            foreach($this->polylang_post_terms as $term) {
                                $term_ids []= $term->term_id;
                            }

                            wp_set_object_terms($published_post_id, $term_ids, 'post_translations');
                        }
                    }
                }
            }, 999, 3);
        }

        // ACF: Revisions match field group page matching rule
        add_filter('acf/location/rule_match/page', [$this, 'fltACFpageMatch'], 10, 4);

        // ACF: ensure custom fields are stored to archive after pending / scheduled revision publication
        add_action('revision_applied', [$this, 'actRevisionApplied'], 20, 2);

        add_action('init', [$this, 'actInitNonceWorkaroundACF'], 20);
		add_filter('wp_revisions_to_keep', [$this, 'fltACFpreviewWorkaround'], 10, 2);

		// todo: move to admin file
        add_filter('revisionary_diff_ui', [$this, 'flt_revision_diff_ui'], 10, 4);

        add_filter('revisionary_compare_meta_fields', [$this, 'flt_compare_meta_fields']);

        // Pro
        if (class_exists('ACFE')) {
            add_action('wp_loaded', [$this, 'addACFEsupport']);
        }
		
		add_action('revisionary_copy_postmeta', [$this, 'actPodsCopyPostmeta'], 10, 3);
    }
	
	function flt_after_save_layout( $post_id, $publish, $data, $settings ) {
		if ( !empty($_REQUEST['fl_builder_data']) && !empty($_REQUEST['fl_builder_data']['publish']) && rvy_in_revision_workflow($post_id) ) {
			$post = get_post($post_id);
			
			// note: capabilities are validated downstream
			switch ($post->post_mime_type) {
				case 'draft-revision' :
					require_once(dirname(REVISIONARY_FILE).'/admin/revision-action_rvy.php');	
					rvy_revision_submit($post_id);
					break;
	
				case 'pending-revision' :
					require_once( dirname(REVISIONARY_FILE).'/admin/revision-action_rvy.php');	
					rvy_revision_approve($post_id);
					break;
			}
		}
	}

    function bufferPolylangData($post_id) {
        $this->polylang_descripts = [];
        $this->polylang_post_terms = [];

        if (rvy_in_revision_workflow($post_id)) {
            if ($published_post_id = rvy_post_id($post_id)) {
                if ($polylang_post_terms = get_transient("_rvy_polylang_terms_{$post_id}")) {
                    $this->polylang_post_terms = (array) $polylang_post_terms;
                } else {
                    $this->polylang_post_terms = wp_get_object_terms($published_post_id, 'post_translations', ['fields' => 'all']);
                    set_transient("_rvy_polylang_terms_{$post_id}", $this->polylang_post_terms, 15);
                }

                if ($polylang_descripts = get_transient("_rvy_polylang_backup_{$post_id}")) {
                    $this->polylang_descripts = (array) $polylang_descripts;
                } else {
                    foreach($this->polylang_post_terms as $term) {
                        $this->polylang_descripts["{$term->term_taxonomy_id}"] = $term->description;
                    }

                    if ($this->polylang_descripts) {
                        set_transient("_rvy_polylang_backup_{$post_id}", $this->polylang_descripts, 15);
                    }
                }
            }
        }
    }

    function fltACFpageMatch($result, $rule, $screen, $field_group) {
        global $post;

        if (empty($post) || empty($rule['operator']) || empty($rule['value'])) {
			return $result;
		}
		
		switch ($rule['operator']) {
			case '==':
				$result = (rvy_post_id($post->ID) == $rule['value']);
				break;

			case '!=':
				$result = (rvy_post_id($post->ID) != $rule['value']);
				break;

			default:
		}

		return $result;
	}

    function actInitNonceWorkaroundACF() {
        $post_id = rvy_detect_post_id();
        $this->restoreACFpreviewNonce($post_id);
    }

    function fltACFpreviewWorkaround($num, $post ) {
        $this->restoreACFpreviewNonce($post->ID);
        return $num;
    }

    private function restoreACFpreviewNonce($post_id) {
        if (!empty($_POST) && !empty($_POST['wp-preview']) && rvy_in_revision_workflow($post_id) && !defined('REVISIONARY_ACF_DEFAULT_PREVIEW_NONCE')) {
            $action = 'post';
            $user  = wp_get_current_user();
            $uid   = (int) $user->ID;
            $token = wp_get_session_token();
            $i     = wp_nonce_tick( $action );
            $nonce_val = substr( wp_hash( $i . '|' . $action . '|' . $uid . '|' . $token, 'nonce' ), -12, 10 );

            $_REQUEST['_acf_nonce'] = $nonce_val;
            $_POST['_acf_nonce'] = $nonce_val;
        }
    }

    /* --- Pro: support ACF Extended single_meta --- */
    function addACFEsupport() {
        // Pro: support ACF Extended single_meta
        if (function_exists('acf_get_setting') && acf_get_setting('acfe/modules/single_meta') && function_exists('acf_get_metadata')) {
            add_filter('revisionary_compare_meta_from', [$this, 'fltACFEcompareFrom'], 10, 2);
            add_filter('revisionary_compare_meta_to', [$this, 'fltACFEcompareTo'], 10, 2);
            add_filter('revisionary_compare_extra_fields', [$this, 'fltACFEadjustExtraFields'], 10, 2);
        }
    }

    function fltACFEcompareFrom($from_meta, $post_id) {
        $acf_from = acf_get_metadata($post_id, 'acf');
        return (is_array($acf_from)) ? array_merge($from_meta, $acf_from) : $from_meta;
    }

    function fltACFEcompareTo($to_meta, $post_id) {
        $acf_to = acf_get_metadata($post_id, 'acf');
        return (is_array($acf_to)) ? array_merge($to_meta, $acf_to) : $to_meta;
    }

    function fltACFEadjustExtraFields($extra_fields, $post_id) {
        unset($extra_fields['acf']);
        unset($extra_fields['_acf']);
        $acf_to = acf_get_metadata($post_id, 'acf');

        return array_merge($extra_fields, array_fill_keys(array_keys($acf_to), true));
    }

    function flt_revision_diff_ui($return, $compare_from, $compare_to, $args) {
        if (!is_array($args)) {
            return $return;
        }
        
        $to_meta = (isset($args['to_meta'])) ? apply_filters('revisionary_compare_meta_to', $args['to_meta'], $compare_to->ID) : [];
        $meta_fields = (isset($args['meta_fields'])) ? $args['meta_fields'] : [];
        $native_fields = (isset($args['native_fields'])) ? $args['native_fields'] : [];
        $strip_tags = (isset($args['strip_tags'])) ? $args['strip_tags'] : [];

        // Display other scalar meta fields
        $from_meta = ($compare_from) ? apply_filters('revisionary_compare_meta_from', get_post_meta($compare_from->ID), $compare_from->ID) : [];

        $extra_fields = $to_meta;

        $extra_fields = array_diff_key($extra_fields, $native_fields, $meta_fields, array_fill_keys(revisionary_unrevisioned_postmeta(), true));
        $extra_fields = apply_filters('revisionary_compare_extra_fields', array_fill_keys(array_keys($extra_fields), true), $compare_to->ID);

        $key_captions = apply_filters('revisionary_meta_key_captions', ['_yoast_wpseo_' => 'Yoast SEO ', '_thumbnail_id' => esc_html__('Featured Image', 'revisionary-pro'), ''], $compare_to);
        $caption_keys = array_keys($key_captions);
        $caption_values = array_values($key_captions);

        ksort($extra_fields);

        foreach($extra_fields as $field => $name) {
            if ($skip_meta_prefixes = apply_filters('revisionary_unrevisioned_prefixes', [], $compare_to)) {
                foreach($skip_meta_prefixes as $prefix) {
                    if (0 === strpos($field, $prefix)) {
                        continue 2;
                    }
                }
            }

            $content_to = (isset($to_meta[$field])) ? $to_meta[$field] : '';
		    $content_to = maybe_unserialize($content_to);

		    // ===== TO META =====
            if (is_array($content_to)) {
                $any_nonscalar = false;
                foreach($content_to as $k => $subval) {
				  $subval = maybe_unserialize($subval);
				  
				  if (is_array($subval) ) {
					$any_sub_nonscalar = false;
					foreach($subval as $_subval) {
						if (!is_scalar($_subval)) {						
							$any_sub_nonscalar = true;
							break;
						}
					}
					
					if (!$any_sub_nonscalar) {
						if (count($content_to) > 1 ) {
							$subval = '(' . implode(', ', $subval) . ')';
						} else {
							$subval = implode(', ', $subval);
						}
					}
				  }
					
                    if (!is_scalar($subval)) {
                        $any_nonscalar = true;
                        break;
                    }
					
				  $content_to[$k] = $subval;
                }

                if (!$any_nonscalar) {
                    $content_to = implode(', ', $content_to);
                }
            }

            if (!is_scalar($content_to)) {
                continue;
            }
		   // =======================

		   // ===== FROM META =====
            if ($compare_from) {
                $content_from = (isset($from_meta[$field])) ? $from_meta[$field] : '';
            } else {
                $content_from = '';
            }

            if (is_array($content_from)) {
                $any_nonscalar = false;
                foreach($content_from as $k => $subval) {
				  $subval = maybe_unserialize($subval);
				  
				  if (is_array($subval) ) {
					$any_sub_nonscalar = false;
					foreach($subval as $_subval) {
						if (!is_scalar($_subval)) {						
							$any_sub_nonscalar = true;
							break;
						}
					}
					
					if (!$any_sub_nonscalar) {
						if (count($content_from) > 1 ) {
							$subval = '(' . implode(', ', $subval) . ')';
						} else {
							$subval = implode(', ', $subval);
						}
					}
				  }
					
                    if (!is_scalar($subval)) {
                        $any_nonscalar = true;
                        break;
                    }
					
				  $content_from[$k] = $subval;
                }

                if (!$any_nonscalar) {
                    $content_from = implode(', ', $content_from);
                }
            }

            if (!is_scalar($content_from)) {
                continue;
            }
		   // =======================

            $args = array(
                'show_split_view' => true,
            );

            $args = apply_filters( 'revision_text_diff_options', $args, $field, $compare_from, $compare_to );

            if ($strip_tags) {
                $content_from = wp_strip_all_tags($content_from);
                $content_to = wp_strip_all_tags($content_to);
            }

            if ('_thumbnail_id' == $name) {
                $content_from = ($content_from) ? "$content_from (" . wp_get_attachment_image_url($content_from, 'full') . ')' : '';
                $content_to = ($content_to) ? "$content_to (" . wp_get_attachment_image_url($content_to, 'full') . ')' : '';
            }

            if ($name !== true) {
                // field label applied by filter
                $field_name = $name;
            } else {
            $field_name = str_replace($caption_keys, $caption_values, $field);

                if ($field_name == $field) {
                    $field_name = trim(ucwords(str_replace('_', ' ', $field)));
                }
            }

            if ($diff = wp_text_diff( $content_from, $content_to, $args )) {
                $return[] = array(
                    'id'   => $field,
                    'name' => $field_name,
                    'diff' => $diff,
                );
            }
        }

        return $return;
    }

    function actRevisionApplied($post_id, $revision) {
        if (!function_exists('acf_save_post_revision')) {
            return;
        }

        if ($_post = get_post($post_id)) {
            if (!rvy_in_revision_workflow($_post) && ('inherit' != $_post->post_status)) {
                acf_save_post_revision($post_id, $revision->ID);
            }
        }
    }

    function flt_compare_meta_fields($meta_fields) {
        $meta_fields['_requested_slug'] = esc_html__('Requested Slug', 'revisionary-pro');
        
        if (defined('FL_BUILDER_VERSION') && defined('REVISIONARY_BEAVER_BUILDER_DIFF')) {
            $meta_fields['_fl_builder_data'] = esc_html__('Beaver Builder Data', 'revisionary-pro');
            $meta_fields['_fl_builder_data_settings'] = esc_html__('Beaver Builder Settings', 'revisionary-pro');
        }
    
        if (defined('PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION')) {
            $meta_fields['ppma_authors_name'] = esc_html__('Author(s)', 'revisionary-pro');
        }

        return $meta_fields;
    }

    public function actPodsCopyPostmeta($from_post, $to_post_id, $args = []) {
        global $wpdb;

        // Also copy Pods relationship fields
		if (defined('PODS_VERSION')) {
			$pods_table = "{$wpdb->prefix}podsrel";
	
			$qry = $wpdb->prepare(
				"SELECT * FROM $pods_table WHERE item_id = %d",
				$from_post->ID
			);
	
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $pods_table WHERE item_id = %d",
					$from_post->ID
				)
			);
	
			foreach($results as $row) {
				$rel_data = array_diff_key(
					(array) $row,
					array_fill_keys(['id', 'pod_id', 'field_id', 'item_id'], true)
				);
	
				$rel_data = array_map('intval', $rel_data);
	
				$match_data = [
					'pod_id' => (int) $row->pod_id,
					'field_id' => (int) $row->field_id,
					'item_id' => (int) $to_post_id
				];
	
				if ($rel_id = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT id FROM $pods_table WHERE pod_id = %d AND field_id = %d AND item_id = %d",
							$match_data['pod_id'],
							$match_data['field_id'],
							$match_data['item_id']
						)
					)
				) {
					$wpdb->update(
						$pods_table,
						$rel_data,
						['id' => $rel_id],
						'%d',
						'%d'
					);
				} else {
					$wpdb->insert(
						$pods_table,
						array_merge($rel_data, $match_data),
						'%d'
					);
				}
            }
            
            wp_cache_flush();
        }
    }
}
