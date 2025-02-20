<?php declare(strict_types = 1);

namespace unit\WooCommerce;

use Codeception\Stub;
use Helper\WordPress;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Blocks\Coupon;
use MailPoet\NewsletterProcessingException;
use MailPoet\WooCommerce\CouponPreProcessor;
use MailPoet\WooCommerce\Helper;
use MailPoetVendor\Doctrine\Common\Collections\ArrayCollection;

class CouponPreProcessorTest extends \MailPoetUnitTest {

  /*** @var CouponPreProcessor */
  private $processor;

  private static $saveCouponId = 100;
  private static $updatingCouponId = 5;

  public function __construct(
    $name = null,
    array $data = [],
    $dataName = ''
  ) {
    parent::__construct($name, $data, $dataName);

    WordPress::interceptFunction('wp_timezone', function() {
      new \DateTimeZone('UTC');
    });

    $this->processor = new CouponPreProcessor(
      Stub::make(Helper::class),
      Stub::make(NewslettersRepository::class)
    );

  }

  public function testProcessCouponsDoesntCreateCouponForPreview() {
    $newsletter = (new NewsletterEntity());
    $blocks = ['blocks' => [
      [
        'type' => Coupon::TYPE,
      ],
    ]];
    $result = $this->processor->processCoupons($newsletter, $blocks, true);

    verify($result)->equals($blocks);
  }

  public function testEnsureCouponForBlocks() {
    $mockedWCCoupon = $this->createCouponMock();

    $wcHelper = $this->make(Helper::class, [
      'createWcCoupon' => $mockedWCCoupon,
      'isWooCommerceActive' => true,
    ]);

    $processor = new CouponPreProcessor(
      $wcHelper,
      Stub::make(NewslettersRepository::class, [
        'flush' => Stub\Expected::never(), // for type = NewsletterEntity::TYPE_AUTOMATIC, the $newsletter->body shouldn't update
      ], $this)
    );

    $newsletter = (new NewsletterEntity());
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATIC); // so that CouponPreProcessor::processCoupons won't try to persist
    $expiryDay = 5;
    // NewsletterEntity::TYPE_AUTOMATIC, so that CouponPreProcessor::processCoupons won't try to persist
    [$newsletter, $blocks] = $this->createNewsletterAndBlockForType(NewsletterEntity::TYPE_AUTOMATIC, $expiryDay);
    $this->assertWCCouponReceivesCorrectValues($mockedWCCoupon, self::$saveCouponId, $expiryDay);

    $result = $processor->processCoupons($newsletter, $blocks, false);
    verify($result[0]['blocks'][0]['couponId'])->equals(self::$saveCouponId);
  }

  public function testEnsureCouponForBlocksSaves() {
    $mockedWCCoupon = $this->createCouponMock();

    $wcHelper = $this->make(Helper::class, [
      'createWcCoupon' => $mockedWCCoupon,
      'isWooCommerceActive' => true,
    ]);

    $processor = new CouponPreProcessor(
      $wcHelper,
      Stub::make(NewslettersRepository::class, [
        'flush' => Stub\Expected::once(), // for type != NewsletterEntity::TYPE_AUTOMATIC, the $newsletter->body should update
      ], $this)
    );

    $newsletter = (new NewsletterEntity());
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD); // so that CouponPreProcessor::processCoupons won't try to persist
    $expiryDay = 10;
    [$newsletter, $blocks] = $this->createNewsletterAndBlockForType(NewsletterEntity::TYPE_STANDARD, $expiryDay);
    $this->assertWCCouponReceivesCorrectValues($mockedWCCoupon, self::$updatingCouponId, $expiryDay);
    /**
     * If the coupon already is generated for the block, it should not get re-generated
     */
    $result = $processor->processCoupons($newsletter, $blocks, false);
    verify($result[0]['blocks'][0]['couponId'])->equals(self::$updatingCouponId);
  }

  public function testEnsureCouponIsNotGeneratedWhenIsSet(): void {
    $mockedWCCoupon = $this->createCouponMock();

    $wcHelper = $this->make(Helper::class, [
      'createWcCoupon' => $mockedWCCoupon,
      'isWooCommerceActive' => true,
    ]);

    $processor = new CouponPreProcessor(
      $wcHelper,
      Stub::make(NewslettersRepository::class, [
        'flush' => Stub\Expected::never(),
      ], $this)
    );

    $newsletter = (new NewsletterEntity());
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    [$newsletter, $blocks] = $this->createNewsletterAndBlockForType(
      NewsletterEntity::TYPE_STANDARD,
      null,
      self::$saveCouponId
    );

    $mockedWCCoupon->expects($this->never())->method('set_code');
    $mockedWCCoupon->expects($this->never())->method('set_description');
    $mockedWCCoupon->expects($this->never())->method('save');
    $result = $processor->processCoupons($newsletter, $blocks);
    verify($result[0]['blocks'][0]['couponId'])->equals(self::$saveCouponId);
  }

  public function testItThrowsWhenWCIsNotActive() {
    $wcHelper = $this->make(Helper::class, [
      'createWcCoupon' => $this->createCouponMock(),
      'isWooCommerceActive' => false,
    ]);

    $processor = new CouponPreProcessor(
      $wcHelper,
      Stub::make(NewslettersRepository::class, [
        'flush' => Stub\Expected::never(),
      ], $this)
    );
    $newsletter = (new NewsletterEntity());

    $blocks = ['blocks' => ['type' => Coupon::TYPE]];
    $this->expectException(NewsletterProcessingException::class);
    $this->expectExceptionMessage('WooCommerce is not active');
    $result = $processor->processCoupons($newsletter, $blocks, false);
    verify($result)->equals($blocks);
  }

  public function testItThrowsWhenCouponClassThrows() {
    $exceptionMessage = 'Invalid discount type';
    $wcHelper = $this->make(Helper::class, [
      /* @phpstan-ignore-next-line Unable to resolve the template type RealInstanceType in call to method Codeception\Test\Unit::make() */
      'createWcCoupon' => $this->make($this->createCouponMock(), ['set_discount_type' => function() use($exceptionMessage) {
        throw new \Exception($exceptionMessage);
      }]),
      'isWooCommerceActive' => true,
    ]);

    $processor = new CouponPreProcessor(
      $wcHelper,
      Stub::make(NewslettersRepository::class, [
        'flush' => Stub\Expected::never(),
      ], $this)
    );

    [$newsletter, $blocks] = $this->createNewsletterAndBlockForType(NewsletterEntity::TYPE_STANDARD, 5);
    $this->expectException(NewsletterProcessingException::class);
    $this->expectExceptionMessage($exceptionMessage);
    $processor->processCoupons($newsletter, $blocks, false);
  }

  public function testItRestrictsCouponToSubscriber(): void {
    $subscriberEmail = 'test@example.com';
    $wcCoupon = $this->createCouponMock();

    $subscriber = $this->make(SubscriberEntity::class, [
      'getEmail' => $subscriberEmail,
    ]);

    $taskSubscriber = $this->make(ScheduledTaskSubscriberEntity::class, [
      'getSubscriber' => $subscriber,
    ]);

    $task = $this->make(ScheduledTaskEntity::class, [
      'getSubscribers' => new ArrayCollection([$taskSubscriber]),
    ]);

    $queue = $this->make(SendingQueueEntity::class, [
      'getTask' => $task,
    ]);

    $wcHelper = $this->make(Helper::class, [
      'createWcCoupon' => $wcCoupon,
      'isWooCommerceActive' => true,
    ]);

    $processor = new CouponPreProcessor(
      $wcHelper,
      $this->make(NewslettersRepository::class)
    );

    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATION);
    $blocks = [
      [
        'type' => 'any',
        'blocks' => [
          [
            'type' => Coupon::TYPE,
            'discountType' => 'percent',
            'amount' => '100',
            'restrictToSubscriber' => true,
          ],
        ],
      ],
    ];
    $newsletter->setBody(['blocks' => $blocks, 'content' => []]);

    $wcCoupon->expects($this->exactly(2))
      ->method('set_email_restrictions')
      ->withConsecutive(
        [[$subscriberEmail]],
        [['other@example.com', $subscriberEmail]]
      );

    // Test with restrictToSubscriber enabled
    $processor->processCoupons($newsletter, $blocks, false, $queue);

    // Test with additional emailRestrictions
    $blocks[0]['blocks'][0]['emailRestrictions'] = 'other@example.com';
    $processor->processCoupons($newsletter, $blocks, false, $queue);
  }

  private function assertWCCouponReceivesCorrectValues($mockedWCCoupon, $expectedCouponId, $expiryDay) {
    $mockedWCCoupon->method('save')->willReturn($expectedCouponId);

    $mockedWCCoupon->method('set_code')->willReturnCallback(function ($code) {
      verify($code)->notEmpty();
    });

    $mockedWCCoupon->method('set_date_expires')->willReturnCallback(function ($date) use ($expiryDay) {
      verify(\date('Y-m-d', $date))->equals((new \DateTime("now", new \DateTimeZone('UTC')))->modify("+{$expiryDay} days")->format('Y-m-d'));
    });
  }

  private function createNewsletterAndBlockForType($newsletterType, ?int $expiryDay, ?int $couponId = null): array {
    $newsletter = (new NewsletterEntity());
    $newsletter->setType($newsletterType);
    $blocks = [
      [
        'type' => 'any',
        'blocks' => [
          [
            'type' => Coupon::TYPE,
            'discountType' => 'percent',
            'amount' => '100',
            'expiryDay' => $expiryDay,
            'couponId' => $couponId,
          ],
        ],
      ],
    ];
    $newsletter->setBody(['blocks' => $blocks, 'content' => []]);
    return [$newsletter, $blocks];
  }

  private function createCouponMock() {
    /* @phpstan-ignore-next-line ignoring usage of string instead of class-string */
    return $this->getMockBuilder('MaybeMissingWC_Coupon')
      ->setMethods([
        'set_code',
        'set_discount_type',
        'set_amount',
        'set_description',
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
  }
}
