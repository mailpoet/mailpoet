<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\WordPress;

use MailPoet\Automation\Integrations\WordPress\ContextFactory;

class ContextFactoryTest extends \MailPoetTest {
  public function _after(): void {
    remove_role('automation_safe_role');
    remove_role('automation_elevated_role');
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

    $context = $this->diContainer->get(ContextFactory::class)->getContextData();
    $editableRoles = $context['editable_roles'];
    $this->assertIsArray($editableRoles);
    $roleIds = array_column($editableRoles, 'id');

    $this->assertContains('automation_safe_role', $roleIds);
    $this->assertNotContains('automation_elevated_role', $roleIds);
    $this->assertNotContains('administrator', $roleIds);
  }
}
