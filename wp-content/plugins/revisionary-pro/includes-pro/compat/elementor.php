<?php
if (!empty($_SERVER['SCRIPT_FILENAME']) && basename(__FILE__) == basename(esc_url_raw($_SERVER['SCRIPT_FILENAME'])) )
	die( 'This page cannot be called directly.' );

/**
 * @package     PublishPress\Revisions\RevisionaryElementor
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2024 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */
class RevisionaryElementor
{	
    private $revision_id = 0;
    private $orig_post_id = 0;
    private $save_in_progress = false;
	private $disable_filtering = false;

    function __construct() {
        global $revisionary;

        if ( ! defined('RVY_CONTENT_ROLES') || !$revisionary->content_roles->is_direct_file_access() ) {
			add_action('template_redirect', [$this, 'act_template_redirect'], 5 ); // Execute after RevisionaryFront::act_template_redirect() in case there is an upstream redirect
		}
		
        add_filter('default_options_rvy', [$this, 'fltDefaultOptions']);
        add_filter('options_sitewide_rvy', [$this, 'fltDefaultOptionScope']);
        add_filter('revisionary_option_captions', [$this, 'fltOptionCaptions']);
        add_filter('revisionary_option_sections', [$this, 'fltOptionSections']);
        add_action('revisionary_option_ui_revision_options', [$this, 'actRevisionOptionsUI']);
        add_action('revisionary_auto_submit_setting_ui', [$this, 'actAutoSubmitSettingUI'], 10, 3);

        add_filter('revisionary_detect_id', [$this, 'elementorDetectID'], 10, 2);
        
        // Editor JS setup, scripts
        add_filter('elementor/document/config', [$this, 'fltPanelConfig']);
        add_filter('elementor/document/urls/wp_preview', [$this, 'fltWPpreview']);
        add_filter('revisionary_queue_row_actions', [$this, 'fltRevisionQueueRowActions'], 10, 2);
        add_filter('revisionary_create_revision_redirect', [$this, 'fltCreateRevisionRedirect'], 10, 2);
        add_action('wp_print_scripts', [$this, 'frontScripts']);

        // Server-side database update hooks
        add_action('elementor/widgets/widgets_registered', [$this, 'elementorMonitorQueries']);
        add_filter('revisionary_do_submission_redirect', [$this, 'elementorDisableSubmissionRedirect']);

        add_filter('posts_request', [$this, 'fltPostsRequestPastRevisions'], 10, 2);

        add_action('elementor/element/wp-page/document_settings/after_section_start', function() {
            add_filter('user_has_cap', [$this, 'fltAllowRevisionSubmission'], 10, 3);
        });

        add_action('elementor/elements/categories_registered', function() {
            add_filter('user_has_cap', [$this, 'fltAllowRevisionSubmission'], 10, 3);
        });

        add_action('elementor/document/before_save', function() {
            add_filter('user_has_cap', [$this, 'fltAllowRevisionSubmission'], 10, 3);
        });

        if (defined('WPSEO_VERSION')) { // Yoast SEO: prevent revision changes from being applied to published post without approval
            add_action('init', [$this, 'wpseoCompatWorkaround'], 11);
        }

        add_filter( "get_post_metadata", [$this, 'fltPostCSSrules'], 10, 5);

        add_action("wp_enqueue_scripts", [$this, 'actEnqueueElementorPostCSS'], 11);

        add_filter('pre_option_elementor_css_print_method', [$this, 'fltCSSprintMethod']);

        add_filter('option_rank_math_modules', [$this, 'fltRankMathModules']);


        add_action('wp_loaded', [$this, 'actSupplementRevisorCaps']);

        add_action('elementor/document/before_save', [$this, 'actFlagBuilderSave'], 10, 2);
        add_action('elementor/document/after_save', [$this, 'actUnflagBuilderSave'], 99, 2);

        if (defined('REVISIONARY_ELEMENTOR_FORCE_LIBRARY_FILTERING') && REVISIONARY_ELEMENTOR_FORCE_LIBRARY_FILTERING) {
            add_filter('revisionary_enabled_post_types', [$this, 'fltEnabledPostTypes']);
        }

        add_filter('presspermit_unfiltered_post_types', [$this, 'fltPressPermitUnfilteredPostTypes']);

        // Elementor Revision edit: action=elementor_ajax, actions={"document-29418":{"action":"get_document_config","data":{"id":29418}}}
        add_filter('user_has_cap', [$this, 'fltAllowDocumentConfig'], 999, 3);

		add_action('admin_print_footer_scripts', function() {
            if (!empty($_REQUEST['page']) && ('revisionary-q' == $_REQUEST['page'])) {
                ?>
                <style>
                .edit_with_elementor, .elementor {display:inline !important;}
                </style>
                <?php
            }
        });
		
        add_filter('elementor/editor/localize_settings', [$this, 'fltElementorEditorSettings']);
		
        // Useful hooks used in past version of Revisions:
        // 'elementor/documents/ajax_save/return_data'
        // 'elementor/editor/wp_head'
    }

    function fltElementorEditorSettings($settings) {
        $settings['autosave_interval'] = 99999999;
        return $settings;
    }

    function actFlagBuilderSave($elem_doc, $data) {
        if (!$id = $elem_doc->get_main_id()) {
            $id = true;
        }

        $this->save_in_progress = $id;

        return $data;
    }

    function actUnflagBuilderSave($elem_doc, $data) {
        global $revisionary;
		
        $this->save_in_progress = false;
		
        $post_id = $elem_doc->get_main_id();

        if ($post_status = get_post_field('post_status', $post_id)) {
            if (in_array($post_status, ['draft', 'auto-draft'])
            && !rvy_in_revision_workflow($post_id)
            ) {
                return;
            }
        }

		$revisionary->skip_filtering = true;
		
        if (!rvy_in_revision_workflow($post_id) 
		&& ('elementor_library' != get_post_field('post_type', $post_id)) 
		&& current_user_can('edit_post', $post_id)) {
			$revisionary->skip_filtering = false;
            return;
        }
		
		$revisionary->skip_filtering = false;

        $this->get_last_user_revision($post_id);
        
        if ($this->revision_id) {
            $this->restore_revision($this->revision_id);
        }
    }

    function fltEnabledPostTypes($types) {
        $types['elementor_library'] = true;

        return $types;
    }

    function fltPressPermitUnfilteredPostTypes($types) {
		$types[] = 'elementor_library';

        return $types;
	}

	function actSupplementRevisorCaps() {
        global $current_user;

        $can_edit_any = false;

        if ($types = rvy_get_manageable_types()) {
            foreach ($types as $_post_type) {
                if ($type_obj = get_post_type_object($_post_type)) {
                    if (!empty($current_user->allcaps[$type_obj->cap->edit_posts]) || (is_multisite() && is_super_admin())) {
                        $can_edit_any = true;
                        break;
                    }
                }
            }
        }

        if ($can_edit_any) {
            $current_user->allcaps['edit_elementor_libraries'] = true;
        }
    }

    function get_last_user_revision($post_id) {
        global $current_user, $wpdb;
    
        if (rvy_in_revision_workflow($post_id)) {
            return get_post($post_id);
        }

		static $busy;
		
		if (!empty($busy)) {
			return;
		}
		
		$busy = true;
		
        remove_filter('query', [$this, 'actAdjustElementorUpdateQuery']);
		
        $target_id = ('revision' == get_post_field('post_type', $post_id)) ? get_post_field('post_parent', $post_id) : $post_id;
		
		if (rvy_in_revision_workflow($target_id)) {
			$target_id = rvy_post_id($target_id);
		}
		
        $revision = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->posts WHERE (post_author = %d OR post_author = 1) AND post_status IN ('draft', 'pending')"
				. " AND post_type != 'revision'"
                . " AND post_mime_type IN ('draft-revision', 'pending-revision')"
                . " AND comment_count = %d ORDER BY ID DESC LIMIT 1",

                $current_user->ID,
                $target_id
            )
        );

		if (!$revision) {
			if ($revision && ('revision' != get_post_field('post_type', $revision->comment_count))) {
				$this->restore_revision($revision->ID);
				
                clean_post_cache($revision->ID);

			} elseif ('elementor_library' == get_post_field('post_type', $target_id)) {
                if (!empty($_REQUEST['initial_document_id']) && ((int) $_REQUEST['initial_document_id'] != $target_id)) {
                    $post_status = get_post_field('post_status', $target_id);

                    if (!$post_status || (in_array($post_status, ['draft', 'auto-draft']) && !rvy_in_revision_workflow($target_id))) {
                        return;
                    }

                    require_once( dirname(REVISIONARY_FILE).'/admin/revision-action_rvy.php');

                    if ($this->revision_id = rvy_revision_create($target_id, ['force' => true, 'suppress_redirect' => true])) {
                        $revision = get_post($this->revision_id);
                        
                        $wpdb->update($wpdb->posts, ['post_status' => 'draft', 'post_mime_type' => 'draft-revision', 'post_author' => $current_user->ID], ['ID' => $this->revision_id]);
                    }
                }
            }
        }

        add_filter('query', [$this, 'actAdjustElementorUpdateQuery']);

		$busy = false;
		
        return $revision;
    }
	
	function restore_revision($revision_id) {
        global $current_user, $wpdb;

        if ($revision = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->posts WHERE post_status IN ('draft', 'pending', 'inherit')"
                . " AND (post_author = %d OR post_author = 1)"
				. " AND ID = %d ORDER BY ID DESC LIMIT 1",

                $current_user->ID,
                $revision_id
            )
        )) {
			if ('revision' == get_post_field('post_type', $revision->comment_count)) {
				$wpdb->update($wpdb->posts, ['comment_count' => get_post_field('post_parent', $revision->comment_count)], ['ID' => $revision_id]);
			}
			
			if (!in_array($revision->post_mime_type, ['draft-revision', 'pending-revision'])) {
				if ('draft' == $revision->post_status) {
					$wpdb->update($wpdb->posts, ['post_mime_type' => 'draft-revision', 'post_author' => $current_user->ID], ['ID' => $revision_id]);
				} elseif ('pending' == $revision->post_status) {
					$wpdb->update($wpdb->posts, ['post_mime_type' => 'pending-revision', 'post_author' => $current_user->ID], ['ID' => $revision_id]);
				} elseif ('inherit' == $revision->post_status) {
					if ($revision_status = get_post_meta($revision_id, '_rvy_revision_status', true)) {
						if ($main_post_id = rvy_post_id($revision->post_parent)) {
							if ($main_post_id != $revision->ID) {
                                if ('revision' == get_post_field('post_type', $main_post_id)) {
									$main_post_id = get_post_field('post_parent', $main_post_id);
								}
								
								$post_status = str_replace('-revision', '', $revision_status);
                                
                                $wpdb->update($wpdb->posts, ['post_type' => get_post_field('post_type', $main_post_id), 'post_status' => $post_status, 'post_mime_type' => $revision_status, 'comment_count' => $main_post_id], ['ID' => $revision_id]);
                                
                                $this->revision_id = $revision_id;
                                
                                add_action('shutdown', function() {
                                    global $wpdb;
                                    $wpdb->update($wpdb->posts, ['post_mime_type' => 'pending-revision', 'post_author' => $current_user->ID], ['ID' => $this->revision_id, 'post_status' => 'pending']);
                                    $wpdb->update($wpdb->posts, ['post_mime_type' => 'draft-revision', 'post_author' => $current_user->ID], ['ID' => $this->revision_id, 'post_status' => 'draft']);
                                });
                            }
						}
					}
				}
			}
			
            clean_post_cache($revision_id);
			
			return true;
        }
		
		return false;
    }

    function fltAllowDocumentConfig($wp_blogcaps, $reqd_caps, $args) {
        global $revisionary, $wpdb, $current_user;
        
        if (!empty($_REQUEST) && !empty($_REQUEST['action']) && ('elementor_ajax' == $_REQUEST['action']) && !empty($_REQUEST['actions']) && empty($revisionary->skip_filtering)) {
            if (false !== strpos($_REQUEST['actions'], 'get_document_config')) {
				// {\"document-29418\":{\"action\":\"get_document_config\",\"data\":{\"id\":29418}}}
                $actions = (array) json_decode(stripslashes($_REQUEST['actions']));
                if ($this_action = reset($actions)) {
                    if (is_object($this_action) && !empty($this_action->data) && !empty($this_action->data->id)) {
                        $request_id = $this_action->data->id;

						if (isset($args[0]) && ('edit_theme_options' == $args[0])) {
							$wp_blogcaps['edit_theme_options'] = true;
						}
						
						if (isset($args[0]) && ('edit_elementor_libraries' == $args[0])) {
							if ('elementor_library' == get_post_field('post_type', $request_id)) {

								// ('builder' == get_post_meta($request_id, '_elementor_edit_mode', true))
								if (('page' == get_post_meta($request_id, '_elementor_template_type', true))
								) {
                                    if (!empty($_REQUEST['initial_document_id'])) {
                                        $post_status = get_post_field('post_status', (int) $_REQUEST['initial_document_id']);

                                        if (in_array($post_status, ['draft', 'auto-draft'])
                                        && !rvy_in_revision_workflow((int) $_REQUEST['initial_document_id'])
                                        ) {
                                            return $wp_blogcaps;
                                        }
                                    }

                                    $revision = $this->get_last_user_revision($request_id);

                                    if (!$revision) {
										$target_id = ('revision' == get_post_field('post_type', $request_id)) ? get_post_field('post_parent', $request_id) : $request_id;
										
                                        if ('elementor_library' == get_post_field('post_type', $target_id)) {
                                            if (!empty($_REQUEST['initial_document_id']) && ((int) $_REQUEST['initial_document_id'] != $target_id)) {
                                                $post_status = get_post_field('post_status', $target_id);

                                                if (!$post_status || (in_array($post_status, ['draft', 'auto-draft']) && !rvy_in_revision_workflow($target_id))) {
                                                    return $wp_blogcaps;
                                                }
                                                
                                                require_once( dirname(REVISIONARY_FILE).'/admin/revision-action_rvy.php');
                                                $this->revision_id = rvy_revision_create($target_id, ['force' => true, 'suppress_redirect' => true]);

                                                $wpdb->update($wpdb->posts, ['post_status' => 'draft', 'post_mime_type' => 'draft-revision', 'post_author' => $current_user->ID], ['ID' => $this->revision_id]);
                                                add_post_meta($this->revision_id, '_rvy_revision_status', 'draft-revision');
                                                clean_post_cache($this->revision_id);
                                            }
                                        }
                                    } else {
										$this->revision_id = $revision->ID;
										$this->restore_revision($revision->ID);
                                    }

									$wp_blogcaps['edit_elementor_libraries'] = true;
								}
							}
						}
						
						if (isset($args[0]) && ('edit_post' == $args[0]) && !empty($args[2]) && ($args[2] == $request_id)) {
							$post_type = get_post_field('post_type', $request_id);
							
							if ('elementor_library' == get_post_field('post_type', $request_id)) {
								$wp_blogcaps = array_merge($wp_blogcaps, array_fill_keys($reqd_caps, true));
							}
						}
                    }
                }
            }

            if (false !== strpos($_REQUEST['actions'], 'save_builder')) {
                $request_id = rvy_detect_post_id();

				if (isset($args[0]) && ('edit_elementor_libraries' == $args[0])) {
					if ('elementor_library' == get_post_field('post_type', $request_id)) {
						$wp_blogcaps['edit_elementor_libraries'] = true;
					}
				}
				
				if (isset($args[0]) && ('edit_post' == $args[0])) {
					$post_type = get_post_field('post_type', $request_id);

					if ('elementor_library' == get_post_field('post_type', $request_id)) {
						$wp_blogcaps = array_merge($wp_blogcaps, array_fill_keys($reqd_caps, true));
					}
				}
            }
        }

        return $wp_blogcaps;
    }

    function fltDefaultOptions($options) {
        $options['elementor_revision_ensure_css_file'] = 1;
        $options['elementor_auto_submit_templates'] = 1;
        $options['elementor_auto_submit_template_notify'] = 0;
        $options['elementor_update_template_revision_notify'] = 0;
        $options['elementor_submission_redirect'] = 1;

        return $options;
    }

    function fltDefaultOptionScope($options) {
        $options['elementor_revision_ensure_css_file'] = true;
        $options['elementor_auto_submit_templates'] = true;
        $options['elementor_auto_submit_template_notify'] = true;
        $options['elementor_update_template_revision_notify'] = true;
        $options['elementor_submission_redirect'] = true;
        
        return $options;
    }

    function fltOptionCaptions($captions) {
        $captions['elementor_revision_ensure_css_file'] = __('Elementor: Generate CSS file for revision if needed', 'revisionary-pro');
        $captions['elementor_auto_submit_templates'] = __('Elementor: Auto-submit Template Revisions', 'revisionary-pro');
        $captions['elementor_auto_submit_template_notify'] = __('Elementor: Send notifications for auto-submitted Template Revisions', 'revisionary-pro');
        $captions['elementor_update_template_revision_notify'] = __('Elementor: Send notifications when a Template Revision is updated', 'revisionary-pro');
        $captions['elementor_submission_redirect'] = __('Elementor: Redirect to preview after editing a revision', 'revisionary-pro');

        return $captions;
    }

    function fltOptionSections($sections) {
        $sections['features']['options'][] = 'elementor_revision_ensure_css_file';
        $sections['features']['working_copy'][] = 'elementor_auto_submit_templates';
        $sections['features']['working_copy'][] = 'elementor_auto_submit_template_notify';
        $sections['features']['working_copy'][] = 'elementor_update_template_revision_notify';
        $sections['features']['working_copy'][] = 'elementor_submission_redirect';

        return $sections;
    }

    function actRevisionOptionsUI($settings_ui) {
        echo "<br />";

        if ('external' !== get_option('elementor_css_print_method')) {
            $hint = esc_html__('NOTE: To also regenerate CSS files for published revisions, enable Elementor > Settings > Advanced > CSS Print Method > External.', 'revisionary-pro');
        } else {
            $hint = esc_html__('Disable this setting if there is any problem viewing or editing revisions on the front end.', 'revisionary-pro');
        }

		$settings_ui->option_checkbox('elementor_revision_ensure_css_file', 'features', 'options', $hint, '');
    }

    function actAutoSubmitSettingUI($settings_ui, $tab, $section) {
        echo '<br />';
        $hint = '';
        $settings_ui->option_checkbox('elementor_auto_submit_templates', $tab, $section, $hint, '');

        $hint = '';
        $settings_ui->option_checkbox('elementor_auto_submit_template_notify', $tab, $section, $hint, '');
        
        $hint = '';
		$settings_ui->option_checkbox('elementor_update_template_revision_notify', $tab, $section, $hint, '');

        echo '<br />';
        $hint = __('Redirect to preview if the revision is updated at least 15 seconds after loading the Elementor editor. This redirect relies on detection of UI messaging elements. Disable it here if it is triggered improperly', 'revisionary-pro');
        $settings_ui->option_checkbox('elementor_submission_redirect', $tab, $section, $hint, '');
    }

    function fltRankMathModules($option_val) {
        // Rank Math SEO's Link Counter module interferes with revision submission in Elementor
        if (!empty($_REQUEST['action']) && ('elementor_ajax' == $_REQUEST['action'])
        && (!empty($_REQUEST['editor_post_id']) && rvy_in_revision_workflow($_REQUEST['editor_post_id']))
        ) {
            $option_val = array_diff((array) $option_val, ['link-counter']);
        }

        return $option_val;
    }

    function act_template_redirect() {
        global $post;

        if ( is_admin() ) {
            return;
        }

        if (defined('FL_BUILDER_VERSION') && isset($_REQUEST['fl_builder'])) {
            return;
        }

        if (!class_exists('Elementor\Core\Files\CSS\Post') || !rvy_get_option('elementor_revision_ensure_css_file')) {
            return;
        }

        if (!empty($_REQUEST['page_id'])) {
            $post_id = (int) $_REQUEST['page_id'];
        } elseif (!empty($_REQUEST['p'])) {
            $post_id = (int) $_REQUEST['p'];
        } else {
            if (!empty($post)) {
                $post_id = $post->ID;
            } else {
                $post_id = 0;
            }
        }

        if (!$post_id) {
            $post_id = rvy_detect_post_id();
        }

        if (!$post_id) {
            return;
        }

        if (rvy_in_revision_workflow($post_id)) {
            $source_post_id = rvy_post_id($post_id);

        } elseif ('revision' == get_post_field('post_type', $post_id)) {
            $source_post_id = get_post_field('post_type', $post_id);

        } elseif (!defined('REVISIONARY_EXTERNAL_CSS_REVISION_ONLY')) {
            $source_post_id = $post_id;
            
        } else {
            return;
        }

        try {
            $uploads = wp_upload_dir();

            $uploads_path = (!empty($uploads['baseurl'])) ? $uploads['baseurl'] : WP_CONTENT_URL . "/uploads";

            if (!empty($uploads['baseurl'])) {
                $filepath = trailingslashit($uploads_path) . "elementor/css/post-{$source_post_id}.css";
                if (!file_exists($filepath)) {
                    $elem_post = new \Elementor\Core\Files\CSS\Post($source_post_id);

                    if (!empty($elem_post->get_content()) || defined('REVISIONARY_ELEMENTOR_DELETE_OBSOLETE_REVISION_CSS')) {
                        $elem_post->update();
                        $elem_post->enqueue();
                    }
                }

                
                // If CSS file exists for published post but not revision, copy contents from published post CSS, replacing post ID
                if ($source_post_id != $post_id) {
                    $revision_filepath = trailingslashit($uploads_path) . "elementor/css/post-{$post_id}.css";
                    if (!file_exists($revision_filepath) && file_exists($filepath)) {
                        $css_content = file_get_contents($filepath);

                        $revision_css_content = str_replace("elementor-{$source_post_id}", "elementor-{$post_id}", $css_content);

                        file_put_contents($revision_filepath, $revision_css_content);
                    }
                }

                if (defined('REVISIONARY_DEBUG') && !empty($_REQUEST['css_file_check'])) {
                    if (file_exists($filepath)) {
                        echo $filepath . ': <br /><br />';
                        $test = file_get_contents($filepath);
                        echo $test;
                        die('test');
                    } else {
                        echo $filepath . ' does not exist.';
                    }
                }
            }
        } catch (Exception $ex) {
            return;
        }
    }

    private function isRevisionPreview() {
        $preview_arg = (defined('RVY_PREVIEW_ARG')) ? sanitize_key(constant('RVY_PREVIEW_ARG')) : 'rv_preview';

        if (!is_admin() && (!defined('REST_REQUEST') || ! REST_REQUEST) && (((!empty($_GET[$preview_arg]) || !empty($_GET['preview']) || !empty($_GET['_ppp'])) && empty($_REQUEST['preview_id'])) || !empty($_GET['mark_current_revision']))) { // preview_id indicates a regular preview via WP core, based on autosave revision
            if (!empty($_REQUEST['page_id'])) {
                $revision_id = (int) $_REQUEST['page_id'];
            } elseif (!empty($_REQUEST['p'])) {
                $revision_id = (int) $_REQUEST['p'];
            } else {
                global $post;

                if (!empty($post)) {
                    $revision_id = $post->ID;
                } else {
                    $revision_id = 0;
                }
            }

            if (rvy_in_revision_workflow($revision_id)) {
                return $revision_id;
            }
        }

        return false;
    }

    function fltCSSprintMethod($option_val) {
        if ($this->isRevisionPreview()) {
            return 'external';
        }

        return $option_val;
    }

    function actEnqueueElementorPostCSS() { 
        if ($revision_id = $this->isRevisionPreview()) {
            $uploads = wp_upload_dir();

            $uploads_path = (!empty($uploads['baseurl'])) ? $uploads['baseurl'] : WP_CONTENT_URL . "/uploads";

            // This may be required for the file creation to be triggered
            if (!defined('REVISIONARY_ELEMENTOR_REVISION_CSS_NO_EXTRA_ENQUEUE')) {
                wp_enqueue_style('revisionary-elementor-post-css-extra', trailingslashit($uploads_path) . "elementor/css/post-{$revision_id}.css", []);
            }

            // This is required for correct CSS file loading
            wp_enqueue_style('revisionary-elementor-post-css', trailingslashit($uploads_path) . "elementor/css/post-{$revision_id}.css", [], null);
        }
    }

    function fltPostCSSrules($rules, $object_id, $meta_key, $single, $meta_type) {
        if (('_elementor_css' == $meta_key) && rvy_in_revision_workflow($object_id)) {
            $parent_post_id = rvy_post_id($object_id);
            
            if (!$parent_post_id || ($parent_post_id == $object_id)) {
                return $rules;
            }

            global $wpdb;
            $_rules = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_elementor_css' && post_id = %d",
                    $object_id
                )
            );

            if ($_rules) {
                $rules = str_replace("elementor-$parent_post_id", "elementor-$object_id", $_rules);
            }
        }

        return $rules;
    }
    
    function wpseoCompatWorkaround() {
        global $wp_filter;

        if (!empty($_REQUEST['editor_post_id']) && !empty($_REQUEST['action']) && ('elementor_ajax' == $_REQUEST['action'])) {
            foreach(['wp_insert_post', 'delete_post'] as $action) {
                foreach(array_keys($wp_filter[$action]->callbacks) as $priority) {
                    foreach(array_keys($wp_filter[$action]->callbacks[$priority]) as $wpseo_filter_key) {
                        if (false !== strpos($wpseo_filter_key, 'build_indexable')) {
                            unset($wp_filter[$action]->callbacks[$priority][$wpseo_filter_key]);
                        }
                    }
                }
            }
        }
    }

    function fltAllowRevisionSubmission($wp_blogcaps, $reqd_caps, $args) {
        global $current_user;
        
        $check_caps = [];

        foreach(get_post_types(['public' => true], 'object') as $type_obj) {
            if (!empty($type_obj->cap->publish_posts) 
            && !empty($current_user->allcaps[$type_obj->cap->edit_posts])
            ) {
                $check_caps[$type_obj->cap->publish_posts] = true;
            }
        }

        if ($check_caps) {
            $wp_blogcaps = array_merge($wp_blogcaps, array_intersect_key($check_caps, array_fill_keys($reqd_caps, true)));

            // unused Elementor action: 'elementor/document/before_save'

            remove_filter('user_has_cap', [$this, 'fltAllowRevisionSubmission'], 10, 3);
        }

        return $wp_blogcaps;
    }

    function fltCreateRevisionRedirect($url, $post_id) {
        if (!empty($_REQUEST['front']) && !defined('PP_REVISIONS_ELEMENTOR_NO_REDIRECT')) {
            $post_type = get_post_field('post_type', $post_id);
            
            if (apply_filters(
                'revisionary_elementor_create_revision_redirect',
                !defined('LEARNDASH_VERSION') || !in_array($post_type, ['sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-question', 'sfwd-certificates', 'sfwd-assignment', 'ld-exam', 'ld-achievement', 'ld-notification']),
                $post_id
            )) {
            	$url = admin_url("post.php?post=$post_id&action=elementor");
            }
        }

        return $url;
    }

    function elementorDetectID($id, $args) {
        $args = (array) $args;

        if (!empty($args['is_ajax']) && !empty($_REQUEST['action']) && ('elementor_ajax' == $_REQUEST['action']) && !empty($_REQUEST['editor_post_id']) && did_action('elementor/db/before_save')) {
            if (!empty($_REQUEST['actions'])) {
                $requests = json_decode( stripslashes(sanitize_text_field($_REQUEST['actions'])), true );

                if (!empty($requests['save_builder'])) {
                    $id = (int) $_REQUEST['editor_post_id'];
                }
            }
        }

        return $id;
    }

    function fltPanelConfig($config) {
        $post_id = rvy_detect_post_id();
        
        if (!$revision_status = rvy_in_revision_workflow($post_id)) {
            if (!empty($_REQUEST['initial_document_id'])) {
                $post_id = $_REQUEST['initial_document_id'];
                $revision_status = rvy_in_revision_workflow($post_id);
            }
        }

        if ($revision_status = rvy_in_revision_workflow($post_id)) {
            if ('draft-revision' == $revision_status) {
                $config['panel']['messages']['publish_notification'] = pp_revisions_status_label('pending-revision', 'submitted');
            } elseif (current_user_can('edit_post', rvy_post_id($post_id)) && ('future-revision' != $revision_status)) {
                $config['panel']['messages']['publish_notification'] = esc_html__('Approval done.', 'revisionary-pro');
            } else {
                $config['panel']['messages']['publish_notification'] = esc_html__('Revision updated.', 'revisionary-pro');
            }

            // @todo: generic revisions top bar ?
            $config['urls']['preview'] = add_query_arg('rvy_embed', 1, add_query_arg('elementor-preview', $post_id, rvy_preview_url($post_id)));

            $config['urls']['wp_preview'] = rvy_preview_url($post_id);
            $config['urls']['permalink'] = rvy_preview_url($post_id);
            $config['urls']['have_a_look'] = rvy_preview_url($post_id);
        }

        return $config;
    }

    function fltWPpreview($url) {
        if ($post_id = rvy_detect_post_id()) {
            if (rvy_in_revision_workflow($post_id)) {
                $url = rvy_preview_url($post_id);
            }
        }

        return $url;
    }

    function fltRevisionQueueRowActions($actions, $post) {
        $actions['elementor'] = sprintf(
                '<a href="%1$s" class="" title="%2$s" aria-label="%2$s">%3$s</a>',
                rvy_admin_url("post.php?post=$post->ID&action=elementor"),
                esc_html__('Elementor', 'revisionary-pro'),
                esc_html__('Elementor', 'revisionary-pro')
        );

        return $actions;
    }

    // @todo: migrate to .js file with localize_script()
    function frontScripts() {
        if ($post_id = rvy_detect_post_id()) {
            if ($revision_status = rvy_in_revision_workflow($post_id)) {
                $can_publish = current_user_can('edit_post', rvy_post_id($post_id));
                
                switch ($revision_status) {
                    case 'draft-revision' :
                        if (current_user_can('set_revision_pending-revision', $post_id)) {
                            $label_caption = __('Submit', 'revisionary-pro');
                        } else {
                            $label_caption = __('Update', 'revisionary-pro');
                        }

                        break;

                    case 'pending-revision' :
                        if ($can_publish) {
                            $post = get_post($post_id);
                            $label_caption = ( strtotime( $post->post_date_gmt ) > agp_time_gmt() ) ? __('Approve', 'revisionary-pro') : __('Publish', 'revisionary-pro');
                        } else {
                            $label_caption = __('Update', 'revisionary-pro');
                        }

                        break;

                    case 'future-revision' :
                        $label_caption = __('Update', 'revisionary-pro');
                        break;

                    case 'default' :
                        $label_caption = __('Update', 'revisionary-pro');
                        break;
                }
                ?>

                <script type="text/javascript">
                /* <![CDATA[ */
                var ppRevisionsPublishCaption = '<?php echo esc_html($label_caption);?>';

                <?php /* Label Publish button for next Revision workflow progression */ ?>
                var rvyIntLabelPublishButton = setInterval(function() {
                    var publishButtonLabel = document.getElementById("elementor-panel-saver-button-publish-label");

                    if (publishButtonLabel !== null) {
                        publishButtonLabel.innerHTML = ppRevisionsPublishCaption;

                        <?php /* Also enable submit / approval without changes */?>
                        var publishButton = document.getElementById("elementor-panel-saver-button-publish");

                        if (publishButton !== null) {
                            if (publishButton.classList.contains("elementor-button-success") && publishButton.classList.contains("elementor-disabled")) {
                                publishButton.classList.remove("elementor-disabled");
                            }
                        }
                    }
                }, 100);

                <?php /* Redirect to Revisions preview screen after revision status change */
                
                if (get_option('rvy_elementor_submission_redirect', true)):
                    $redirect_delay_msec = (defined('REVISIONARY_ELEMENTOR_REDIRECT_DELAY_MSEC')) ? REVISIONARY_ELEMENTOR_REDIRECT_DELAY_MSEC : 15000;
                ?>

                var rvyIntDetectLoad = setInterval(function() {
                    var elementorPublish = document.getElementById("elementor-panel-footer-settings");
                    if (elementorPublish !== null) {
                        clearInterval(rvyIntDetectLoad);

                        // Delay save detection to avoid triggering on autosave indicator
                        setTimeout(function() {
                            var rvyIntDetectStatusChange = setInterval(function() {

                                var elementorToast = document.getElementById("elementor-toast");
                                if (elementorToast !== null) {

                                    var toastStyle = elementorToast.getAttribute("style");
                                    if (toastStyle !== null) {
                                        if (-1 == toastStyle.indexOf('display: none')) {
                                            clearInterval(rvyIntDetectStatusChange);

                                            setTimeout(function() {
                                                window.location = '<?php echo add_query_arg('base_post', rvy_post_id($post_id), rvy_preview_url($post_id));?>';
                                            }, 500);
                                        }
                                    }
                                }
                            }, 500);
                        }, <?php echo $redirect_delay_msec;?>);
                    }
                }, 500);

                <?php endif;?>

                /* ]]> */
                </script>
                <?php

                // Elementor disables the iframe links, so hide them
                if (!empty($_REQUEST['rvy_embed'])):?>
                    <style>
                    div.rvy_view_revision a.rvy_preview_linkspan, #pp_revisions_top_bar div.rvy_preview_msgspan a {display: none;}
                    </style>
                <?php endif;
            }
        }
    }

    function elementorMonitorQueries() {
        add_filter('query', [$this, 'actAdjustElementorUpdateQuery']);
    }

    function actAdjustElementorUpdateQuery($qry) {
        global $revisionary, $wpdb, $current_user;

        if ($this->save_in_progress) {
			if (0 === strpos($qry, 'UPDATE')) {
                // WHERE `ID` = 32507
                $matches = [];

                if (preg_match("/\s*WHERE\s*`ID`\s*=\s*([0-9]+)/", $qry, $matches)) {
                    if ($matches[1]) {
						if (rvy_in_revision_workflow($matches[1])) {
							$revision = get_post($matches[1]);
						} else {
							$revisionary->skip_filtering = true;
							
                            if (current_user_can('edit_post', $matches[1])
                            //&& (empty($_REQUEST['post']) || !rvy_in_revision_workflow($_REQUEST['post']))
                            && (empty($_REQUEST['initial_document_id']) || empty($_REQUEST['editor_post_id']) 
                            || ($_REQUEST['initial_document_id'] == $matches[1])
                            || !rvy_in_revision_workflow($_REQUEST['initial_document_id'])
                            || ('elementor_library' != get_post_field('post_type', $matches[1]))
                            )
                            ) {
                                return $qry;
                            }
							
                            if (!empty($_REQUEST['initial_document_id'])) {
                                $post_status = get_post_field('post_status', (int) $_REQUEST['initial_document_id']);

                                if (in_array($post_status, ['draft', 'auto-draft'])
                                && !rvy_in_revision_workflow((int) $_REQUEST['initial_document_id'])
                                ) {
                                    return $qry;
                                }
                            }

							$revisionary->skip_filtering = false;

                            $revision = $this->get_last_user_revision($matches[1]);
						}
						
						if ($revision) {
                            $revision_id = $revision->ID;
                            $this->revision_id = $revision->ID;
							
                            $qry = str_replace(
                                " WHERE `ID` = {$matches[1]}", 
                                " WHERE `ID` = {$revision->ID}",
                                $qry
                            );
							
							$qry = str_replace(", `post_mime_type` = ''", '', $qry);
							$qry = str_replace(", `post_status` = 'publish'", '', $qry);
													
                            if ('pending-revision' != $revision->post_mime_type) {
                                if (('elementor_library' != $revision->post_type) || rvy_get_option('elementor_auto_submit_templates')) {
                                    $qry = str_replace(
                                        "UPDATE `$wpdb->posts` SET ",
                                        "UPDATE `$wpdb->posts` SET `post_status` = 'pending', `post_mime_type` = 'pending-revision', ",
                                        $qry
                                    );

                                    if (rvy_get_option('elementor_auto_submit_template_notify')) {
                                        $this->revision_id = $revision->ID;
                                        add_action('elementor/document/after_save', [$this, 'actSubmitPendingRevision'], 10, 2);
                                    }
                                }
                            } else {
								if (('elementor_library' != $revision->post_type) || rvy_get_option('elementor_auto_submit_templates')) {
									$this->revision_id = $revision->ID;
									add_action('elementor/document/after_save', [$this, 'actUpdatePendingRevision'], 10, 2);
								}
							}
							
							add_action('elementor/document/after_save', [$this, 'actMirrorElementorDataToPostContent'], 5, 2);
                        }
                    }
                }
				
                // WHERE `post_id` = 32507 AND `meta_key` = '_elementor_data'
                $matches = [];
                if (preg_match("/ WHERE `post_id` = ([0-9]+) AND `meta_key` = '_elementor_data'/", $qry, $matches)) {
				if ($matches[1]) {
                    $revisionary->skip_filtering = true;
					
					if (!rvy_in_revision_workflow($matches[1]) 
					//&& ('elementor_library' != get_post_field('post_type', $matches[1])) 
                    && current_user_can('edit_post', $matches[1])
                    && (empty($_REQUEST['initial_document_id']) || empty($_REQUEST['editor_post_id']) 
	                || ($_REQUEST['initial_document_id'] == $matches[1])
	                || !rvy_in_revision_workflow($_REQUEST['initial_document_id'])
	                || ('elementor_library' != get_post_field('post_type', $matches[1]))
	                )
                    ) {
                        $revisionary->skip_filtering = false;
						
						return $qry;
                    }
					
					$revisionary->skip_filtering = false;

                    if (!empty($_REQUEST['initial_document_id'])) {
                        $post_status = get_post_field('post_status', (int) $_REQUEST['initial_document_id']);

                        if (in_array($post_status, ['draft', 'auto-draft'])
                        && !rvy_in_revision_workflow((int) $_REQUEST['initial_document_id'])
                        ) {
                            return $qry;
                        }
                    }

					if ($revision = $this->get_last_user_revision($matches[1])) {
                            $qry = str_replace(
                                " WHERE `post_id` = {$matches[1]} AND `meta_key` = '_elementor_data'", 
							    " WHERE `post_id` = {$revision->ID} AND `meta_key` = '_elementor_data'",
                                $qry
                            );

                            $revision_id = $revision->ID;
							$this->revision_id = $revision->ID;
                        }
                    }
                }
			}
        } 

        if (0 === strpos($qry, 'UPDATE ') && strpos($qry, "`post_status` = '")) {
            if (!empty($_REQUEST['actions'])) {
                $actions = sanitize_text_field($_REQUEST['actions']);
                
                if (strpos($actions, '"action\":\"save_builder\"')
                && (strpos($actions, '"data\":{\"status\":\"publish\"') || strpos($actions, '"data\":{\"status\":\"future\"'))
                ) {
                    if (empty($revision_id)) {
                        $post_id = (isset($_REQUEST['editor_post_id'])) ? (int) $_REQUEST['editor_post_id'] : 0;
                    } else {
                        $post_id = $revision_id;
                    }
					
					$this->revision_id = $post_id;

                    if ($revision_status = rvy_in_revision_workflow($post_id)) {
                        switch($revision_status) {
                            case 'draft-revision':
                                $qry = str_replace("`post_mime_type` = 'draft-revision'", "`post_mime_type` = 'pending-revision'", $qry);
                                $qry = str_replace("`post_status` = 'draft'", "`post_mime_type` = 'pending'", $qry);
                                $qry = str_replace("`post_status` = 'publish'", "`post_mime_type` = 'pending'", $qry);
								$qry = str_replace("`post_author` = 1'", "`post_author` = " . intval($current_user->ID), $qry);

								update_post_meta($post_id, '_rvy_revision_status', 'pending-revision');

                                $this->revision_id = $post_id;
                                add_action('elementor/document/after_save', [$this, 'actSubmitPendingRevision'], 10, 2);

                                clean_post_cache($this->revision_id);

                                break;

                            case 'pending-revision':
                                $qry = str_replace("`post_status` = 'publish'", "`post_mime_type` = 'pending'", $qry); // don't allow the revision itself to be set published
								$qry = str_replace("`post_author` = 1'", "`post_author` = " . intval($current_user->ID), $qry);
								
                                $this->revision_id = $post_id;
                                add_action('elementor/document/after_save', [$this, 'actApprovePendingRevision'], 10, 2);
                                break;

                            case 'future-revision':
                                break;

                            default:
                        }
                    }
                }
            }
        }

        return $qry;
    }

	function actMirrorElementorDataToPostContent($elem_doc, $data) {
		global $wpdb;

		if (class_exists('\Elementor\Plugin') && rvy_in_revision_workflow($this->revision_id)) {
			if ($elem = \Elementor\Plugin::instance()) {
				if ($post_content = $elem->db->get_plain_text($this->revision_id)) {
					$wpdb->update($wpdb->posts, ['post_content' => $post_content], ['ID' => $this->revision_id]);
				}
			}
		}
	}
	
    function actApprovePendingRevision($elem_doc, $data) {
		global $revisionary;
		
		$revisionary->skip_filtering = true;
		
        if (current_user_can('edit_post', rvy_post_id($this->revision_id))) {
            require_once(dirname(REVISIONARY_FILE) . '/admin/revision-action_rvy.php');	
            rvy_revision_approve($this->revision_id, ['force_notify' => true]);
			delete_post_meta($this->revision_id, '_rvy_revision_status');
        }
		
		$revisionary->skip_filtering = false;
    }

    function actSubmitPendingRevision($elem_doc, $data) {
        require_once(dirname(REVISIONARY_FILE).'/revision-workflow_rvy.php');
        $rvy_workflow_ui = new Rvy_Revision_Workflow_UI();
        $_post = get_post(rvy_post_id($this->revision_id));

        $args = ['revision_id' => $this->revision_id, 'published_post' => $_post, 'object_type' => $_post->post_type];
        $rvy_workflow_ui->do_notifications('pending-revision', 'pending-revision', (array) $_post, $args);
		
		clean_post_cache($this->revision_id);
    }

    function actUpdatePendingRevision($elem_doc, $data) {
		require_once(dirname(REVISIONARY_FILE).'/revision-workflow_rvy.php');
        $rvy_workflow_ui = new Rvy_Revision_Workflow_UI();
        $_post = get_post(rvy_post_id($this->revision_id));

		if (rvy_get_option('revision_update_notifications') && !defined('REVISIONARY_ELEMENTOR_NO_UPDATE_NOTIFICATIONS')) {
        	$args = ['update' => true, 'revision_id' => $this->revision_id, 'published_post' => $_post, 'object_type' => $_post->post_type];
        	$rvy_workflow_ui->do_notifications('pending-revision', 'pending-revision', (array) $_post, $args);
    	}
    }

    function actPublishScheduledRevision($elem_doc, $data) {
        require_once( dirname(REVISIONARY_FILE).'/admin/revision-action_rvy.php');	
        rvy_revision_publish($this->revision_id);
    }

    // Stop Elementor from blocking front end display (even outside Elementor ) of past revisions to capable users
    function fltPostsRequestPastRevisions($request, $query_obj) {
        global $wpdb;
        static $busy;

        if (!empty($busy)) {
            return $request;
        }

        $busy = true;

        if (!is_admin() && (!defined('REST_REQUEST') || ! REST_REQUEST)) {
            $is_revision_query = strpos($request, "post_type = 'revision'");

            if (!$this->orig_post_id  && !empty($query_obj->query_vars['p'])) {
                if (('revision' == get_post_field('post_type', $query_obj->query_vars['p'])) && current_user_can('edit_post', $query_obj->query_vars['p'])) {
                    $this->orig_post_id = (int) $query_obj->query_vars['p'];
                }
            }

            if ($this->orig_post_id) {
                $this->orig_post_id = (int) $this->orig_post_id;
                $request = str_replace("ID = {$this->orig_post_id} AND $wpdb->posts.post_type IN ('post', 'page'", "ID = {$this->orig_post_id} AND $wpdb->posts.post_type IN ('post', 'page', 'revision'", $request);
            }
        }

        $busy = false;
        return $request;
    }

    // @todo: Is this hook still needed for scheduled revision submission?
    function elementorDisableSubmissionRedirect($redirect) {
        if (defined('DOING_AJAX') && DOING_AJAX && !empty($_REQUEST['action']) && ('elementor_ajax' == $_REQUEST['action']) && !empty($_REQUEST['editor_post_id']) && did_action('elementor/db/before_save')) {
            $redirect = false;
        }

        return $redirect;
    }
}
