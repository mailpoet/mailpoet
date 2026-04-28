<?php declare(strict_types = 1);

namespace unit\EmailEditor\Integrations\MailPoet\Coupons;

use MailPoet\EmailEditor\Integrations\MailPoet\Coupons\CouponBlockValidationException;
use MailPoet\EmailEditor\Integrations\MailPoet\Coupons\CouponBlockValidator;
use MailPoet\WooCommerce\Helper;
use MailPoet\WP\Functions as WPFunctions;

class CouponBlockValidatorTest extends \MailPoetUnitTest {
  public function testItValidatesCouponAttributes(): void {
    $validator = new CouponBlockValidator(
      $this->makeHelper(),
      $this->makeWpFunctions()
    );

    $attrs = $validator->validate([
      'discountType' => 'percent',
      'amount' => '25',
      'expiryDay' => '10',
      'freeShipping' => 'true',
      'minimumAmount' => '5',
      'maximumAmount' => '50',
      'individualUse' => true,
      'excludeSaleItems' => 'yes',
      'usageLimit' => '2',
      'usageLimitPerUser' => '1',
      'emailRestrictions' => ' CUSTOMER@example.com,subscriber@example.com ',
      'restrictToSubscriber' => true,
    ], 'Subscriber@Example.com');

    verify($attrs['discountType'])->equals('percent');
    verify($attrs['amount'])->equals(25.0);
    verify($attrs['expiryDay'])->equals(10);
    verify($attrs['freeShipping'])->true();
    verify($attrs['minimumAmount'])->equals(5.0);
    verify($attrs['maximumAmount'])->equals(50.0);
    verify($attrs['individualUse'])->true();
    verify($attrs['excludeSaleItems'])->true();
    verify($attrs['usageLimit'])->equals(2);
    verify($attrs['usageLimitPerUser'])->equals(1);
    verify($attrs['emailRestrictions'])->equals(['customer@example.com', 'subscriber@example.com']);
  }

  public function testItRejectsInvalidDiscountType(): void {
    $this->expectException(CouponBlockValidationException::class);
    $this->expectExceptionMessage('Invalid discount type.');

    (new CouponBlockValidator($this->makeHelper(), $this->makeWpFunctions()))
      ->validate(['discountType' => 'invalid', 'amount' => '10'], '');
  }

  public function testItRejectsPercentAmountAboveOneHundred(): void {
    $this->expectException(CouponBlockValidationException::class);
    $this->expectExceptionMessage('Percent coupon amount must be 100 or lower.');

    (new CouponBlockValidator($this->makeHelper(), $this->makeWpFunctions()))
      ->validate(['discountType' => 'percent', 'amount' => '101'], '');
  }

  public function testItRejectsMaximumAmountBelowMinimumAmount(): void {
    $this->expectException(CouponBlockValidationException::class);
    $this->expectExceptionMessage('Maximum amount must be greater than or equal to minimum amount.');

    (new CouponBlockValidator($this->makeHelper(), $this->makeWpFunctions()))
      ->validate([
        'discountType' => 'fixed_cart',
        'amount' => '10',
        'minimumAmount' => '20',
        'maximumAmount' => '10',
      ], '');
  }

  public function testItRejectsInvalidProductId(): void {
    $this->expectException(CouponBlockValidationException::class);
    $this->expectExceptionMessage('Invalid product ID.');

    (new CouponBlockValidator(
      $this->makeHelper(['wcGetProduct' => false]),
      $this->makeWpFunctions()
    ))->validate([
      'discountType' => 'percent',
      'amount' => '10',
      'productIds' => [['id' => 123]],
    ], '');
  }

  public function testItRejectsInvalidProductCategoryId(): void {
    $this->expectException(CouponBlockValidationException::class);
    $this->expectExceptionMessage('Invalid product category ID.');

    (new CouponBlockValidator(
      $this->makeHelper(),
      $this->makeWpFunctions(['getTerm' => false])
    ))->validate([
      'discountType' => 'percent',
      'amount' => '10',
      'productCategoryIds' => [['id' => 123]],
    ], '');
  }

  public function testItKeepsEmptyRecipientRestrictionExplicit(): void {
    $attrs = (new CouponBlockValidator(
      $this->makeHelper(),
      $this->makeWpFunctions()
    ))->validate([
      'discountType' => 'percent',
      'amount' => '10',
      'restrictToSubscriber' => true,
    ], '');

    verify($attrs['emailRestrictions'])->equals([]);
  }

  private function makeHelper(array $overrides = []): Helper {
    return $this->make(Helper::class, array_merge([
      'wcGetCouponTypes' => [
        'percent' => 'Percentage discount',
        'fixed_cart' => 'Fixed cart discount',
      ],
      'wcGetProduct' => new \stdClass(),
    ], $overrides));
  }

  private function makeWpFunctions(array $overrides = []): WPFunctions {
    return $this->make(WPFunctions::class, array_merge([
      'sanitizeEmail' => function(string $email): string {
        return trim($email);
      },
      'isEmail' => function($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
      },
      'getTerm' => new \stdClass(),
      'isWpError' => false,
    ], $overrides));
  }
}
