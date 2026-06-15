<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Templates;

use MailPoet\Automation\Engine\Data\AutomationTemplate;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Templates\AutomationBuilder;
use MailPoet\Automation\Integrations\MailPoet\Templates\EmailFactory;
use MailPoet\Automation\Integrations\MailPoet\Templates\TemplatesFactory;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\AbandonedCart\AbandonedCartTrigger;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\BuysAProductTrigger;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\BuysFromACategoryTrigger;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\BuysFromATagTrigger;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\Orders\OrderCompletedTrigger;
use MailPoet\Automation\Integrations\WooCommerce\WooCommerce;
use MailPoet\Validator\Schema\ObjectSchema;
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

  public function testWinBackCustomersTemplateIsNotReturned(): void {
    $factory = $this->createFactory();

    $this->assertNull($this->findTemplateBySlug($factory->createTemplates(), 'win-back-customers'));
  }

  /**
   * The first purchase template filters on WooCommerce order fields, which can
   * only be built with an active WooCommerce, so these tests run in the woo jobs.
   *
   * @group woo
   * @dataProvider postPurchaseTemplateEmailData
   */
  public function testPostPurchaseTemplateCreatesBlockEditorEmail(
    string $slug,
    string $expectedTriggerKey,
    string $expectedPattern,
    string $expectedName,
    string $expectedSubject,
    string $expectedPreheader
  ): void {
    $this->ensureWooCommerceTriggerRegistered($expectedTriggerKey);
    $this->emailFactory->expects($this->once())
      ->method('createBlockEditorEmail')
      ->with([
        'pattern' => $expectedPattern,
        'subject' => $expectedSubject,
        'preheader' => $expectedPreheader,
      ])
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
    $this->assertInstanceOf(Step::class, $sendEmailStep);
    $args = $sendEmailStep->getArgs();

    $this->assertSame($expectedName, $args['name']);
    $this->assertSame($expectedSubject, $args['subject']);
    $this->assertSame($expectedPreheader, $args['preheader']);
    $this->assertSame(123, $args['email_id']);
    $this->assertSame(456, $args['email_wp_post_id']);
  }

  /**
   * @group woo
   * @dataProvider postPurchaseTemplateEmailData
   */
  public function testPostPurchaseTemplatePreviewDoesNotCreatePersistentEmail(
    string $slug,
    string $expectedTriggerKey,
    string $expectedPattern,
    string $expectedName,
    string $expectedSubject,
    string $expectedPreheader
  ): void {
    $this->ensureWooCommerceTriggerRegistered($expectedTriggerKey);
    $this->emailFactory->expects($this->never())->method('createBlockEditorEmail');

    $factory = $this->createFactory();
    $template = $this->findTemplateBySlug($factory->createTemplates(), $slug);
    $this->assertInstanceOf(AutomationTemplate::class, $template);

    $automation = $template->createAutomation(true);

    $this->assertNotNull($this->getFirstStepByKey($automation->getSteps(), $expectedTriggerKey));
    $sendEmailStep = $this->getFirstStepByKey($automation->getSteps(), 'mailpoet:send-email');
    $this->assertInstanceOf(Step::class, $sendEmailStep);
    $args = $sendEmailStep->getArgs();

    $this->assertSame($expectedName, $args['name']);
    $this->assertSame($expectedSubject, $args['subject']);
    $this->assertSame($expectedPreheader, $args['preheader']);
    $this->assertArrayNotHasKey('email_id', $args);
    $this->assertArrayNotHasKey('email_wp_post_id', $args);
  }

  public function postPurchaseTemplateEmailData(): array {
    return [
      'first purchase' => [
        'first-purchase',
        'woocommerce:order-completed',
        'first-purchase-thank-you',
        'First purchase thank you',
        'Thank you for your first order!',
        'Welcome to the family! Check out what’s next for you.',
      ],
      'purchased product' => [
        'purchased-product',
        'woocommerce:buys-a-product',
        'product-purchase-follow-up',
        'Important information about your order',
        'Important information about your order',
        'A few details about your purchase',
      ],
      'purchased product with tag' => [
        'purchased-product-with-tag',
        'woocommerce:buys-from-a-tag',
        'tag-purchase-follow-up',
        'Important information about your order',
        'Important information about your order',
        'A few details about your purchase',
      ],
      'purchased in category' => [
        'purchased-in-category',
        'woocommerce:buys-from-a-category',
        'category-purchase-follow-up',
        'Important information about your order',
        'Important information about your order',
        'A few details about your purchase',
      ],
    ];
  }

  /**
   * @group woo
   */
  public function testThankLoyalCustomersTemplateCreatesBlockEditorEmail(): void {
    $this->ensureWooCommerceTriggerRegistered('woocommerce:order-completed');
    $this->emailFactory->expects($this->once())
      ->method('createBlockEditorEmail')
      ->with([
        'pattern' => 'post-purchase-thank-you',
        'subject' => 'Thank you for your loyalty',
        'preheader' => 'We appreciate your continued support',
      ])
      ->willReturn([
        'email_id' => 123,
        'email_wp_post_id' => 456,
      ]);

    $factory = $this->createFactory();
    $template = $this->findTemplateBySlug($factory->createTemplates(), 'thank-loyal-customers');
    $this->assertInstanceOf(AutomationTemplate::class, $template);

    $automation = $template->createAutomation();

    $this->assertNotNull($this->getFirstStepByKey($automation->getSteps(), 'woocommerce:order-completed'));
    $sendEmailStep = $this->getFirstStepByKey($automation->getSteps(), 'mailpoet:send-email');
    $this->assertInstanceOf(Step::class, $sendEmailStep);
    $args = $sendEmailStep->getArgs();

    $this->assertSame('Thank you for your loyalty', $args['name']);
    $this->assertSame('Thank you for your loyalty', $args['subject']);
    $this->assertSame('We appreciate your continued support', $args['preheader']);
    $this->assertSame(123, $args['email_id']);
    $this->assertSame(456, $args['email_wp_post_id']);
  }

  /**
   * @group woo
   */
  public function testThankLoyalCustomersTemplatePreviewDoesNotCreatePersistentEmail(): void {
    $this->ensureWooCommerceTriggerRegistered('woocommerce:order-completed');
    $this->emailFactory->expects($this->never())->method('createBlockEditorEmail');

    $factory = $this->createFactory();
    $template = $this->findTemplateBySlug($factory->createTemplates(), 'thank-loyal-customers');
    $this->assertInstanceOf(AutomationTemplate::class, $template);

    $automation = $template->createAutomation(true);

    $sendEmailStep = $this->getFirstStepByKey($automation->getSteps(), 'mailpoet:send-email');
    $this->assertInstanceOf(Step::class, $sendEmailStep);
    $args = $sendEmailStep->getArgs();

    $this->assertSame('Thank you for your loyalty', $args['name']);
    $this->assertSame('Thank you for your loyalty', $args['subject']);
    $this->assertSame('We appreciate your continued support', $args['preheader']);
    $this->assertArrayNotHasKey('email_id', $args);
    $this->assertArrayNotHasKey('email_wp_post_id', $args);
  }

  public function testBirthdayEmailTemplateCreatesBlockEditorEmail(): void {
    $this->ensureAnnualDateTriggerRegistered();
    $this->emailFactory->expects($this->once())
      ->method('createBlockEditorEmail')
      ->with([
        'pattern' => 'birthday-email-with-discount',
        'subject' => 'A birthday treat from us',
        'preheader' => 'Enjoy 10% off your next order',
      ])
      ->willReturn([
        'email_id' => 123,
        'email_wp_post_id' => 456,
      ]);

    $factory = $this->createFactory();
    $template = $this->findTemplateBySlug($factory->createTemplates(), 'birthday-email');
    $this->assertInstanceOf(AutomationTemplate::class, $template);

    $automation = $template->createAutomation();

    $this->assertNotNull($this->getFirstStepByKey($automation->getSteps(), 'mailpoet:annual-date'));
    $this->assertNull($automation->getMeta('mailpoet:run-once-per-subscriber'));
    $sendEmailStep = $this->getFirstStepByKey($automation->getSteps(), 'mailpoet:send-email');
    $this->assertInstanceOf(Step::class, $sendEmailStep);
    $args = $sendEmailStep->getArgs();

    $this->assertSame('A birthday treat from us', $args['name']);
    $this->assertSame('A birthday treat from us', $args['subject']);
    $this->assertSame('Enjoy 10% off your next order', $args['preheader']);
    $this->assertSame(123, $args['email_id']);
    $this->assertSame(456, $args['email_wp_post_id']);
  }

  public function testBirthdayEmailTemplateUsesPlainPatternWithoutGeneratedCouponSupport(): void {
    $this->ensureAnnualDateTriggerRegistered();
    $this->emailFactory->expects($this->once())
      ->method('createBlockEditorEmail')
      ->with([
        'pattern' => 'birthday-email-content',
        'subject' => 'Happy birthday!',
        'preheader' => 'Wishing you a wonderful day',
      ])
      ->willReturn([
        'email_id' => 123,
        'email_wp_post_id' => 456,
      ]);

    $factory = $this->createFactory(true, '10.7.0');
    $template = $this->findTemplateBySlug($factory->createTemplates(), 'birthday-email');
    $this->assertInstanceOf(AutomationTemplate::class, $template);

    $automation = $template->createAutomation();
    $sendEmailStep = $this->getFirstStepByKey($automation->getSteps(), 'mailpoet:send-email');
    $this->assertInstanceOf(Step::class, $sendEmailStep);
    $args = $sendEmailStep->getArgs();

    $this->assertSame('Happy birthday!', $args['name']);
    $this->assertSame('Happy birthday!', $args['subject']);
    $this->assertSame('Wishing you a wonderful day', $args['preheader']);
    $this->assertSame(123, $args['email_id']);
    $this->assertSame(456, $args['email_wp_post_id']);
  }

  public function testBirthdayEmailTemplatePreviewDoesNotCreatePersistentEmail(): void {
    $this->ensureAnnualDateTriggerRegistered();
    $this->emailFactory->expects($this->never())->method('createBlockEditorEmail');

    $factory = $this->createFactory();
    $template = $this->findTemplateBySlug($factory->createTemplates(), 'birthday-email');
    $this->assertInstanceOf(AutomationTemplate::class, $template);

    $automation = $template->createAutomation(true);
    $this->assertNotNull($this->getFirstStepByKey($automation->getSteps(), 'mailpoet:annual-date'));
    $this->assertNull($automation->getMeta('mailpoet:run-once-per-subscriber'));
    $sendEmailStep = $this->getFirstStepByKey($automation->getSteps(), 'mailpoet:send-email');
    $this->assertInstanceOf(Step::class, $sendEmailStep);
    $args = $sendEmailStep->getArgs();

    $this->assertSame('A birthday treat from us', $args['name']);
    $this->assertSame('A birthday treat from us', $args['subject']);
    $this->assertSame('Enjoy 10% off your next order', $args['preheader']);
    $this->assertArrayNotHasKey('email_id', $args);
    $this->assertArrayNotHasKey('email_wp_post_id', $args);
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
  // createFromSequence() silently drop the trigger step. Re-register them so these tests do not
  // depend on suite ordering.
  private function ensureAbandonedCartTriggerRegistered(): void {
    $registry = $this->diContainer->get(Registry::class);
    if ($registry->getStep(AbandonedCartTrigger::KEY) === null) {
      $registry->addTrigger($this->diContainer->get(AbandonedCartTrigger::class));
    }
  }

  private function ensureWooCommerceTriggerRegistered(string $triggerKey): void {
    $triggerClasses = [
      'woocommerce:order-completed' => OrderCompletedTrigger::class,
      BuysAProductTrigger::KEY => BuysAProductTrigger::class,
      BuysFromATagTrigger::KEY => BuysFromATagTrigger::class,
      BuysFromACategoryTrigger::KEY => BuysFromACategoryTrigger::class,
    ];
    $registry = $this->diContainer->get(Registry::class);
    if ($registry->getStep($triggerKey) === null) {
      $registry->addTrigger($this->diContainer->get($triggerClasses[$triggerKey]));
    }
  }

  private function ensureAnnualDateTriggerRegistered(): void {
    $registry = $this->diContainer->get(Registry::class);
    if ($registry->getStep('mailpoet:annual-date') === null) {
      $registry->addTrigger(new class implements Trigger {
        public function getKey(): string {
          return 'mailpoet:annual-date';
        }

        public function getName(): string {
          return 'Annual date';
        }

        public function getArgsSchema(): ObjectSchema {
          return new ObjectSchema();
        }

        public function getSubjectKeys(): array {
          return [];
        }

        public function validate(StepValidationArgs $args): void {
        }

        public function registerHooks(): void {
        }

        public function isTriggeredBy(StepRunArgs $args): bool {
          return true;
        }
      });
    }
  }

  private function createFactory(bool $woocommerceActive = true, string $wooCommerceVersion = '10.8.0'): TemplatesFactory {
    $woocommerce = $this->createMock(WooCommerce::class);
    $woocommerce->method('isWooCommerceActive')->willReturn($woocommerceActive);
    $woocommerceSubscriptions = $this->createMock(WooCommerceSubscriptions::class);
    $woocommerceSubscriptions->method('isWooCommerceSubscriptionsActive')->willReturn(false);
    $bookingsHelper = $this->createMock(WooCommerceBookingsHelper::class);
    $bookingsHelper->method('isWooCommerceBookingsActive')->willReturn(false);
    $woocommerceHelper = $this->createMock(WooCommerceHelper::class);
    $woocommerceHelper->method('wcSupportsOrderReviewUrl')->willReturn(false);
    $woocommerceHelper->method('getWooCommerceVersion')->willReturn($wooCommerceVersion);

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
