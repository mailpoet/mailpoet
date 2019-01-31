<?php

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class Capabilities {
  const MEMBERS_CAP_GROUP_NAME = 'mailpoet';

  private $renderer = null;
  private $wp;

  function __construct($renderer = null, WPFunctions $wp = null) {
    if($renderer !== null) {
      $this->renderer = $renderer;
    }
    if($wp == null) {
      $wp = new WPFunctions;
    }
    $this->wp = $wp;
  }

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
        if(!is_object($role_objects[$role])) continue;
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
        if(!is_object($role_objects[$role])) continue;
        $role_objects[$role]->remove_cap($name);
      }
    }
  }

  function setupMembersCapabilities() {
    $this->wp->addAction('admin_enqueue_scripts', array($this, 'enqueueMembersStyles'));
    $this->wp->addAction('members_register_cap_groups', array($this, 'registerMembersCapGroup'));
    $this->wp->addAction('members_register_caps', array($this, 'registerMembersCapabilities'));
  }

  function enqueueMembersStyles() {
    wp_enqueue_style(
      'mailpoet-admin-global',
      Env::$assets_url . '/dist/css/' . $this->renderer->getCssAsset('admin-global.css')
    );
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
      $this->registerMembersCapability($name, $label);
    }
  }

  function registerMembersCapability($name, $label) {
    members_register_cap(
      $name,
      array(
        'label' => $label,
        'group' => self::MEMBERS_CAP_GROUP_NAME
      )
    );
  }
}
