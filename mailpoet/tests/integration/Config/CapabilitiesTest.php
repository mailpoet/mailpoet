<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Capabilities;
use MailPoet\Config\RendererFactory;
use MailPoet\WP\Functions as WPFunctions;
use WP_Role;

class CapabilitiesTest extends \MailPoetTest {

  /** @var AccessControl */
  private $accessControl;

  /** @var Capabilities */
  private $caps;

  public function _before() {
    parent::_before();
    $renderer = (new RendererFactory())->getRenderer();
    $this->caps = new Capabilities($renderer);
    $this->accessControl = new AccessControl();
  }

  public function _after() {
    // Guarantee cleanup even if assertions fail
    delete_transient(Capabilities::TRANSIENT_CAPS_VERIFIED);
    $this->caps->setupWPCapabilities();
    set_current_screen('front');
    parent::_after();
  }

  public function testItInitializes() {
    $caps = Stub::makeEmptyExcept(
      $this->caps,
      'init',
      [
        'setupMembersCapabilities' => Expected::once(),
        'wp' => new WPFunctions(),
        'accessControl' => new AccessControl(),
      ],
      $this
    );
    $caps->init();
  }

  public function testItSetsUpWPCapabilities() {
    $permissions = $this->accessControl->getDefaultPermissions();
    $this->caps->setupWPCapabilities();
    $checked = false;
    foreach ($permissions as $name => $roles) {
      foreach ($roles as $role) {
        $checked = true;
        $role = get_role($role);
        $this->assertInstanceOf(WP_Role::class, $role);
        verify($role->has_cap($name))->true();
      }
    }
    verify($checked)->true();
  }

  public function testItRemovesWPCapabilities() {
    $permissions = $this->accessControl->getDefaultPermissions();
    $this->caps->removeWPCapabilities();
    $checked = false;
    foreach ($permissions as $name => $roles) {
      foreach ($roles as $role) {
        $checked = true;
        $role = get_role($role);
        $this->assertInstanceOf(WP_Role::class, $role);
        verify($role->has_cap($name))->false();
      }
    }
    verify($checked)->true();
    // Restore capabilities
    $this->caps->setupWPCapabilities();
  }

  public function testItRestoresMissingCapabilitiesOnInit() {
    // Simulate admin context
    set_current_screen('dashboard');

    // Remove a capability to simulate it being lost
    $adminRole = get_role('administrator');
    $this->assertInstanceOf(WP_Role::class, $adminRole);
    $adminRole->remove_cap(AccessControl::PERMISSION_MANAGE_AUTOMATIONS);
    verify($adminRole->has_cap(AccessControl::PERMISSION_MANAGE_AUTOMATIONS))->false();

    // Clear any cached transient
    delete_transient(Capabilities::TRANSIENT_CAPS_VERIFIED);

    // init() should restore the missing capability
    $this->caps->init();

    // Verify capability was restored
    $adminRole = get_role('administrator');
    $this->assertInstanceOf(WP_Role::class, $adminRole);
    verify($adminRole->has_cap(AccessControl::PERMISSION_MANAGE_AUTOMATIONS))->true();

    // Verify transient was set
    verify(get_transient(Capabilities::TRANSIENT_CAPS_VERIFIED))->notEmpty();
  }

  public function testItSkipsCapabilityCheckWhenTransientIsSet() {
    // Simulate admin context
    set_current_screen('dashboard');

    // Set the transient to simulate a recent successful check
    set_transient(Capabilities::TRANSIENT_CAPS_VERIFIED, '1', HOUR_IN_SECONDS);

    // Remove a capability
    $adminRole = get_role('administrator');
    $this->assertInstanceOf(WP_Role::class, $adminRole);
    $adminRole->remove_cap(AccessControl::PERMISSION_MANAGE_AUTOMATIONS);

    // init() should NOT restore it because transient is set
    $this->caps->init();

    // Capability should still be missing
    $adminRole = get_role('administrator');
    $this->assertInstanceOf(WP_Role::class, $adminRole);
    verify($adminRole->has_cap(AccessControl::PERMISSION_MANAGE_AUTOMATIONS))->false();
  }

  public function testItSkipsCapabilityCheckOnNonAdminRequests() {
    // Ensure we're NOT in admin context
    set_current_screen('front');

    // Clear transient
    delete_transient(Capabilities::TRANSIENT_CAPS_VERIFIED);

    // Remove a capability
    $adminRole = get_role('administrator');
    $this->assertInstanceOf(WP_Role::class, $adminRole);
    $adminRole->remove_cap(AccessControl::PERMISSION_MANAGE_AUTOMATIONS);

    // init() should NOT restore it because we're not in admin
    $this->caps->init();

    // Capability should still be missing
    $adminRole = get_role('administrator');
    $this->assertInstanceOf(WP_Role::class, $adminRole);
    verify($adminRole->has_cap(AccessControl::PERMISSION_MANAGE_AUTOMATIONS))->false();
  }

  public function testItDoesNotSetupCapabilitiesForNonexistentRoles() {
    $this->caps->removeWPCapabilities();

    $filter = function() {
      return ['nonexistent_role'];
    };
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_permission_access_plugin_admin', $filter);
    $this->caps->setupWPCapabilities();

    // role does not exist
    verify(get_role('nonexistent_role'))->null();

    // other MailPoet capabilities were successfully configured
    $editorRole = get_role('editor');
    $this->assertInstanceOf(WP_Role::class, $editorRole);
    verify($editorRole->has_cap(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN))->false();
    verify($editorRole->has_cap(AccessControl::PERMISSION_MANAGE_EMAILS))->true();

    // Restore capabilities
    $wp->removeFilter('mailpoet_permission_access_plugin_admin', $filter);
    $this->caps->setupWPCapabilities();

    $editorRole = get_role('editor');
    $this->assertInstanceOf(WP_Role::class, $editorRole);
    verify($editorRole->has_cap(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN))->true();
    verify($editorRole->has_cap(AccessControl::PERMISSION_MANAGE_EMAILS))->true();
  }

  public function testItSetsUpMembersCapabilities() {
    $wp = Stub::make(new WPFunctions, [
      'addAction' => asCallable([WPHooksHelper::class, 'addAction']),
    ]);
    $this->caps = new Capabilities((new RendererFactory())->getRenderer(), $wp);

    $this->caps->setupMembersCapabilities();

    $hookName = 'members_register_cap_groups';
    verify(WPHooksHelper::isActionAdded($hookName))->true();
    verify(is_callable(WPHooksHelper::getActionAdded($hookName)[0]))->true();

    $hookName = 'members_register_caps';
    verify(WPHooksHelper::isActionAdded($hookName))->true();
    verify(is_callable(WPHooksHelper::getActionAdded($hookName)[0]))->true();
  }

  public function testItRegistersMembersCapabilities() {
    $permissions = $this->accessControl->getPermissionLabels();
    $permissionCount = count($permissions);
    if (function_exists('members_register_cap')) { // Members plugin active
      $this->caps->registerMembersCapabilities();
      verify(members_get_cap_group(Capabilities::MEMBERS_CAP_GROUP_NAME)->caps)
        ->arrayCount($permissionCount);
    } else {
      $caps = Stub::makeEmptyExcept(
        $this->caps,
        'registerMembersCapabilities',
        [
          'registerMembersCapability' => Expected::exactly($permissionCount),
          'accessControl' => $this->accessControl,
        ],
        $this
      );
      $caps->registerMembersCapabilities();
    }
  }
}
