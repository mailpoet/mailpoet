<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Data\AutomationTemplate;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Templates\AutomationBuilder;
use MailPoet\Automation\Integrations\MailPoet\Templates\EmailFactory;
use MailPoet\Automation\Integrations\MailPoet\Templates\TemplatesFactory;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\AbandonedCart\AbandonedCartTrigger;
use MailPoet\Automation\Integrations\WooCommerce\WooCommerce;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\WooCommerceBookings\Helper as WooCommerceBookingsHelper;
use MailPoet\WooCommerce\WooCommerceSubscriptions\Helper as WooCommerceSubscriptions;
use MailPoetTest;
use PHPUnit\Framework\MockObject\MockObject;

class TemplatesFactoryTest extends MailPoetTest {
  /** @var AutomationBuilder */
  private $builder;

  /** @var MockObject&EmailFactory */
  private $emailFactory;

  public function _before(): void {
    parent::_before();
    $this->builder = $this->diContainer->get(AutomationBuilder::class);
    $this->emailFactory = $this->createMock(EmailFactory::class);
  }

  /**
   * @dataProvider welcomeTemplateEmailData
   */
  public function testWelcomeTemplateCreatesBlockEditorEmail(
    string $slug,
    string $expectedTriggerKey,
    string $expectedName,
    string $expectedSubject,
    string $expectedPreheader
  ): void {
    $this->emailFactory->expects($this->once())
      ->method('createBlockEditorEmail')
      ->with($this->callback(function (array $data) use ($expectedSubject, $expectedPreheader): bool {
        return ($data['pattern'] ?? null) === 'welcome-email-content'
          && ($data['subject'] ?? null) === $expectedSubject
          && ($data['preheader'] ?? null) === $expectedPreheader;
      }))
      ->willReturn([
        'email_id' => 123,
        'email_wp_post_id' => 456,
      ]);

    $factory = $this->createFactory();
    $template = $this->findTemplateBySlug($factory->createTemplates(), $slug);
    $this->assertInstanceOf(AutomationTemplate::class, $template);

    $automation = $template->createAutomation();

    $this->assertNotNull($this->getFirstStepByKey($automation->getSteps(), $expectedTriggerKey));
    $sendEmailStep = $this->getFirstStepByKey($automation->getSteps(), 'mailpoet:send-email');
    $this->assertNotNull($sendEmailStep);

    $args = $sendEmailStep->getArgs();
    $this->assertSame($expectedName, $args['name']);
    $this->assertSame($expectedSubject, $args['subject']);
    $this->assertSame($expectedPreheader, $args['preheader']);
    $this->assertSame(123, $args['email_id']);
    $this->assertSame(456, $args['email_wp_post_id']);
  }

  /**
   * @dataProvider welcomeTemplateEmailData
   */
  public function testWelcomeTemplatePreviewDoesNotCreatePersistentEmail(
    string $slug,
    string $expectedTriggerKey,
    string $expectedName,
    string $expectedSubject,
    string $expectedPreheader
  ): void {
    $this->emailFactory->expects($this->never())->method('createBlockEditorEmail');

    $factory = $this->createFactory();
    $template = $this->findTemplateBySlug($factory->createTemplates(), $slug);
    $this->assertInstanceOf(AutomationTemplate::class, $template);

    $automation = $template->createAutomation(true);

    $this->assertNotNull($this->getFirstStepByKey($automation->getSteps(), $expectedTriggerKey));
    $sendEmailStep = $this->getFirstStepByKey($automation->getSteps(), 'mailpoet:send-email');
    $this->assertNotNull($sendEmailStep);

    $args = $sendEmailStep->getArgs();
    $this->assertSame($expectedName, $args['name']);
    $this->assertSame($expectedSubject, $args['subject']);
    $this->assertSame($expectedPreheader, $args['preheader']);
    $this->assertArrayNotHasKey('email_id', $args);
    $this->assertArrayNotHasKey('email_wp_post_id', $args);
  }

  public function welcomeTemplateEmailData(): array {
    return [
      'subscriber welcome email' => [
        'subscriber-welcome-email',
        'mailpoet:someone-subscribes',
        'Welcome email',
        'Welcome to our community!',
        'Thanks for subscribing',
      ],
      'user welcome email' => [
        'user-welcome-email',
        'mailpoet:wp-user-registered',
        'Welcome email',
        'Welcome to our community!',
        'Thanks for joining us',
      ],
    ];
  }

  public function testAbandonedCartTemplateCreatesBlockEditorEmail(): void {
    $this->ensureAbandonedCartTriggerRegistered();
    $this->emailFactory->expects($this->once())
      ->method('createBlockEditorEmail')
      ->with([
        'pattern' => 'abandoned-cart-content',
        'subject' => 'You left something behind!',
        'preheader' => 'Complete your purchase today',
      ])
      ->willReturn([
        'email_id' => 123,
        'email_wp_post_id' => 456,
      ]);

    $factory = $this->createFactory();
    $template = $this->findTemplateBySlug($factory->createTemplates(), 'abandoned-cart');
    $this->assertInstanceOf(AutomationTemplate::class, $template);

    $automation = $template->createAutomation();
    $this->assertNotNull($this->getFirstStepByKey($automation->getSteps(), 'woocommerce:abandoned-cart'));

    $sendEmailStep = $this->getFirstStepByKey($automation->getSteps(), 'mailpoet:send-email');
    $this->assertInstanceOf(Step::class, $sendEmailStep);
    $args = $sendEmailStep->getArgs();

    $this->assertSame('Abandoned cart reminder', $args['name']);
    $this->assertSame('You left something behind!', $args['subject']);
    $this->assertSame('Complete your purchase today', $args['preheader']);
    $this->assertSame(123, $args['email_id']);
    $this->assertSame(456, $args['email_wp_post_id']);
  }

  public function testAbandonedCartTemplatePreviewDoesNotCreatePersistentEmail(): void {
    $this->emailFactory->expects($this->never())->method('createBlockEditorEmail');

    $factory = $this->createFactory();
    $template = $this->findTemplateBySlug($factory->createTemplates(), 'abandoned-cart');
    $this->assertInstanceOf(AutomationTemplate::class, $template);

    $automation = $template->createAutomation(true);
    $sendEmailStep = $this->getFirstStepByKey($automation->getSteps(), 'mailpoet:send-email');
    $this->assertInstanceOf(Step::class, $sendEmailStep);
    $args = $sendEmailStep->getArgs();

    $this->assertSame('Abandoned cart reminder', $args['name']);
    $this->assertSame('You left something behind!', $args['subject']);
    $this->assertSame('Complete your purchase today', $args['preheader']);
    $this->assertArrayNotHasKey('email_id', $args);
    $this->assertArrayNotHasKey('email_wp_post_id', $args);
  }

  // WooCommerce automation triggers are registered once into the shared Registry at engine init.
  // A test running earlier in the suite can leave the Registry without them, which would make
  // createFromSequence() silently drop the trigger step. Re-register it so this test does not
  // depend on suite ordering.
  private function ensureAbandonedCartTriggerRegistered(): void {
    $registry = $this->diContainer->get(Registry::class);
    if ($registry->getStep(AbandonedCartTrigger::KEY) === null) {
      $registry->addTrigger($this->diContainer->get(AbandonedCartTrigger::class));
    }
  }

  private function createFactory(): TemplatesFactory {
    $woocommerce = $this->createMock(WooCommerce::class);
    $woocommerce->method('isWooCommerceActive')->willReturn(true);
    $woocommerceSubscriptions = $this->createMock(WooCommerceSubscriptions::class);
    $woocommerceSubscriptions->method('isWooCommerceSubscriptionsActive')->willReturn(false);
    $bookingsHelper = $this->createMock(WooCommerceBookingsHelper::class);
    $bookingsHelper->method('isWooCommerceBookingsActive')->willReturn(false);
    $woocommerceHelper = $this->createMock(WooCommerceHelper::class);
    $woocommerceHelper->method('wcSupportsOrderReviewUrl')->willReturn(false);

    return new TemplatesFactory(
      $this->builder,
      $woocommerce,
      $woocommerceSubscriptions,
      $this->emailFactory,
      $bookingsHelper,
      $woocommerceHelper
    );
  }

  /**
   * @param AutomationTemplate[] $templates
   */
  private function findTemplateBySlug(array $templates, string $slug): ?AutomationTemplate {
    foreach ($templates as $template) {
      if ($template->getSlug() === $slug) {
        return $template;
      }
    }
    return null;
  }

  /**
   * @param Step[] $steps
   */
  private function getFirstStepByKey(array $steps, string $key): ?Step {
    $matches = array_values(array_filter($steps, fn(Step $step) => $step->getKey() === $key));
    return $matches[0] ?? null;
  }
}
