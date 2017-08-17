<?php

namespace MailPoet\Config;

use MailPoet\WP\Hooks as WPHooks;

if(!defined('ABSPATH')) exit;
require_once(ABSPATH . 'wp-includes/pluggable.php');

class AccessControl {
  const PERMISSION_ACCESS_PLUGIN_ADMIN = 'access_plugin_admin';
  const PERMISSION_MANAGE_SETTINGS = 'manage_settings';
  const PERMISSION_MANAGE_EMAILS = 'manage_emails';
  const PERMISSION_MANAGE_SUBSCRIBERS = 'manage_subscribers';
  const PERMISSION_MANAGE_FORMS = 'manage_forms';
  const PERMISSION_MANAGE_SEGMENTS = 'manage_segments';
  const PERMISSION_UPDATE_PLUGIN = 'update_plugin';
  const NO_ACCESS_RESTRICTION = 'no_access_restriction';

  public $permissions;
  public $current_user_roles;
  public $user_capabilities;

  function __construct() {
    $this->permissions = $this->getDefaultPermissions();
    $this->user_roles = $this->getUserRoles();
    $this->user_capabilities = $this->getUserCapabilities();
  }

  private function getDefaultPermissions() {
    return array(
      self::PERMISSION_ACCESS_PLUGIN_ADMIN => WPHooks::applyFilters(
        'mailpoet_permission_access_plugin_admin',
        array(
          'administrator',
          'editor'
        )
      ),
      self::PERMISSION_MANAGE_SETTINGS => WPHooks::applyFilters(
        'mailpoet_permission_manage_settings',
        array(
          'administrator'
        )
      ),
      self::PERMISSION_MANAGE_EMAILS => WPHooks::applyFilters(
        'mailpoet_permission_manage_emails',
        array(
          'administrator',
          'editor'
        )
      ),
      self::PERMISSION_MANAGE_SUBSCRIBERS => WPHooks::applyFilters(
        'mailpoet_permission_manage_subscribers',
        array(
          'administrator'
        )
      ),
      self::PERMISSION_MANAGE_FORMS => WPHooks::applyFilters(
        'mailpoet_permission_manage_forms',
        array(
          'administrator'
        )
      ),
      self::PERMISSION_MANAGE_SEGMENTS => WPHooks::applyFilters(
        'mailpoet_permission_manage_segments',
        array(
          'administrator'
        )
      ),
      self::PERMISSION_UPDATE_PLUGIN => WPHooks::applyFilters(
        'mailpoet_permission_update_plugin',
        array(
          'administrator'
        )
      ),
    );
  }

  function getUserRoles() {
    $user = wp_get_current_user();
    return $user->roles;
  }

  function getUserCapabilities() {
    $user = wp_get_current_user();
    return array_keys($user->allcaps);
  }

  function getUserFirstCapability() {
    return (!empty($this->user_capabilities)) ?
      $this->user_capabilities[0] :
      null;
  }

  function validatePermission($permission) {
    if(empty($this->permissions[$permission])) return false;
    $permitted_roles = array_intersect(
      $this->user_roles,
      $this->permissions[$permission]
    );
    return (!empty($permitted_roles));
  }
}