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

  public function testItAcceptsNullCouponCodeFromEarlierFilters(): void {
    $collector = new GutenbergCouponGenerationFailureCollector();
    $generator = new GutenbergCouponGenerator(
      Stub::make(Helper::class),
      Stub::make(GutenbergCouponValidator::class),
      $collector,
      Stub::make(WPFunctions::class)
    );

    $result = $generator->generate(null, ['source' => 'createNew'], $this->createRenderingContext([
      'integration' => 'mailpoet',
      'is_real_send' => false,
      'is_preview' => true,
    ]));

    verify($result)->equals(GutenbergCouponGenerator::SAFE_PLACEHOLDER);
    verify($collector->hasFailures())->false();
  }

  public function testItTreatsMissingSourceWithExistingCouponCodeAsStaticCoupon(): void {
    $collector = new GutenbergCouponGenerationFailureCollector();
    $generator = new GutenbergCouponGenerator(
      Stub::make(Helper::class),
      Stub::make(GutenbergCouponValidator::class),
      $collector,
      Stub::make(WPFunctions::class)
    );

    $result = $generator->generate('', ['couponCode' => 'WELCOME10'], $this->createStandardRenderingContext());

    verify($result)->equals(GutenbergCouponGenerator::SAFE_PLACEHOLDER);
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
    /** @var object{generatedCode: string|null, emailRestrictions: array|null} $coupon */
    $coupon = $this->createCouponStub();

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
    verify($coupon->generatedCode)->equals($result);
    verify($coupon->emailRestrictions)->equals(['subscriber@example.com']);
    verify($collector->hasFailures())->false();
  }

  public function testItGeneratesCouponForStandardNewsletterContext(): void {
    /** @var object{generatedCode: string|null, emailRestrictions: array|null} $coupon */
    $coupon = $this->createCouponStub();

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
      'amount' => '10',
    ], $this->createStandardRenderingContext());

    $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{6}-[A-Z0-9]{4}$/', $result);
    verify($coupon->generatedCode)->equals($result);
    verify($coupon->emailRestrictions)->equals([]);
    verify($collector->hasFailures())->false();
  }

  public function testItRejectsRecipientRestrictionForStandardNewsletterContext(): void {
    $collector = new GutenbergCouponGenerationFailureCollector();
    $generator = new GutenbergCouponGenerator(
      $this->make(Helper::class, [
        'isWooCommerceActive' => true,
      ]),
      Stub::make(GutenbergCouponValidator::class),
      $collector,
      $this->makeWpFunctions()
    );

    $result = $generator->generate('', [
      'source' => 'createNew',
      'discountType' => 'percent',
      'amount' => '10',
      'restrictToSubscriber' => true,
    ], $this->createStandardRenderingContext());

    verify($result)->equals(GutenbergCouponGenerator::SAFE_PLACEHOLDER);
    verify($collector->hasFailures())->true();
    verify($collector->getFailures()[0]['message'])->equals('Recipient-restricted generated coupons are only supported in automation emails sent to one subscriber at a time.');
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

  public function testItRecordsFailureWhenUniqueCodeGenerationIsExhausted(): void {
    $collector = new GutenbergCouponGenerationFailureCollector();
    $helper = $this->make(Helper::class, [
      'isWooCommerceActive' => true,
      'wcGetCouponTypes' => ['percent' => 'Percentage discount'],
      'wcGetCouponIdByCode' => 123,
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
      'amount' => '15',
    ], $this->createPositiveRenderingContext());

    verify($result)->equals(GutenbergCouponGenerator::SAFE_PLACEHOLDER);
    verify($collector->getFailures()[0]['message'])->equals('Failed to generate a unique coupon code.');
  }

  public function testItRecordsFailureWhenWooCommerceDoesNotSaveCoupon(): void {
    $collector = new GutenbergCouponGenerationFailureCollector();
    $helper = $this->make(Helper::class, [
      'isWooCommerceActive' => true,
      'wcGetCouponTypes' => ['percent' => 'Percentage discount'],
      'wcGetCouponIdByCode' => 0,
      'createWcCoupon' => $this->createCouponStub(0),
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
      'amount' => '15',
    ], $this->createPositiveRenderingContext());

    verify($result)->equals(GutenbergCouponGenerator::SAFE_PLACEHOLDER);
    verify($collector->getFailures()[0]['message'])->equals('WooCommerce did not save the generated coupon.');
  }

  public function testItDoesNotRecordRecipientEmailInFailureContext(): void {
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

    $generator->generate('', [
      'source' => 'createNew',
      'discountType' => 'percent',
      'amount' => '101',
    ], $this->createPositiveRenderingContext());

    verify(isset($collector->getFailures()[0]['context']['recipient_email']))->false();
  }

  private function createPositiveRenderingContext(): Rendering_Context {
    return $this->createRenderingContext([
      'integration' => 'mailpoet',
      'recipient_email' => 'Subscriber@Example.com',
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

  private function createStandardRenderingContext(): Rendering_Context {
    return $this->createRenderingContext([
      'integration' => 'mailpoet',
      'newsletter_id' => 1,
      'queue_id' => 2,
      'email_type' => NewsletterEntity::TYPE_STANDARD,
      'is_real_send' => true,
      'is_preview' => false,
      'is_single_recipient' => false,
      'subscriber_count' => 10,
      'mailpoet_is_automation' => false,
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

  // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Methods mirror WooCommerce coupon setters.
  private function createCouponStub(int $saveResult = 123): object {
    return new class($saveResult) {
      /** @var int */
      private $saveResult;

      /** @var string|null */
      public $generatedCode;

      /** @var array|null */
      public $emailRestrictions;

      public function __construct(
        int $saveResult
      ) {
        $this->saveResult = $saveResult;
      }

      public function set_code(string $code): void {
        $this->generatedCode = $code;
      }

      public function set_email_restrictions(array $restrictions): void {
        $this->emailRestrictions = $restrictions;
      }

      public function save(): int {
        return $this->saveResult;
      }

      public function set_description(string $description): void {
      }

      public function set_discount_type(string $discountType): void {
      }

      public function set_amount(float $amount): void {
      }

      public function set_date_expires($date): void {
      }

      public function set_free_shipping(bool $freeShipping): void {
      }

      public function set_minimum_amount($minimumAmount): void {
      }

      public function set_maximum_amount($maximumAmount): void {
      }

      public function set_individual_use(bool $individualUse): void {
      }

      public function set_exclude_sale_items(bool $excludeSaleItems): void {
      }

      public function set_product_ids(array $productIds): void {
      }

      public function set_excluded_product_ids(array $excludedProductIds): void {
      }

      public function set_product_categories(array $productCategoryIds): void {
      }

      public function set_excluded_product_categories(array $excludedProductCategoryIds): void {
      }

      public function set_usage_limit(int $usageLimit): void {
      }

      public function set_usage_limit_per_user(int $usageLimitPerUser): void {
      }
    };
  }

  // phpcs:enable
}
