<?php

namespace MailPoet\Config;

use MailPoet\WP\Hooks;

class Capabilities {
  const MEMBERS_CAP_GROUP_NAME = 'mailpoet';

  function init() {
    $this->setupMembersCapabilities();
  }

  function setupWPCapabilities() {
    $permissions = AccessControl::getDefaultPermissions();
    $role_objects = array();
    foreach($permissions as $name => $roles) {
      foreach($roles as $role) {
        if(!isset($role_objects[$role])) {
          $role_objects[$role] = get_role($role);
        }
        $role_objects[$role]->add_cap($name);
      }
    }
  }

  function removeWPCapabilities() {
    $permissions = AccessControl::getDefaultPermissions();
    $role_objects = array();
    foreach($permissions as $name => $roles) {
      foreach($roles as $role) {
        if(!isset($role_objects[$role])) {
          $role_objects[$role] = get_role($role);
        }
        $role_objects[$role]->remove_cap($name);
      }
    }
  }

  function setupMembersCapabilities() {
    wp_enqueue_style('mailpoet-admin-global', Env::$assets_url . '/css/admin-global.css');
    Hooks::addAction('members_register_cap_groups', array($this, 'registerMembersCapGroup'));
    Hooks::addAction('members_register_caps', array($this, 'registerMembersCapabilities'));
  }

  function registerMembersCapGroup() {
    members_register_cap_group(
      self::MEMBERS_CAP_GROUP_NAME,
      array(
        'label' => __('MailPoet', 'mailpoet'),
        'caps' => array(),
        'icon' => 'mailpoet-icon-logo',
        'priority' => 30
      )
    );
  }

  function registerMembersCapabilities() {
    $permissions = AccessControl::getPermissionLabels();
    foreach($permissions as $name => $label) {
      members_register_cap(
        $name,
        array(
          'label' => $label,
          'group' => self::MEMBERS_CAP_GROUP_NAME
        )
      );
    }
  }
}
