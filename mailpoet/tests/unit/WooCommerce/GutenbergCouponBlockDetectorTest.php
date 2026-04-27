<?php declare(strict_types = 1);

namespace unit\WooCommerce;

use MailPoet\WooCommerce\GutenbergCouponBlockDetector;
use MailPoet\WP\Functions as WPFunctions;

class GutenbergCouponBlockDetectorTest extends \MailPoetUnitTest {
  public function testItTreatsMissingSourceAsCreateNew(): void {
    $detector = new GutenbergCouponBlockDetector($this->makeWpFunctions([
      [
        'blockName' => 'woocommerce/coupon-code',
        'attrs' => [],
        'innerBlocks' => [],
      ],
    ]));

    verify($detector->hasCreateNewCouponBlock('content'))->true();
  }

  public function testItFindsNestedCreateNewCouponBlocks(): void {
    $detector = new GutenbergCouponBlockDetector($this->makeWpFunctions([
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
    $detector = new GutenbergCouponBlockDetector($this->makeWpFunctions([
      [
        'blockName' => 'woocommerce/coupon-code',
        'attrs' => ['source' => 'existing'],
        'innerBlocks' => [],
      ],
    ]));

    verify($detector->hasCreateNewCouponBlock('content'))->false();
  }

  private function makeWpFunctions(array $blocks): WPFunctions {
    return $this->make(WPFunctions::class, [
      'parseBlocks' => $blocks,
    ]);
  }
}
