<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use Codeception\Stub;
use MailPoet\Config\AccessControl;
use MailPoet\WP\Functions as WPFunctions;

class AccessControlTest extends \MailPoetTest {

  /** @var AccessControl */
  private $accessControl;

  public function _before() {
    parent::_before();
    $this->accessControl = new AccessControl;
  }

  public function testItAllowsSettingCustomPermissions() {
    $wp = new WPFunctions;
    $wp->addFilter(
      'mailpoet_permission_access_plugin_admin',
      function() {
        return ['custom_access_plugin_admin_role'];
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_settings',
      function() {
        return ['custom_manage_settings_role'];
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_features',
      function() {
        return ['custom_manage_features_role'];
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_emails',
      function() {
        return ['custom_manage_emails_role'];
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_subscribers',
      function() {
        return ['custom_manage_subscribers_role'];
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_forms',
      function() {
        return ['custom_manage_forms_role'];
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_segments',
      function() {
        return ['custom_manage_segments_role'];
      }
    );
    $wp->addFilter(
      'mailpoet_permission_manage_automations',
      function() {
        return ['custom_manage_automations_role'];
      }
    );

    expect($this->accessControl->getDefaultPermissions())->equals(
      [
        AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN => [
          'custom_access_plugin_admin_role',
        ],
        AccessControl::PERMISSION_MANAGE_SETTINGS => [
          'custom_manage_settings_role',
        ],
        AccessControl::PERMISSION_MANAGE_FEATURES => [
          'custom_manage_features_role',
        ],
        AccessControl::PERMISSION_MANAGE_EMAILS => [
          'custom_manage_emails_role',
        ],
        AccessControl::PERMISSION_MANAGE_SUBSCRIBERS => [
          'custom_manage_subscribers_role',
        ],
        AccessControl::PERMISSION_MANAGE_FORMS => [
          'custom_manage_forms_role',
        ],
        AccessControl::PERMISSION_MANAGE_SEGMENTS => [
          'custom_manage_segments_role',
        ],
        AccessControl::PERMISSION_MANAGE_AUTOMATIONS => [
          'custom_manage_automations_role',
        ],
      ]
    );
  }

  public function testItGetsPermissionLabels() {
    $permissions = $this->accessControl->getDefaultPermissions();
    $labels = $this->accessControl->getPermissionLabels();
    expect(count($permissions))->equals(count($labels));
  }

  public function testItValidatesIfUserHasCapability() {
    $capability = 'some_capability';
    $accessControl = new AccessControl();
    WPFunctions::set(Stub::make(new WPFunctions, [
      'currentUserCan' => true,
    ]));

    expect($accessControl->validatePermission($capability))->true();
  }

  public function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions);
  }
}
