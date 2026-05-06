<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\WordPress;

use MailPoet\Automation\Integrations\WordPress\ContextFactory;

class ContextFactoryTest extends \MailPoetTest {
  public function testItExposesEditableRoles(): void {
    wp_set_current_user(1);

    $context = $this->diContainer->get(ContextFactory::class)->getContextData();

    $this->assertArrayHasKey('editable_roles', $context);
    $roleIds = array_column($context['editable_roles'], 'id');
    $this->assertContains('subscriber', $roleIds);
  }
}
