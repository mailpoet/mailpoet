<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Coupons;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
class CouponsTest extends \MailPoetTest {

  /** @var Coupons */
  private $endpoint;

  /** @var WooCommerceHelper */
  private $wcHelper;

  /** @var WPFunctions */
  private $wpFunctions;

  public function _before() {
    parent::_before();
    $this->endpoint = $this->diContainer->get(Coupons::class);
    $this->wcHelper = $this->diContainer->get(WooCommerceHelper::class);
    $this->wpFunctions = $this->diContainer->get(WPFunctions::class);
  }

  public function testItCanGetAllCoupons(): void {
    $coupons = [
      $this->createCoupon('coupon-1'),
      $this->createCoupon('coupon-2'),
      $this->createCoupon('coupon-3'),
    ];
    $response = $this->endpoint->getCoupons();

    $this->validateResponse($response, $coupons);
  }

  public function testItGetsIncludedCouponIds(): void {
    $coupons = [
      $this->createCoupon('coupon-1'),
      $this->createCoupon('coupon-2'),
      $this->createCoupon('coupon-3'),
      $this->createCoupon('include-coupon-4'),
      $this->createCoupon('coupon-5'),
      $this->createCoupon('include-coupon-6'),
    ];

    $response = $this->endpoint->getCoupons([
      'include_coupon_ids' => [
        $coupons[3]->get_id(),
        $coupons[5]->get_id(),
      ],
      'page_size' => 1,
    ]);
    // expected coupons ordered by code
    $expectedResult = [
      $coupons[3], // include-coupon-4
      $coupons[5], // include-coupon-6
      $coupons[0], // coupon-1
    ];

    $this->validateResponse($response, $expectedResult);
  }

  public function testItGetsCouponsBySearchQuery(): void {
    $coupons = [
      $this->createCoupon('search-coupon-1'),
      $this->createCoupon('coupon-search-2'),
      $this->createCoupon('coupon-3'),
      $this->createCoupon('coupon-4'),
      $this->createCoupon('coupon-5-search'),
      $this->createCoupon('coupon-6'),
    ];

    $response = $this->endpoint->getCoupons([
      'search' => 'search',
      'page_size' => 3,
    ]);

    // expected coupons ordered by code
    $expectedResult = [
      $coupons[4], // coupon-5-search
      $coupons[1], // coupon-search-2
      $coupons[0], // search-coupon-1
    ];
    $this->validateResponse($response, $expectedResult);
  }

  public function testItGetsCouponsByPageNumber(): void {
    $coupons = [
      $this->createCoupon('coupon-1'),
      $this->createCoupon('coupon-2'),
      $this->createCoupon('coupon-3'),
      $this->createCoupon('coupon-4'),
      $this->createCoupon('coupon-5'),
      $this->createCoupon('coupon-6'),
    ];

    $response = $this->endpoint->getCoupons([
      'page_number' => 2,
      'page_size' => 2,
    ]);

    // expected coupons ordered by code
    $expectedResult = [
      $coupons[2], // coupon-3
      $coupons[3], // coupon-4
    ];
    $this->validateResponse($response, $expectedResult);
  }

  public function testItGetsCouponsByDiscountType(): void {
    $coupons = [
      $this->createCoupon('coupon-1', 'fixed_cart'),
      $this->createCoupon('coupon-2', 'percent'),
      $this->createCoupon('coupon-3', 'percent'),
      $this->createCoupon('coupon-4', 'fixed_product'),
      $this->createCoupon('coupon-5', 'fixed_cart'),
      $this->createCoupon('coupon-6', 'fixed_product'),
    ];

    $response = $this->endpoint->getCoupons(['discount_type' => 'fixed_product']);

    // expected coupons ordered by code
    $expectedResult = [
      $coupons[3], // coupon-4
      $coupons[5], // coupon-6
    ];
    $this->validateResponse($response, $expectedResult);
  }

  public function _after() {
    parent::_after();
    $coupons = $this->wpFunctions->getPosts([
      'post_type' => 'shop_coupon',
      'posts_per_page' => -1,
    ]);
    foreach ($coupons as $coupon) {
      $this->wpFunctions->wpDeletePost($coupon->ID, true);
    }
  }

  private function validateResponse($response, $expectedCoupons): void {
    $returnedCoupons = $response->data;
    verify($response->status)->equals(APIResponse::STATUS_OK);
    expect($returnedCoupons)->count(count($expectedCoupons));

    foreach ($expectedCoupons as $key => $coupon) {
      verify($coupon->get_id())->equals($returnedCoupons[$key]['id']);
      verify($coupon->get_code())->equals($returnedCoupons[$key]['text']);
      verify($coupon->get_discount_type())->equals($returnedCoupons[$key]['discountType']);
    }
  }

  private function createCoupon(?string $couponCode = null, ?string $discountType = null): \WC_Coupon {
    $discountType = $discountType ?: current(array_keys($this->wcHelper->wcGetCouponTypes()));
    $coupon = $this->wcHelper->createWcCoupon('');
    $coupon->set_code($couponCode);
    $coupon->set_discount_type($discountType);
    $coupon->set_amount(10);
    $coupon->save();
    return $coupon;
  }
}
