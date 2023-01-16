<?php declare(strict_types = 1);

namespace unit\WooCommerce;

use Codeception\Stub;
use Helper\WordPress;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Renderer\Blocks\Coupon;
use MailPoet\WooCommerce\CouponPreProcessor;
use MailPoet\WooCommerce\Helper;

class CouponPreProcessorTest extends \MailPoetUnitTest {

  /*** @var CouponPreProcessor */
  private $processor;

  private static $saveCouponId = 100;

  public function __construct(
    $name = null,
    array $data = [],
    $dataName = ''
  ) {
    parent::__construct($name, $data, $dataName);

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

    expect($result)->equals($blocks);
  }

  public function testEnsureCouponForBlocks() {
    WordPress::interceptFunction('wp_timezone', function() {
      new \DateTimeZone('UTC');
    });

    /* @phpstan-ignore-next-line ignoring usage of string instead of class-string */
    $mockedWCCoupon = $this->getMockBuilder('MaybeMissingWC_Coupon')
      ->setMethods(['set_code', 'set_discount_type', 'set_amount', 'set_description', 'set_date_expires', 'save'])
      ->getMock();

    $wcHelper = $this->make(Helper::class, [
      'createWcCoupon' => $mockedWCCoupon,
    ]);

    $processor = new CouponPreProcessor(
      $wcHelper,
      Stub::make(NewslettersRepository::class)
    );

    $newsletter = (new NewsletterEntity());
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATIC); // so that CouponPreProcessor::processCoupons won't try to persist
    $expiryDay = 5;
    $blocks = [
      [
      'type' => 'any',
      'blocks' => [
          [
            'type' => Coupon::TYPE,
            'discountType' => 'percent',
            'amount' => '100',
            'expiryDay' => $expiryDay,
          ],
        ],
      ],
    ];
    $newsletter->setBody(['blocks' => $blocks]);

    /* @phpstan-ignore-next-line ignoring method of undefined class MaybeMissingWC_Coupon */
    $mockedWCCoupon->method('save')->willReturn(self::$saveCouponId);

    /* @phpstan-ignore-next-line ignoring method of undefined class MaybeMissingWC_Coupon */
    $mockedWCCoupon->method('set_code')->willReturnCallback(function ($code) {
      expect($code)->notEmpty();
    });

    /* @phpstan-ignore-next-line ignoring method of undefined class MaybeMissingWC_Coupon */
    $mockedWCCoupon->method('set_date_expires')->willReturnCallback(function ($date) use ($expiryDay) {
      expect(\date('Y-m-d', $date))->equals((new \DateTime("now", new \DateTimeZone('UTC')))->modify("+{$expiryDay} days")->format('Y-m-d'));
    });

    $result = $processor->processCoupons($newsletter, $blocks, false);
    expect($result[0]['blocks'][0]['couponId'])->equals(self::$saveCouponId);
  }
}
