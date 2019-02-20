<?php

namespace MailPoet\Test\Config;

use AspectMock\Test as Mock;
use Helper\WordPress as WPHelper;
use MailPoet\Config\AccessControl;
use MailPoet\WP\Functions as WPFunctions;

class AccessControlTest extends \MailPoetTest {

  /** @var AccessControl */
  private $access_control;

  function _before() {
    parent::_before();
    $this->access_control = new AccessControl(new WPFunctions());
  }

  function testItAllowsSettingCustomPermissions() {
    $wp = new WPFunctions;
    $wp->addFilter(
      'mailpoet_permission_access_plugin_admin',
      function() {
        return array('custom_access_plugin_admin_role');
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_settings',
      function() {
        return array('custom_manage_settings_role');
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_emails',
      function() {
        return array('custom_manage_emails_role');
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_subscribers',
      function() {
        return array('custom_manage_subscribers_role');
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_forms',
      function() {
        return array('custom_manage_forms_role');
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_segments',
      function() {
        return array('custom_manage_segments_role');
      }
    );

    expect($this->access_control->getDefaultPermissions())->equals(
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
    $permissions = $this->access_control->getDefaultPermissions();
    $labels = $this->access_control->getPermissionLabels();
    expect(count($permissions))->equals(count($labels));
  }

  function testItValidatesIfUserHasCapability() {
    $capability = 'some_capability';

    $func = Mock::func('MailPoet\Config', 'current_user_can', true);

    expect($this->access_control->validatePermission($capability))->true();
    $func->verifyInvoked([$capability]);
  }

  function _after() {
    Mock::clean();
    WPHelper::releaseAllFunctions();
  }
}
