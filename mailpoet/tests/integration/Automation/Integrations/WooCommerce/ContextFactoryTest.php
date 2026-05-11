<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\WooCommerce;

use MailPoet\Automation\Integrations\WooCommerce\ContextFactory;

/**
 * @group woo
 */
class ContextFactoryTest extends \MailPoetTest {
  public function testItExposesPaidStatuses(): void {
    $context = $this->diContainer->get(ContextFactory::class)->getContextData();

    $this->assertArrayHasKey('paid_statuses', $context);
    $this->assertSame(wc_get_is_paid_statuses(), $context['paid_statuses']);
  }
}
