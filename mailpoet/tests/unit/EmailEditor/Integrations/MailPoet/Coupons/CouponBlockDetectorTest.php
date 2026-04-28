<?php declare(strict_types = 1);

namespace unit\EmailEditor\Integrations\MailPoet\Coupons;

use MailPoet\EmailEditor\Integrations\MailPoet\Coupons\CouponBlockDetector;
use MailPoet\WP\Functions as WPFunctions;

class CouponBlockDetectorTest extends \MailPoetUnitTest {
  public function testItTreatsMissingSourceWithStaticCouponCodeAsExisting(): void {
    $detector = new CouponBlockDetector($this->makeWpFunctions([
      [
        'blockName' => 'woocommerce/coupon-code',
        'attrs' => ['couponCode' => 'WELCOME10'],
        'innerHTML' => '<span class="woocommerce-coupon-code">WELCOME10</span>',
        'innerBlocks' => [],
      ],
    ]));

    verify($detector->hasCreateNewCouponBlock('content'))->false();
  }

  public function testItTreatsMissingSourceWithGeneratedPlaceholderAsCreateNew(): void {
    $detector = new CouponBlockDetector($this->makeWpFunctions([
      [
        'blockName' => 'woocommerce/coupon-code',
        'attrs' => [],
        'innerHTML' => '<span class="woocommerce-coupon-code">XXXX-XXXXXX-XXXX</span>',
        'innerBlocks' => [],
      ],
    ]));

    verify($detector->hasCreateNewCouponBlock('content'))->true();
  }

  public function testItFindsNestedCreateNewCouponBlocks(): void {
    $detector = new CouponBlockDetector($this->makeWpFunctions([
      [
        'blockName' => 'core/group',
        'attrs' => [],
        'innerBlocks' => [
          [
            'blockName' => 'woocommerce/coupon-code',
            'attrs' => ['source' => 'createNew'],
            'innerBlocks' => [],
          ],
        ],
      ],
    ]));

    verify($detector->hasCreateNewCouponBlock('content'))->true();
  }

  public function testItIgnoresExistingCouponBlocks(): void {
    $detector = new CouponBlockDetector($this->makeWpFunctions([
      [
        'blockName' => 'woocommerce/coupon-code',
        'attrs' => ['source' => 'existing'],
        'innerBlocks' => [],
      ],
    ]));

    verify($detector->hasCreateNewCouponBlock('content'))->false();
  }

  public function testItFindsRecipientRestrictedCreateNewCouponBlocks(): void {
    $detector = new CouponBlockDetector($this->makeWpFunctions([
      [
        'blockName' => 'core/group',
        'attrs' => [],
        'innerBlocks' => [
          [
            'blockName' => 'core/columns',
            'attrs' => [],
            'innerBlocks' => [
              [
                'blockName' => 'core/column',
                'attrs' => [],
                'innerBlocks' => [
                  [
                    'blockName' => 'woocommerce/coupon-code',
                    'attrs' => ['source' => 'createNew', 'restrictToSubscriber' => true],
                    'innerBlocks' => [],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ]));

    verify($detector->hasRecipientRestrictedCreateNewCouponBlock('content'))->true();
  }

  public function testItIgnoresRecipientRestrictionOnExistingCouponBlocks(): void {
    $detector = new CouponBlockDetector($this->makeWpFunctions([
      [
        'blockName' => 'woocommerce/coupon-code',
        'attrs' => ['source' => 'existing', 'restrictToSubscriber' => true],
        'innerBlocks' => [],
      ],
    ]));

    verify($detector->hasRecipientRestrictedCreateNewCouponBlock('content'))->false();
  }

  private function makeWpFunctions(array $blocks): WPFunctions {
    return $this->make(WPFunctions::class, [
      'parseBlocks' => $blocks,
    ]);
  }
}
