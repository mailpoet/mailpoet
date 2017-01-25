<?php
use MailPoet\API\API;

// required to be able to use wp_delete_user()
require_once(ABSPATH.'wp-admin/includes/user.php');

class APITest extends MailPoetTest {
  function _before() {
    // create WP user
    $this->wp_user_id = null;
    $wp_user_id = wp_create_user('WP User', 'pass', 'wp_user@mailpoet.com');
    if(is_wp_error($wp_user_id)) {
      // user already exists
      $this->wp_user_id = email_exists('wp_user@mailpoet.com');
    } else {
      $this->wp_user_id = $wp_user_id;
    }

    $this->api = new API();
  }

  function testItChecksPermissions() {
    // logged out user
    expect($this->api->checkPermissions())->false();

    // give administrator role to wp user
    $wp_user = get_user_by('id', $this->wp_user_id);
    $wp_user->add_role('administrator');
    wp_set_current_user($wp_user->ID, $wp_user->user_login);

    // administrator should have permission
    expect($this->api->checkPermissions())->true();
  }

  function _after() {
    wp_delete_user($this->wp_user_id);
  }
}