<?php

namespace MailPoet\Test\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Capabilities;
use MailPoet\Config\Renderer;
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

  public function testItInitializes() {
    $caps = Stub::makeEmptyExcept(
      $this->caps,
      'init',
      ['setupMembersCapabilities' => Expected::once()],
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
        assert($role instanceof WP_Role);
        expect($role->has_cap($name))->true();
      }
    }
    expect($checked)->true();
  }

  public function testItRemovesWPCapabilities() {
    $permissions = $this->accessControl->getDefaultPermissions();
    $this->caps->removeWPCapabilities();
    $checked = false;
    foreach ($permissions as $name => $roles) {
      foreach ($roles as $role) {
        $checked = true;
        $role = get_role($role);
        assert($role instanceof WP_Role);
        expect($role->has_cap($name))->false();
      }
    }
    expect($checked)->true();
    // Restore capabilities
    $this->caps->setupWPCapabilities();
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
    expect(get_role('nonexistent_role'))->null();

    // other MailPoet capabilities were successfully configured
    $editorRole = get_role('editor');
    assert($editorRole instanceof WP_Role);
    expect($editorRole->has_cap(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN))->false();
    expect($editorRole->has_cap(AccessControl::PERMISSION_MANAGE_EMAILS))->true();

    // Restore capabilities
    $wp->removeFilter('mailpoet_permission_access_plugin_admin', $filter);
    $this->caps->setupWPCapabilities();

    $editorRole = get_role('editor');
    assert($editorRole instanceof WP_Role);
    expect($editorRole->has_cap(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN))->true();
    expect($editorRole->has_cap(AccessControl::PERMISSION_MANAGE_EMAILS))->true();
  }

  public function testItSetsUpMembersCapabilities() {
    $wp = Stub::make(new WPFunctions, [
      'addAction' => asCallable([WPHooksHelper::class, 'addAction']),
    ]);
    $this->caps = new Capabilities((new RendererFactory())->getRenderer(), $wp);

    $this->caps->setupMembersCapabilities();

    $hookName = 'members_register_cap_groups';
    expect(WPHooksHelper::isActionAdded($hookName))->true();
    expect(is_callable(WPHooksHelper::getActionAdded($hookName)[0]))->true();

    $hookName = 'members_register_caps';
    expect(WPHooksHelper::isActionAdded($hookName))->true();
    expect(is_callable(WPHooksHelper::getActionAdded($hookName)[0]))->true();
  }

  public function testItRegistersMembersCapabilities() {
    $permissions = $this->accessControl->getPermissionLabels();
    $permissionCount = count($permissions);
    if (function_exists('members_register_cap')) { // Members plugin active
      $this->caps->registerMembersCapabilities();
      expect(members_get_cap_group(Capabilities::MEMBERS_CAP_GROUP_NAME)->caps)
        ->count($permissionCount);
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
