<?php
namespace MailPoet\Config;

class Permissions {
  function __construct() {
  }

  function init() {
    add_action(
      'admin_init',
      array($this, 'setup')
    );
  }

  function setup() {
    // administrative roles
    $roles = array('administrator', 'super_admin');

    // mailpoet capabilities
    $capabilities = array(
      'mailpoet_newsletters',
      'mailpoet_newsletter_styles',
      'mailpoet_subscribers',
      'mailpoet_settings',
      'mailpoet_statistics'
    );

    foreach($roles as $role_key){
        // get role based on role key
      $role = get_role($role_key);

        // if the role doesn't exist, skip it
      if($role !== null) {
        // add capability
        foreach($capabilities as $capability) {
          if(!$role->has_cap($capability)) {
            $role->add_cap($capability);
          }
        }
      }
    }
  }
}