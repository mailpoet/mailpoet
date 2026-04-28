<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Coupons;

class CouponBlock {
  const NAME = 'woocommerce/coupon-code';
  const SAFE_PLACEHOLDER = 'XXXX-XXXXXX-XXXX';

  private const GENERATED_COUPON_ATTRIBUTES = [
    'discountType',
    'amount',
    'expiryDay',
    'freeShipping',
    'minimumAmount',
    'maximumAmount',
    'individualUse',
    'excludeSaleItems',
    'productIds',
    'excludedProductIds',
    'productCategoryIds',
    'excludedProductCategoryIds',
    'emailRestrictions',
    'usageLimit',
    'usageLimitPerUser',
    'restrictToSubscriber',
  ];

  public static function isCreateNew(array $attrs, bool $containsGeneratedPlaceholder = false): bool {
    if (array_key_exists('source', $attrs)) {
      return $attrs['source'] === 'createNew';
    }

    if (!empty($attrs['couponCode'])) {
      return false;
    }

    return $containsGeneratedPlaceholder || self::hasGeneratedCouponAttributes($attrs);
  }

  private static function hasGeneratedCouponAttributes(array $attrs): bool {
    foreach (self::GENERATED_COUPON_ATTRIBUTES as $attribute) {
      if (array_key_exists($attribute, $attrs)) {
        return true;
      }
    }

    return false;
  }
}
