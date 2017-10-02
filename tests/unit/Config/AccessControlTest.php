<?php

namespace MailPoet\Test\Config;

use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Config\AccessControl;
use MailPoet\WP\Hooks;

class AccessControlTest extends \MailPoetTest {
  function testItSetsDefaultPermissionsUponInitialization() {
    $default_permissions = array(
      AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN => array(
        'administrator',
        'editor'
      ),
      AccessControl::PERMISSION_MANAGE_SETTINGS => array(
        'administrator'
      ),
      AccessControl::PERMISSION_MANAGE_EMAILS => array(
        'administrator',
        'editor'
      ),
      AccessControl::PERMISSION_MANAGE_SUBSCRIBERS => array(
        'administrator'
      ),
      AccessControl::PERMISSION_MANAGE_FORMS => array(
        'administrator'
      ),
      AccessControl::PERMISSION_MANAGE_SEGMENTS => array(
        'administrator'
      ),
    );
    $access_control = new AccessControl();
    expect($access_control->permissions)->equals($default_permissions);
  }

  function testItAllowsSettingCustomPermissions() {
    Hooks::addFilter(
      'mailpoet_permission_access_plugin_admin',
      function() {
        return array('custom_access_plugin_admin_role');
      }
    );
    Hooks::addFilter(
      'mailpoet_permission_manage_settings',
      function() {
        return array('custom_manage_settings_role');
      }
    );
    Hooks::addFilter(
      'mailpoet_permission_manage_emails',
      function() {
        return array('custom_manage_emails_role');
      }
    );
    Hooks::addFilter(
      'mailpoet_permission_manage_subscribers',
      function() {
        return array('custom_manage_subscribers_role');
      }
    );
    Hooks::addFilter(
      'mailpoet_permission_manage_forms',
      function() {
        return array('custom_manage_forms_role');
      }
    );
    Hooks::addFilter(
      'mailpoet_permission_manage_segments',
      function() {
        return array('custom_manage_segments_role');
      }
    );

    $access_control = new AccessControl();
    expect($access_control->permissions)->equals(
      array(
        AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN => array(
          'custom_access_plugin_admin_role'
        ),
        AccessControl::PERMISSION_MANAGE_SETTINGS => array(
          'custom_manage_settings_role'
        ),
        AccessControl::PERMISSION_MANAGE_EMAILS => array(
          'custom_manage_emails_role'
        ),
        AccessControl::PERMISSION_MANAGE_SUBSCRIBERS => array(
          'custom_manage_subscribers_role'
        ),
        AccessControl::PERMISSION_MANAGE_FORMS => array(
          'custom_manage_forms_role'
        ),
        AccessControl::PERMISSION_MANAGE_SEGMENTS => array(
          'custom_manage_segments_role'
        ),
      )
    );
  }

  function testItGetsPermissionLabels() {
    $permissions = AccessControl::getDefaultPermissions();
    $labels = AccessControl::getPermissionLabels();
    expect(count($permissions))->equals(count($labels));
  }

  function _after() {
    WPHooksHelper::releaseAllHooks();
  }
}