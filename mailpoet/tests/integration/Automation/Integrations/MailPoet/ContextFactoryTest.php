<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet;

use MailPoet\Automation\Integrations\MailPoet\ContextFactory;

class ContextFactoryTest extends \MailPoetTest {
  public function testItExposesBlockEmailEditorAvailability(): void {
    $contextFactory = $this->diContainer->get(ContextFactory::class);
    $context = $contextFactory->getContextData();

    $this->assertArrayHasKey('block_email_editor_enabled', $context);
    $this->assertIsBool($context['block_email_editor_enabled']);
  }
}
