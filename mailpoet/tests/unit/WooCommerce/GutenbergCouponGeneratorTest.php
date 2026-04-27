<?php declare(strict_types = 1);

namespace unit\WooCommerce;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Codeception\Stub;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\WooCommerce\GutenbergCouponGenerationFailureCollector;
use MailPoet\WooCommerce\GutenbergCouponGenerator;
use MailPoet\WooCommerce\GutenbergCouponValidator;
use MailPoet\WooCommerce\Helper;
use MailPoet\WP\Functions as WPFunctions;

class GutenbergCouponGeneratorTest extends \MailPoetUnitTest {
  public function testItRegistersWooCommerceFilter(): void {
    $wp = $this->make(WPFunctions::class, [
      'addFilter' => function($hook, $callback, $priority, $acceptedArgs) {
        verify($hook)->equals('woocommerce_coupon_code_block_auto_generate');
        verify($callback)->isArray();
        verify($priority)->equals(5);
        verify($acceptedArgs)->equals(3);
        return true;
      },
    ]);

    $generator = new GutenbergCouponGenerator(
      Stub::make(Helper::class),
      Stub::make(GutenbergCouponValidator::class),
      new GutenbergCouponGenerationFailureCollector(),
      $wp
    );

    $generator->init();
  }

  public function testItReturnsExistingCouponCodeUnchanged(): void {
    $collector = new GutenbergCouponGenerationFailureCollector();
    $generator = new GutenbergCouponGenerator(
      Stub::make(Helper::class),
      Stub::make(GutenbergCouponValidator::class),
      $collector,
      Stub::make(WPFunctions::class)
    );

    $result = $generator->generate('EXISTING', ['source' => 'createNew'], $this->createRenderingContext([]));

    verify($result)->equals('EXISTING');
    verify($collector->hasFailures())->false();
  }

  public function testItReturnsPlaceholderForMailPoetPreviewToPreventWooCommerceDefaultGeneration(): void {
    $collector = new GutenbergCouponGenerationFailureCollector();
    $generator = new GutenbergCouponGenerator(
      Stub::make(Helper::class),
      Stub::make(GutenbergCouponValidator::class),
      $collector,
      Stub::make(WPFunctions::class)
    );

    $result = $generator->generate('', ['source' => 'createNew'], $this->createRenderingContext([
      'integration' => 'mailpoet',
      'is_real_send' => false,
      'is_preview' => true,
    ]));

    verify($result)->equals(GutenbergCouponGenerator::SAFE_PLACEHOLDER);
    verify($collector->hasFailures())->false();
  }

  public function testItGeneratesCouponForPositiveOneRecipientAutomationContext(): void {
    $generatedCode = null;
    $emailRestrictions = null;
    $coupon = $this->getMockBuilder(\stdClass::class)
      ->addMethods([
        'set_code',
        'set_description',
        'set_discount_type',
        'set_amount',
        'set_date_expires',
        'set_free_shipping',
        'set_minimum_amount',
        'set_maximum_amount',
        'set_individual_use',
        'set_exclude_sale_items',
        'set_product_ids',
        'set_excluded_product_ids',
        'set_product_categories',
        'set_excluded_product_categories',
        'set_email_restrictions',
        'set_usage_limit',
        'set_usage_limit_per_user',
        'save',
      ])
      ->getMock();
    $coupon->method('set_code')->willReturnCallback(function($code) use (&$generatedCode): void {
      $generatedCode = $code;
    });
    $coupon->method('set_email_restrictions')->willReturnCallback(function($restrictions) use (&$emailRestrictions): void {
      $emailRestrictions = $restrictions;
    });
    $coupon->method('save')->willReturn(123);

    $collector = new GutenbergCouponGenerationFailureCollector();
    $generator = new GutenbergCouponGenerator(
      $this->make(Helper::class, [
        'isWooCommerceActive' => true,
        'wcGetCouponTypes' => ['percent' => 'Percentage discount'],
        'wcGetCouponIdByCode' => 0,
        'createWcCoupon' => $coupon,
      ]),
      new GutenbergCouponValidator(
        $this->make(Helper::class, [
          'wcGetCouponTypes' => ['percent' => 'Percentage discount'],
        ]),
        $this->makeWpFunctions()
      ),
      $collector,
      $this->makeWpFunctions()
    );

    $result = $generator->generate('', [
      'source' => 'createNew',
      'discountType' => 'percent',
      'amount' => '25',
      'restrictToSubscriber' => true,
    ], $this->createPositiveRenderingContext());

    $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{6}-[A-Z0-9]{4}$/', $result);
    verify($generatedCode)->equals($result);
    verify($emailRestrictions)->equals(['subscriber@example.com']);
    verify($collector->hasFailures())->false();
  }

  public function testItRecordsFailureAndReturnsPlaceholderForInvalidAttributes(): void {
    $collector = new GutenbergCouponGenerationFailureCollector();
    $helper = $this->make(Helper::class, [
      'isWooCommerceActive' => true,
      'wcGetCouponTypes' => ['percent' => 'Percentage discount'],
    ]);
    $generator = new GutenbergCouponGenerator(
      $helper,
      new GutenbergCouponValidator($helper, $this->makeWpFunctions()),
      $collector,
      $this->makeWpFunctions()
    );

    $result = $generator->generate('', [
      'source' => 'createNew',
      'discountType' => 'percent',
      'amount' => '101',
    ], $this->createPositiveRenderingContext());

    verify($result)->equals(GutenbergCouponGenerator::SAFE_PLACEHOLDER);
    verify($collector->hasFailures())->true();
  }

  private function createPositiveRenderingContext(): Rendering_Context {
    return $this->createRenderingContext([
      'integration' => 'mailpoet',
      'recipient_email' => 'subscriber@example.com',
      'newsletter_id' => 1,
      'queue_id' => 2,
      'email_type' => NewsletterEntity::TYPE_AUTOMATION,
      'is_real_send' => true,
      'is_preview' => false,
      'is_single_recipient' => true,
      'subscriber_count' => 1,
      'mailpoet_is_automation' => true,
    ]);
  }

  private function createRenderingContext(array $context): Rendering_Context {
    $renderingContext = $this->createMock(Rendering_Context::class);
    $renderingContext->method('get_email_context')->willReturn($context);
    $renderingContext->method('get_recipient_email')->willReturn($context['recipient_email'] ?? null);
    return $renderingContext;
  }

  private function makeWpFunctions(): WPFunctions {
    return $this->make(WPFunctions::class, [
      'isEmail' => function($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
      },
      'sanitizeEmail' => function(string $email): string {
        return trim($email);
      },
    ]);
  }
}
