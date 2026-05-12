<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\WordPress;

use MailPoet\Automation\Integrations\WordPress\ContextFactory;

class ContextFactoryTest extends \MailPoetTest {
  /** @var callable|null */
  private $editableRolesFilter = null;

  public function _after(): void {
    remove_role('automation_safe_role');
    remove_role('automation_elevated_role');
    remove_role('automation_elevated_delete_plugins_role');
    remove_role('automation_elevated_manage_network_role');
    remove_role('automation_elevated_update_core_role');
    remove_role('automation_no_capabilities_role');
    if ($this->editableRolesFilter !== null) {
      remove_filter('editable_roles', $this->editableRolesFilter);
      $this->editableRolesFilter = null;
    }
    wp_set_current_user(0);
  }

  public function testItExposesEditableRoles(): void {
    wp_set_current_user(1);

    $context = $this->diContainer->get(ContextFactory::class)->getContextData();

    $this->assertArrayHasKey('editable_roles', $context);
    $roleIds = array_column($context['editable_roles'], 'id');
    $this->assertContains('subscriber', $roleIds);
  }

  public function testItDoesNotExposeElevatedRoles(): void {
    wp_set_current_user(1);
    add_role('automation_safe_role', 'Automation Safe Role', ['read' => true]);
    add_role('automation_elevated_role', 'Automation Elevated Role', ['unfiltered_html' => true]);
    add_role('automation_elevated_delete_plugins_role', 'Automation Elevated Role', ['delete_plugins' => true]);
    add_role('automation_elevated_manage_network_role', 'Automation Elevated Role', ['manage_network' => true]);
    add_role('automation_elevated_update_core_role', 'Automation Elevated Role', ['update_core' => true]);

    $context = $this->diContainer->get(ContextFactory::class)->getContextData();
    $editableRoles = $context['editable_roles'];
    $this->assertIsArray($editableRoles);
    $roleIds = array_column($editableRoles, 'id');

    $this->assertContains('automation_safe_role', $roleIds);
    $this->assertNotContains('automation_elevated_role', $roleIds);
    $this->assertNotContains('automation_elevated_delete_plugins_role', $roleIds);
    $this->assertNotContains('automation_elevated_manage_network_role', $roleIds);
    $this->assertNotContains('automation_elevated_update_core_role', $roleIds);
    $this->assertNotContains('administrator', $roleIds);
  }

  public function testItDoesNotExposeAdministratorRoleById(): void {
    wp_set_current_user(1);
    $this->editableRolesFilter = function(array $roles): array {
      $roles['administrator'] = [
        'name' => 'Administrator',
        'capabilities' => [
          'read' => true,
        ],
      ];
      return $roles;
    };
    add_filter('editable_roles', $this->editableRolesFilter);

    $context = $this->diContainer->get(ContextFactory::class)->getContextData();
    $this->assertIsArray($context['editable_roles']);

    $this->assertNotContains('administrator', array_column($context['editable_roles'], 'id'));
  }

  public function testItExposesRoleWithoutCapabilities(): void {
    wp_set_current_user(1);
    $this->editableRolesFilter = function(array $roles): array {
      $roles['automation_no_capabilities_role'] = [
        'name' => 'Automation No Capabilities Role',
      ];
      return $roles;
    };
    add_filter('editable_roles', $this->editableRolesFilter);

    $context = $this->diContainer->get(ContextFactory::class)->getContextData();
    $this->assertIsArray($context['editable_roles']);

    $this->assertContains('automation_no_capabilities_role', array_column($context['editable_roles'], 'id'));
  }
}
