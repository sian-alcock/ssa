<?php
class RevisionaryProSettingsUI {
    function __construct() {
        add_action('revisionary_settings_ui', [$this, 'actSettingsUI']);
        add_action('revisionary_option_ui_pending_revisions', [$this, 'actPendingRevisionsUI']);
        add_action('revisionary_option_ui_revision_options', [$this, 'actRevisionOptionsUI']);
        add_filter('revisionary_option_captions', [$this, 'fltOptionCaptions']);
        add_filter('revisionary_option_sections', [$this, 'fltOptionSections']);
    }

    function actSettingsUI($ui) {
        $ui->option_captions['display_pp_branding'] = esc_html__('Display PublishPress Branding in Admin', 'revisionary-pro');

        $ui->section_captions = ['license' => esc_html__('License Key', 'revisionary-pro'), 'branding' => esc_html__('Branding', 'revisionary-pro')] + $ui->section_captions;

        $ui->form_options['features']['branding'] = ['display_pp_branding'];
    }

    function fltOptionCaptions($captions) {
        $captions['pending_revision_unpublished'] = (rvy_get_option('revision_statuses_noun_labels')) ? esc_html__('Change Requests for Unpublished Posts', 'revisionary-pro') :  esc_html__('Revision Submission for Unpublished Posts', 'revisionary-pro');
        $captions['publish_by_revision'] = (rvy_get_option('revision_statuses_noun_labels')) ? esc_html__('Publish by Change Request', 'revisionary-pro') :  esc_html__('Publish by Revision', 'revisionary-pro');
        
        if (class_exists('ACF')) {
            $captions['prevent_rest_revisions'] = esc_html__('Prevent Redundant Revisions', 'revisionary-pro');
        }

        return $captions;
    }

    function fltOptionSections($sections) {
        $sections['features']['pending_revisions'][] = 'pending_revision_unpublished';
        $sections['features']['pending_revisions'][] = 'publish_by_revision';

        if (class_exists('ACF')) {
            $sections['features']['revisions'][] = 'prevent_rest_revisions';
        }

        return $sections;
    }

    function actPendingRevisionsUI($settings_ui) {
        $hint = '';
        $settings_ui->option_checkbox('pending_revision_unpublished', 'features', 'pending_revisions', $hint, '');

        $hint = (rvy_get_option('revision_statuses_noun_labels')) 
        ? esc_html__('Approval of a Change Request to an unpublished post causes it to be published', 'revisionary-pro')
        : esc_html__('Approval of a Revision to an unpublished post causes it to be published', 'revisionary-pro');

        
        $args = [];
        $args['style'] = (rvy_get_option('pending_revision_unpublished')) ? '' : 'display: none;';

        $settings_ui->option_checkbox('publish_by_revision', 'features', 'pending_revisions', $hint, '', $args);

        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready( function($) {
            $('#pending_revision_unpublished').on('click', function() {
                $('#publish_by_revision').closest('div').toggle( $('#pending_revision_unpublished').prop('checked'));
            });
        });
        /* ]]> */
        </script>
    <?php
    }

    function actRevisionOptionsUI($settings_ui) {
        echo "<br />";
        
        if (class_exists('ACF')) {
            $hint = esc_html__( 'Prevent REST requests from generating revisions (which may be stored without ACF fields)', 'revisionary-pro' );
            $settings_ui->option_checkbox('prevent_rest_revisions', 'features', 'revisions', $hint, '');
        }

        $hint = esc_html__('Revisors will be able to request the deletion of a published post. The Deletion Queue is not accessible until a deletion request exists.', 'revisionary-pro');
		$settings_ui->option_checkbox('deletion_queue', 'features', 'revisions', $hint, '');
        echo '<br />';
    }
}