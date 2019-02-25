<?php
namespace MailPoet\Test\Config;

use AspectMock\Test as Mock;
use Codeception\Stub;
use Codeception\Stub\Expected;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Capabilities;
use MailPoet\Config\Renderer;
use MailPoet\WP\Functions as WPFunctions;

class CapabilitiesTest extends \MailPoetTest {

  /** @var AccessControl */
  private $access_control;

  function _before() {
    parent::_before();
    $renderer = new Renderer();
    $this->caps = new Capabilities($renderer);
    $this->access_control = new AccessControl(new WPFunctions());
  }

  function testItInitializes() {
    $caps = Stub::makeEmptyExcept(
      $this->caps,
      'init',
      array('setupMembersCapabilities' => Expected::once()),
      $this
    );
    $caps->init();
  }

  function testItSetsUpWPCapabilities() {
    $permissions = $this->access_control->getDefaultPermissions();
    $this->caps->setupWPCapabilities();
    $checked = false;
    foreach ($permissions as $name => $roles) {
      foreach ($roles as $role) {
        $checked = true;
        expect(get_role($role)->has_cap($name))->true();
      }
    }
    expect($checked)->true();
  }

  function testItRemovesWPCapabilities() {
    $permissions = $this->access_control->getDefaultPermissions();
    $this->caps->removeWPCapabilities();
    $checked = false;
    foreach ($permissions as $name => $roles) {
      foreach ($roles as $role) {
        $checked = true;
        expect(get_role($role)->has_cap($name))->false();
      }
    }
    expect($checked)->true();
    // Restore capabilities
    $this->caps->setupWPCapabilities();
  }

  function testItDoesNotSetupCapabilitiesForNonexistentRoles() {
    $this->caps->removeWPCapabilities();

    $filter = function() {
      return array('nonexistent_role');
    };
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_permission_access_plugin_admin', $filter);
    $this->caps->setupWPCapabilities();

    // role does not exist
    expect(get_role('nonexistent_role'))->null();

    // other MailPoet capabilities were successfully configured
    $editor_role = get_role('editor');
    expect($editor_role->has_cap(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN))->false();
    expect($editor_role->has_cap(AccessControl::PERMISSION_MANAGE_EMAILS))->true();

    // Restore capabilities
    $wp->removeFilter('mailpoet_permission_access_plugin_admin', $filter);
    $this->caps->setupWPCapabilities();

    $editor_role = get_role('editor');
    expect($editor_role->has_cap(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN))->true();
    expect($editor_role->has_cap(AccessControl::PERMISSION_MANAGE_EMAILS))->true();
  }

  function testItSetsUpMembersCapabilities() {
    $wp = Stub::make(new WPFunctions, [
      'addAction' => asCallable([WPHooksHelper::class, 'addAction'])
    ]);
    $this->caps = new Capabilities(new Renderer, $wp);

    $this->caps->setupMembersCapabilities();

    $hook_name = 'members_register_cap_groups';
    expect(WPHooksHelper::isActionAdded($hook_name))->true();
    expect(is_callable(WPHooksHelper::getActionAdded($hook_name)[0]))->true();

    $hook_name = 'members_register_caps';
    expect(WPHooksHelper::isActionAdded($hook_name))->true();
    expect(is_callable(WPHooksHelper::getActionAdded($hook_name)[0]))->true();
  }

  function testItRegistersMembersCapabilities() {
    $permissions = $this->access_control->getPermissionLabels();
    $permission_count = count($permissions);
    if (function_exists('members_register_cap')) { // Members plugin active
      $this->caps->registerMembersCapabilities();
      expect(members_get_cap_group(Capabilities::MEMBERS_CAP_GROUP_NAME)->caps)
        ->count($permission_count);
    } else {
      $caps = Stub::makeEmptyExcept(
        $this->caps,
        'registerMembersCapabilities',
        [
          'registerMembersCapability' => Expected::exactly($permission_count),
          'access_control' => $this->access_control,
        ],
        $this
      );
      $caps->registerMembersCapabilities();
    }
  }

  function _after() {
    Mock::clean();
  }
}
