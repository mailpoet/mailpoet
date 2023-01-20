<?php declare(strict_types = 1);

namespace unit\Newsletter\Editor;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewsletterCoupon;
use MailPoet\Newsletter\Renderer\Blocks\Coupon;

class NewsletterCouponTest extends \MailPoetUnitTest {
  public function testCleanupSensitiveDataRecursively() {
    $newsletterCoupon = new NewsletterCoupon();
    $newsletter = new NewsletterEntity();
    $blocks = [
      [
        'type' => 'any',
        'blocks' => [
          [
            'type' => Coupon::TYPE,
            'couponId' => '100',
            'code' => 'asdasjdkkjaskljdasd',
          ],
          [
            'type' => 'any',
            'blocks' => [
              [
                'type' => Coupon::TYPE,
                'couponId' => '100',
                'code' => 'asdasjdkkjaskljdasd',
              ],
            ],
          ],
        ],
      ],
      [
        'type' => Coupon::TYPE,
        'couponId' => '100',
        'code' => 'asdasjdkkjaskljdasd',
      ],
    ];
    $newsletter->setBody(['content' => ['blocks' => $blocks]]);
    $result = $newsletterCoupon->cleanupSensitiveData($newsletter);
    $body = (array)$result->getBody();
    expect($body['content']['blocks'][0]['blocks'][0])->equals(['type' => Coupon::TYPE]);
    expect($body['content']['blocks'][0]['blocks'][1]['blocks'][0])->equals(['type' => Coupon::TYPE]);
    expect($body['content']['blocks'][1])->equals(['type' => Coupon::TYPE]);
  }
}
