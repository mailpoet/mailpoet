<?php

namespace MailPoet\Config;
use MailPoet\WP\Functions as WPFunctions;
use WP_Role;

class Capabilities {
  const MEMBERS_CAP_GROUP_NAME = 'mailpoet';

  private $renderer = null;
  /** @var WPFunctions  */
  private $wp;
  /** @var AccessControl */
  private $access_control;

  function __construct($renderer = null, WPFunctions $wp = null) {
    if ($renderer !== null) {
      $this->renderer = $renderer;
    }
    if ($wp == null) {
      $wp = new WPFunctions;
    }
    $this->wp = $wp;
    $this->access_control = new AccessControl;
  }

  function init() {
    $this->setupMembersCapabilities();
  }

  function setupWPCapabilities() {
    $permissions = $this->access_control->getDefaultPermissions();
    $role_objects = [];
    foreach ($permissions as $name => $roles) {
      foreach ($roles as $role) {
        if (!isset($role_objects[$role])) {
          $role_objects[$role] = WPFunctions::get()->getRole($role);
        }
        if (!$role_objects[$role] instanceof WP_Role) continue;
        $role_objects[$role]->add_cap($name);
      }
    }
  }

  function removeWPCapabilities() {
    $permissions = $this->access_control->getDefaultPermissions();
    $role_objects = [];
    foreach ($permissions as $name => $roles) {
      foreach ($roles as $role) {
        if (!isset($role_objects[$role])) {
          $role_objects[$role] = WPFunctions::get()->getRole($role);
        }
        if (!$role_objects[$role] instanceof WP_Role) continue;
        $role_objects[$role]->remove_cap($name);
      }
    }
  }

  function setupMembersCapabilities() {
    $this->wp->addAction('admin_enqueue_scripts', [$this, 'enqueueMembersStyles']);
    $this->wp->addAction('members_register_cap_groups', [$this, 'registerMembersCapGroup']);
    $this->wp->addAction('members_register_caps', [$this, 'registerMembersCapabilities']);
  }

  function enqueueMembersStyles() {
    WPFunctions::get()->wpEnqueueStyle(
      'mailpoet-admin-global',
      Env::$assets_url . '/dist/css/' . $this->renderer->getCssAsset('adminGlobal.css')
    );
  }

  function registerMembersCapGroup() {
    members_register_cap_group(
      self::MEMBERS_CAP_GROUP_NAME,
      [
        'label' => WPFunctions::get()->__('MailPoet', 'mailpoet'),
        'caps' => [],
        'icon' => 'mailpoet-icon-logo',
        'priority' => 30,
      ]
    );
  }

  function registerMembersCapabilities() {
    $permissions = $this->access_control->getPermissionLabels();
    foreach ($permissions as $name => $label) {
      $this->registerMembersCapability($name, $label);
    }
  }

  function registerMembersCapability($name, $label) {
    members_register_cap(
      $name,
      [
        'label' => $label,
        'group' => self::MEMBERS_CAP_GROUP_NAME,
      ]
    );
  }
}
