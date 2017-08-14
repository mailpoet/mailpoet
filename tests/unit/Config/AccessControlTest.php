<?php

namespace MailPoet\Test\Config;

use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Config\AccessControl;
use MailPoet\WP\Hooks;

class AccessControlTest extends \MailPoetTest {
  function testItSetsDefaultPermissionsUponInitialization() {
    AccessControl::init();
    $default_permissions = array(
      'access_plugin' => array(
        'administrator',
        'editor'
      ),
      'manage_settings' => array(
        'administrator'
      ),
      'manage_emails' => array(
        'administrator',
        'editor'
      ),
      'manage_subscribers' => array(
        'administrator'
      ),
      'manage_forms' => array(
        'administrator'
      ),
      'manage_segments' => array(
        'administrator'
      )
    );
    expect(AccessControl::getPermissions())->equals($default_permissions);
  }

  function testItSetsCustomPermissionsUponInitialization() {
    $custom_permissions = array(
      'custom_permissions' => array(
        'custom_role'
      )
    );
    AccessControl::init($custom_permissions);
    expect(AccessControl::$permissions)->equals($custom_permissions);
  }

  function testItGetsPermissions() {
    expect(AccessControl::getPermissions())->equals(
      array(
        'access_plugin' => array(
          'administrator',
          'editor'
        ),
        'manage_settings' => array(
          'administrator'
        ),
        'manage_emails' => array(
          'administrator',
          'editor'
        ),
        'manage_subscribers' => array(
          'administrator'
        ),
        'manage_forms' => array(
          'administrator'
        ),
        'manage_segments' => array(
          'administrator'
        )
      )
    );
  }

  function testItAllowsSettingCustonPermissions() {
    Hooks::addFilter(
      'mailpoet_permission_access_plugin',
      function() {
        return array('custom_access_plugin_role');
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
        return array('custom_manage_forms_role');
      }
    );
    AccessControl::init();
    expect(AccessControl::$permissions)->equals(
      array(
        'access_plugin' => array(
          'custom_access_plugin_role'
        ),
        'manage_settings' => array(
          'custom_manage_settings_role'
        ),
        'manage_emails' => array(
          'custom_manage_emails_role'
        ),
        'manage_subscribers' => array(
          'custom_manage_subscribers_role'
        ),
        'manage_forms' => array(
          'custom_manage_forms_role'
        ),
        'manage_segments' => array(
          'custom_manage_forms_role'
        )
      )
    );
  }

  function _after() {
    WPHooksHelper::releaseAllHooks();
  }
}