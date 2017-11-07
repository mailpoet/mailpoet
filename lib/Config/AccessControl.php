<?php

namespace MailPoet\Config;

use MailPoet\WP\Hooks as WPHooks;

if(!defined('ABSPATH')) exit;
require_once(ABSPATH . 'wp-includes/pluggable.php');

class AccessControl {
  const PERMISSION_ACCESS_PLUGIN_ADMIN = 'mailpoet_access_plugin_admin';
  const PERMISSION_MANAGE_SETTINGS = 'mailpoet_manage_settings';
  const PERMISSION_MANAGE_EMAILS = 'mailpoet_manage_emails';
  const PERMISSION_MANAGE_SUBSCRIBERS = 'mailpoet_manage_subscribers';
  const PERMISSION_MANAGE_FORMS = 'mailpoet_manage_forms';
  const PERMISSION_MANAGE_SEGMENTS = 'mailpoet_manage_segments';
  const NO_ACCESS_RESTRICTION = 'mailpoet_no_access_restriction';

  public $permissions;
  public $user_roles;
  public $user_capabilities;

  function __construct() {
    $this->permissions = self::getDefaultPermissions();
  }

  static function getDefaultPermissions() {
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
    );
  }

  static function getPermissionLabels() {
    return array(
      self::PERMISSION_ACCESS_PLUGIN_ADMIN => __('Admin menu item', 'mailpoet'),
      self::PERMISSION_MANAGE_SETTINGS => __('Manage settings', 'mailpoet'),
      self::PERMISSION_MANAGE_EMAILS => __('Manage emails', 'mailpoet'),
      self::PERMISSION_MANAGE_SUBSCRIBERS => __('Manage subscribers', 'mailpoet'),
      self::PERMISSION_MANAGE_FORMS => __('Manage forms', 'mailpoet'),
      self::PERMISSION_MANAGE_SEGMENTS => __('Manage segments', 'mailpoet'),
    );
  }

  function validatePermission($permission) {
    if($permission === self::NO_ACCESS_RESTRICTION) return true;
    return current_user_can($permission);
  }
}
