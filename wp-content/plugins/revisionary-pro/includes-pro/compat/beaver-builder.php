<?php
if (!empty($_SERVER['SCRIPT_FILENAME']) && basename(__FILE__) == basename(esc_url_raw($_SERVER['SCRIPT_FILENAME'])) )
	die( 'This page cannot be called directly.' );
	
/**
 * @package     PublishPress\Revisions\RevisionaryBeaverBuilder
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2024 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */
class RevisionaryBeaverBuilder
{		
	// minimal config retrieval to support pre-init usage by WP_Scoped_User before text domain is loaded
	function __construct() {
		add_action('wp_print_scripts', [$this, 'submissionRedirect']);

		add_filter('fl_builder_ui_bar_review', [$this, 'flt_publish_caption']);
		add_filter('fl_builder_ui_bar_publish', [$this, 'flt_publish_caption']);
		
		add_filter('fl_get_edit_url', [$this, 'flt_edit_url'], 10, 2);

		add_filter('page_link', [$this, 'fltPermalink'], 10, 2);
		add_filter('post_type_link', [$this, 'fltPermalink'], 10, 2);

		add_action('revisionary_queue_row_actions', [$this, 'actRevisionQueueRowActions'], 10, 2);

		add_filter('revisionary_admin_bar_absolute', [$this, 'fltAdminBarAbsolute']);
		add_filter('revisionary_create_revision_redirect', [$this, 'fltCreateRevisionRedirect'], 10, 2);
		add_filter('revisionary_do_revision_notice', [$this, 'flt_do_revision_notice'], 10, 3);
    }

	function flt_do_revision_notice($do_it, $revision, $published_post) {
        return $do_it && empty($_REQUEST['fl-builder-redirect']);
    }

	function fltAdminBarAbsolute($absolute) {
		return false;
	}

	function getBeaverUrlArgs() {
		return method_exists('FLBuilderUIIFrame', 'is_enabled') && FLBuilderUIIFrame::is_enabled() ? ['fl_builder' => '', 'fl_builder_ui' => ''] : ['fl_builder' => ''];
	}

	function flt_edit_url($url, $post) {
		preg_match('/(https?)/', get_bloginfo('url'), $matches);

		$scheme = (isset($matches[1])) ? $matches[1] : false;

		$url = set_url_scheme(add_query_arg($this->getBeaverUrlArgs(), rvy_preview_url($post)), $scheme);

		return $url;
	}

	function fltPermalink($url, $post) {
		static $busy;

		if (!empty($busy) || is_admin()) {
			return $url;
		}

		$busy = true;

		if (rvy_in_revision_workflow($post)) {
			$url = rvy_preview_url($post);
		}

		$busy = false;

		return $url;
	}

	function flt_publish_caption($caption) {
		global $post;

		// @ todo: implement this with Save Copy functionality

		if ($post && rvy_in_revision_workflow($post)) {
			switch ($post->post_mime_type) {
				case 'draft-revision':
					if (current_user_can("set_revision_pending-revision", $post->ID)) {
						$caption = pp_revisions_status_label('pending-revision', 'submit_short');
					} else {
						$caption = pp_revisions_status_label('draft-revision', 'update');
					}

					break;

				case 'pending-revision':
					if (current_user_can("edit_post", rvy_post_id($post))) {
						$caption = esc_html__('Approve', 'revisionary-pro');
					} else {
						$caption = pp_revisions_status_label('pending-revision', 'update');
					}
					
					break;

				default:
					$caption = pp_revisions_label('update_revision');
			}
		}

		return $caption;
	}

	function submissionRedirect() {
		if ($post_id = rvy_detect_post_id()) {
            if ($revision_status = rvy_in_revision_workflow($post_id)) {
				/* Redirect to Revisions preview screen after revision status change */
				?>

				<script type="text/javascript">
				/* <![CDATA[ */

				var rvyBBinitSave = false;
				var rvyBBisSaving = false;
				var rvyRedirectDone = false;

				var rvyIntDetectStatusChange = setInterval(function() {
					
					var actionMaskElem = document.getElementsByClassName("fl-builder-publish-actions-click-away-mask");

					if (! rvyBBinitSave) {
						if (actionMaskElem.length) {
							var elemStyle = actionMaskElem[0].getAttribute('style');

							if ('display: block;' == elemStyle) {
								rvyBBinitSave = true;
							}
						}

					} else {
						if (! rvyBBisSaving) {
							if (!actionMaskElem.length || actionMaskElem[0].getAttribute('style') != 'display: block;') {
								
								doneElem = document.getElementsByClassName("fl-builderdone-button");

								if (!doneElem.length || doneElem[0].getAttribute('style') != 'display: block;') {
									rvyBBisSaving = true;
								}
							}
						} else {
							if (rvyRedirectDone) {
								return;
							}

							barElem = document.getElementsByClassName("fl-builder-bar");

							if (!barElem.length || -1 != barElem[0].className.split(' ').indexOf('is-hidden')) {
								clearInterval(rvyIntDetectStatusChange);

								//setTimeout(function() {
									rvyRedirectDone = true;
									window.location = '<?php echo add_query_arg('base_post', rvy_post_id($post_id), rvy_preview_url($post_id));?>';
								//}, 50);
							}
						}
					}
				}, 100);

				/* ]]> */
				</script>

				<style>
				body.fl-builder-edit div.rvy_view_revision a.rvy_preview_linkspan {display: none;}
				</style>

				<?php
			}
		}
	}

	function fltCreateRevisionRedirect($url, $post_id) {
        if (!empty($_REQUEST['front']) && !defined('PP_REVISIONS_BEAVER_BUILDER_NO_REDIRECT')) {
			$url = add_query_arg($this->getBeaverUrlArgs(), rvy_preview_url($post_id));
        }

        return $url;
    }

	function actRevisionQueueRowActions($actions, $post) {
		$bb = str_replace(' ', '&nbsp;', esc_html__('Beaver Builder', 'revisionary-pro'));
		
        $actions['beaver'] = sprintf(
            '<a href="%1$s" class="" title="%2$s" aria-label="%2$s">%3$s</a>',
            add_query_arg($this->getBeaverUrlArgs(), rvy_preview_url($post->ID)),
            $bb,
            $bb
        );

        return $actions;
    }

} // end RevisionaryBeaverBuilder class
