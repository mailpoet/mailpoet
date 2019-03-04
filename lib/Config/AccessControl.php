<?php

namespace MailPoet\Config;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class AccessControl {
  const PERMISSION_ACCESS_PLUGIN_ADMIN = 'mailpoet_access_plugin_admin';
  const PERMISSION_MANAGE_SETTINGS = 'mailpoet_manage_settings';
  const PERMISSION_MANAGE_EMAILS = 'mailpoet_manage_emails';
  const PERMISSION_MANAGE_SUBSCRIBERS = 'mailpoet_manage_subscribers';
  const PERMISSION_MANAGE_FORMS = 'mailpoet_manage_forms';
  const PERMISSION_MANAGE_SEGMENTS = 'mailpoet_manage_segments';
  const NO_ACCESS_RESTRICTION = 'mailpoet_no_access_restriction';

  function getDefaultPermissions() {
    return array(
      self::PERMISSION_ACCESS_PLUGIN_ADMIN => WPFunctions::get()->applyFilters(
        'mailpoet_permission_access_plugin_admin',
        array(
          'administrator',
          'editor'
        )
      ),
      self::PERMISSION_MANAGE_SETTINGS => WPFunctions::get()->applyFilters(
        'mailpoet_permission_manage_settings',
        array(
          'administrator'
        )
      ),
      self::PERMISSION_MANAGE_EMAILS => WPFunctions::get()->applyFilters(
        'mailpoet_permission_manage_emails',
        array(
          'administrator',
          'editor'
        )
      ),
      self::PERMISSION_MANAGE_SUBSCRIBERS => WPFunctions::get()->applyFilters(
        'mailpoet_permission_manage_subscribers',
        array(
          'administrator'
        )
      ),
      self::PERMISSION_MANAGE_FORMS => WPFunctions::get()->applyFilters(
        'mailpoet_permission_manage_forms',
        array(
          'administrator'
        )
      ),
      self::PERMISSION_MANAGE_SEGMENTS => WPFunctions::get()->applyFilters(
        'mailpoet_permission_manage_segments',
        array(
          'administrator'
        )
      ),
    );
  }

  function getPermissionLabels() {
    return array(
      self::PERMISSION_ACCESS_PLUGIN_ADMIN => WPFunctions::get()->__('Admin menu item', 'mailpoet'),
      self::PERMISSION_MANAGE_SETTINGS => WPFunctions::get()->__('Manage settings', 'mailpoet'),
      self::PERMISSION_MANAGE_EMAILS => WPFunctions::get()->__('Manage emails', 'mailpoet'),
      self::PERMISSION_MANAGE_SUBSCRIBERS => WPFunctions::get()->__('Manage subscribers', 'mailpoet'),
      self::PERMISSION_MANAGE_FORMS => WPFunctions::get()->__('Manage forms', 'mailpoet'),
      self::PERMISSION_MANAGE_SEGMENTS => WPFunctions::get()->__('Manage segments', 'mailpoet'),
    );
  }

  function validatePermission($permission) {
    if ($permission === self::NO_ACCESS_RESTRICTION) return true;
    return WPFunctions::get()->currentUserCan($permission);
  }
}
