<?php

namespace MailPoet\Config;

use MailPoet\WP\Hooks as WPHooks;

if(!defined('ABSPATH')) exit;
require_once(ABSPATH . 'wp-includes/pluggable.php');

class AccessControl {
  static $permissions;
  const PERMISSION_ACCESS_PLUGIN = 'access_plugin';
  const PERMISSION_MANAGE_SETTINGS = 'manage_settings';
  const PERMISSION_MANAGE_EMAILS = 'manage_emails';
  const PERMISSION_MANAGE_SUBSCRIBERS = 'manage_subscribers';
  const PERMISSION_MANAGE_FORMS = 'manage_forms';
  const PERMISSION_MANAGE_SEGMENTS = 'manage_segments';

  static function init($permissions = array()) {
    self::setPermissions($permissions);
  }

  static function setPermissions($permissions = array()) {
    self::$permissions = ($permissions) ? $permissions : self::getPermissions();
  }

  static function getPermissions() {
    return array(
      self::PERMISSION_ACCESS_PLUGIN => WPHooks::applyFilters(
        'mailpoet_permission_access_plugin',
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
      )
    );
  }

  static function validatePermission($permission) {
    if(empty(self::$permissions)) self::init();
    if(empty(self::$permissions[$permission])) return false;
    $current_user = wp_get_current_user();
    $current_user_roles = $current_user->roles;
    $permitted_roles = array_intersect(
      $current_user_roles,
      self::$permissions[$permission]
    );
    return (!empty($permitted_roles));
  }
}