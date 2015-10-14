<?php
namespace MailPoet\Settings;

class Permissions {
  static function getCapabilities() {
    $capabilities = array(
      'mailpoet_newsletters' =>
        __('Who can create newsletters?'),
      'mailpoet_newsletter_styles' =>
        __('Who can see the styles tab in the visual editor?'),
      'mailpoet_subscribers' =>
        __('Who can manage subscribers?'),
      'mailpoet_settings' =>
        __("Who can change MailPoet's settings?")
    );
    $capabilities = apply_filters('mailpoet_capabilities', $capabilities);

    return $capabilities;
  }

  static function getRoles() {
    $roles = array();

    global $wp_roles;
    $editable_roles = apply_filters('editable_roles', $wp_roles->roles);
    foreach($editable_roles as $role => $role_data) {
      $roles[$role] = translate_user_role($role_data['name']);
    }

    return $roles;
  }

  static function get() {
    $roles = static::getRoles();
    $capabilities = static::getCapabilities();

    // go over each capability
    foreach($capabilities as $capability => $label) {
      $capability_roles = array();
      // go over each role and check permission
      foreach($roles as $role_key => $role_data) {
        // get role object based on role key
        $role = get_role($role_key);

        // assign role capability
        $capability_roles[$role_key] = array(
          'capability' => $capability,
          'is_capable' => (
            in_array($role_key, array('administrator', 'super_admin'))
            || ($role->has_cap($capability))
          ),
          'is_disabled' =>(
            in_array($role_key, array('administrator', 'super_admin'))
          )
        );
      }
      $capabilities[$capability] = array(
        'label' => $label,
        'roles' => $capability_roles
      );
    }

    return array(
      'roles' => $roles,
      'capabilities' => $capabilities
    );
  }

  static function set($permissions = array()) {
    if(!empty($permissions)) {
      foreach($permissions as $permission) {
        // ignore administrator & superadmin roles
        if(in_array(
          $permission['role'],
          array('administrator', 'superadmin'))
        ) {
          continue;
        }

        // get role
        $role = get_role($permission['role']);
        if((bool)$permission['is_capable'] === true) {
          // add capability to role
          $role->add_cap($permission['capability']);
        } else {
          // remove capability to role
          if($role->has_cap($permission['capability'])) {
            $role->remove_cap($permission['capability']);
          }
        }
      }
    }
  }
}