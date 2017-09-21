<?php
namespace MailPoet\Test\Config;

use AspectMock\Test as Mock;
use Codeception\Util\Stub;
use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Capabilities;

class CapabilitiesTest extends \MailPoetTest {
  function _before() {
    $this->caps = new Capabilities();
  }

  function testItInitializes() {
    $caps = Stub::makeEmptyExcept(
      $this->caps,
      'init',
      array('setupMembersCapabilities' => Stub::once()),
      $this
    );
    $caps->init();
  }

  function testItSetsUpWPCapabilities() {
    $permissions = AccessControl::getDefaultPermissions();
    $this->caps->setupWPCapabilities();
    $checked = false;
    foreach($permissions as $name => $roles) {
      foreach($roles as $role) {
        $checked = true;
        expect(get_role($role)->has_cap($name))->true();
      }
    }
    expect($checked)->true();
  }

  function testItRemovesWPCapabilities() {
    $permissions = AccessControl::getDefaultPermissions();
    $this->caps->removeWPCapabilities();
    $checked = false;
    foreach($permissions as $name => $roles) {
      foreach($roles as $role) {
        $checked = true;
        expect(get_role($role)->has_cap($name))->false();
      }
    }
    expect($checked)->true();
    // Restore capabilities
    $this->caps->setupWPCapabilities();
  }

  function testItSetsUpMembersCapabilities() {
    WPHooksHelper::interceptAddAction();

    $this->caps->setupMembersCapabilities();

    $hook_name = 'members_register_cap_groups';
    expect(WPHooksHelper::isActionAdded($hook_name))->true();
    expect(is_callable(WPHooksHelper::getActionAdded($hook_name)[0]))->true();

    $hook_name = 'members_register_caps';
    expect(WPHooksHelper::isActionAdded($hook_name))->true();
    expect(is_callable(WPHooksHelper::getActionAdded($hook_name)[0]))->true();
  }

  function testItRegistersMembersCapGroup() {
    if(function_exists('members_register_cap_group')) { // Members plugin active
      $this->caps->registerMembersCapGroup();
      expect_that(members_cap_group_exists(Capabilities::MEMBERS_CAP_GROUP_NAME));
    } else {
      $func = Mock::func('MailPoet\Config', 'members_register_cap_group', true);
      $this->caps->registerMembersCapGroup();
      $func->verifyInvoked([Capabilities::MEMBERS_CAP_GROUP_NAME]);
    }
  }

  function testItRegistersMembersCapabilities() {
    $permissions = AccessControl::getPermissionLabels();
    $permission_count = count($permissions);
    if(function_exists('members_register_cap')) { // Members plugin active
      $this->caps->registerMembersCapabilities();
      expect(members_get_cap_group(Capabilities::MEMBERS_CAP_GROUP_NAME)->caps)
        ->count($permission_count);
    } else {
      $func = Mock::func('MailPoet\Config', 'members_register_cap', true);
      $this->caps->registerMembersCapabilities();
      $func->verifyInvokedMultipleTimes($permission_count);
    }
  }

  function _after() {
    WPHooksHelper::releaseAllHooks();
    Mock::clean();
  }
}
