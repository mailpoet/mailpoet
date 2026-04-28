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

  private const CREATE_NEW_DEFAULT_ATTRIBUTES = [
    'source' => 'createNew',
    'discountType' => 'percent',
    'amount' => 10,
    'expiryDay' => 10,
    'freeShipping' => false,
    'minimumAmount' => '',
    'maximumAmount' => '',
    'individualUse' => false,
    'excludeSaleItems' => false,
    'productIds' => [],
    'excludedProductIds' => [],
    'productCategoryIds' => [],
    'excludedProductCategoryIds' => [],
    'emailRestrictions' => '',
    'usageLimit' => 0,
    'usageLimitPerUser' => 0,
    'restrictToSubscriber' => false,
  ];

  public static function isCreateNew(array $attrs, ?bool $containsGeneratedPlaceholder = null): bool {
    if (array_key_exists('source', $attrs)) {
      return $attrs['source'] === 'createNew';
    }

    if (!empty($attrs['couponCode'])) {
      return false;
    }

    if ($containsGeneratedPlaceholder !== null) {
      return $containsGeneratedPlaceholder || self::hasGeneratedCouponAttributes($attrs);
    }

    return true;
  }

  public static function withCreateNewDefaults(array $attrs): array {
    return array_merge(self::CREATE_NEW_DEFAULT_ATTRIBUTES, $attrs);
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
