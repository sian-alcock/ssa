<?php

/* Set wpseo_noindex_author to 'on' when registering a new User.
*/
add_action( 'user_register', 'set_noindex_author', 10, 1 );

function set_noindex_author( $user_id ) {
  update_user_meta( $user_id, 'wpseo_noindex_author', 'on' );
  update_user_meta( $user_id, 'wpseo_profile', array('noindex-author' => 1) );
}

function set_author_archive_setting_checked($user_id) {
  update_user_meta( $user_id, 'wpseo_noindex_author', 'on' );
  update_user_meta( $user_id, 'wpseo_profile', array('noindex-author' => 1) );
}

function add_author_archive_setting_checkbox() {
  $user_id = get_current_user_id();
  $user = get_userdata($user_id);

  if ($user) {
      if (in_array('administrator', $user->roles)) {
      add_action('show_user_profile', 'author_archive_setting_checkbox');
      add_action('edit_user_profile', 'author_archive_setting_checkbox');
  }
  }
}

function author_archive_setting_checkbox($user) {
  $is_noindex_set = get_user_meta($user->ID, 'wpseo_noindex_author', true);
  $noindex_author = isset($is_noindex_set) && ($is_noindex_set == 'on' || $is_noindex_set == '1') ? 'checked="checked"' : '';

  ?>
  <h3><?php esc_html_e('Yoast SEO Settings', 'text-domain'); ?></h3>
  <table class="form-table">
      <tr>
          <th>
              <label for="noindex-author"><?php esc_html_e("Do not allow search engines to show this author's archives in search results", 'text-domain'); ?></label>
          </th>
          <td>
              <input type="checkbox" id="noindex-author" name="noindex-author" value="1" <?php echo $noindex_author; ?> />
          </td>
      </tr>
  </table>
  <?php
}

function save_author_archive_setting($user_id) {
  if (!current_user_can('edit_user', $user_id)) {
      return false;
  }

  if (isset($_POST['noindex-author'])) {
      update_user_meta($user_id, 'wpseo_profile', array('noindex-author' => 1));
      update_user_meta( $user_id, 'wpseo_noindex_author', 'on' );
  } else {
      update_user_meta($user_id, 'wpseo_profile', array('noindex-author' => 0));
      update_user_meta( $user_id, 'wpseo_noindex_author', '' );
  }
}

// IOUH-165 Adding a custom checkbox to hide authors being crawled for SEO - intial value of checked when creating new user
// IOUH-192 update to prevent fubnction breaking the filters if not logged in

add_action('admin_init', 'add_author_archive_setting_checkbox');
add_action('personal_options_update', 'save_author_archive_setting');
add_action('edit_user_profile_update', 'save_author_archive_setting');
add_action('edit_user_created_user', 'set_author_archive_setting_checked');


/* Remove tag archives.
*/

add_action('template_redirect', 'remove_archives_tag');

function remove_archives_tag() {
    if (is_tag()){
        $target = get_option('siteurl');
        $status = '301';
        wp_redirect($target, 301);
        die();
    }
}
