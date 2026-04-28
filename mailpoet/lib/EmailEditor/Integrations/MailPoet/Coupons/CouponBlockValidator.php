<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Coupons;

use MailPoet\WooCommerce\Helper;
use MailPoet\WP\Functions as WPFunctions;

class CouponBlockValidator {
  /** @var Helper */
  private $wcHelper;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    Helper $wcHelper,
    WPFunctions $wp
  ) {
    $this->wcHelper = $wcHelper;
    $this->wp = $wp;
  }

  public function validate(array $attrs, string $recipientEmail): array {
    $discountType = $this->validateDiscountType($attrs['discountType'] ?? null);
    $amount = $this->validateRequiredNumber($attrs['amount'] ?? null, 'amount');
    if ($discountType === 'percent' && $amount > 100) {
      throw new CouponBlockValidationException('Percent coupon amount must be 100 or lower.');
    }

    $minimumAmount = $this->validateOptionalNumber($attrs['minimumAmount'] ?? null, 'minimumAmount');
    $maximumAmount = $this->validateOptionalNumber($attrs['maximumAmount'] ?? null, 'maximumAmount');
    if ($minimumAmount !== '' && $maximumAmount !== '' && $maximumAmount < $minimumAmount) {
      throw new CouponBlockValidationException('Maximum amount must be greater than or equal to minimum amount.');
    }

    $emailRestrictions = $this->validateEmailRestrictions($attrs['emailRestrictions'] ?? []);
    if (CouponBlockAttributeParser::toBoolean($attrs['restrictToSubscriber'] ?? false)) {
      $recipientEmailRestrictions = $this->validateEmailRestrictions([$recipientEmail]);
      if (empty($recipientEmailRestrictions)) {
        throw new CouponBlockValidationException('Recipient email is required for recipient-restricted coupons.');
      }
      $emailRestrictions[] = $recipientEmailRestrictions[0];
    }

    return [
      'discountType' => $discountType,
      'amount' => $amount,
      'expiryDay' => $this->validateOptionalInteger($attrs['expiryDay'] ?? null, 'expiryDay'),
      'freeShipping' => CouponBlockAttributeParser::toBoolean($attrs['freeShipping'] ?? false),
      'individualUse' => CouponBlockAttributeParser::toBoolean($attrs['individualUse'] ?? false),
      'excludeSaleItems' => CouponBlockAttributeParser::toBoolean($attrs['excludeSaleItems'] ?? false),
      'usageLimit' => $this->validateOptionalInteger($attrs['usageLimit'] ?? null, 'usageLimit'),
      'usageLimitPerUser' => $this->validateOptionalInteger($attrs['usageLimitPerUser'] ?? null, 'usageLimitPerUser'),
      'minimumAmount' => $minimumAmount,
      'maximumAmount' => $maximumAmount,
      'productIds' => $this->validateProductIds($attrs['productIds'] ?? []),
      'excludedProductIds' => $this->validateProductIds($attrs['excludedProductIds'] ?? []),
      'productCategoryIds' => $this->validateProductCategoryIds($attrs['productCategoryIds'] ?? []),
      'excludedProductCategoryIds' => $this->validateProductCategoryIds($attrs['excludedProductCategoryIds'] ?? []),
      'emailRestrictions' => array_values(array_unique($emailRestrictions)),
    ];
  }

  private function validateDiscountType($discountType): string {
    if (!is_string($discountType) || $discountType === '') {
      throw new CouponBlockValidationException('Discount type is required.');
    }

    if (!in_array($discountType, array_keys($this->wcHelper->wcGetCouponTypes()), true)) {
      throw new CouponBlockValidationException('Invalid discount type.');
    }

    return $discountType;
  }

  private function validateRequiredNumber($value, string $field): float {
    if ($value === null || $value === '' || !is_numeric($value)) {
      throw new CouponBlockValidationException(sprintf('%s must be numeric.', $field));
    }

    $value = (float)$value;
    if ($value < 0) {
      throw new CouponBlockValidationException(sprintf('%s must be greater than or equal to 0.', $field));
    }

    return $value;
  }

  /**
   * @return float|''
   */
  private function validateOptionalNumber($value, string $field) {
    if ($value === null || $value === '') {
      return '';
    }

    return $this->validateRequiredNumber($value, $field);
  }

  private function validateOptionalInteger($value, string $field): int {
    if ($value === null || $value === '') {
      return 0;
    }

    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
      throw new CouponBlockValidationException(sprintf('%s must be an integer.', $field));
    }

    $value = (int)$value;
    if ($value < 0) {
      throw new CouponBlockValidationException(sprintf('%s must be greater than or equal to 0.', $field));
    }

    return $value;
  }

  private function validateProductIds($items): array {
    $ids = $this->extractItemIds($items, 'product');
    foreach ($ids as $id) {
      if (!$this->wcHelper->wcGetProduct($id)) {
        throw new CouponBlockValidationException('Invalid product ID.');
      }
    }
    return $ids;
  }

  private function validateProductCategoryIds($items): array {
    $ids = $this->extractItemIds($items, 'product category');
    foreach ($ids as $id) {
      $term = $this->wp->getTerm($id, 'product_cat');
      if (!$term || $this->wp->isWpError($term)) {
        throw new CouponBlockValidationException('Invalid product category ID.');
      }
    }
    return $ids;
  }

  private function extractItemIds($items, string $label): array {
    if ($items === null || $items === '') {
      return [];
    }
    if (!is_array($items)) {
      throw new CouponBlockValidationException(sprintf('Invalid %s IDs.', $label));
    }

    $ids = [];
    foreach ($items as $item) {
      $id = null;
      if (is_array($item)) {
        $id = $item['id'] ?? null;
      } elseif (is_object($item) && isset($item->id)) {
        $id = $item->id;
      }

      if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        throw new CouponBlockValidationException(sprintf('Invalid %s ID.', $label));
      }
      $ids[] = (int)$id;
    }

    return array_values(array_unique($ids));
  }

  private function validateEmailRestrictions($raw): array {
    if ($raw === null || $raw === '') {
      return [];
    }

    if (is_string($raw)) {
      $raw = explode(',', $raw);
    }

    if (!is_array($raw)) {
      throw new CouponBlockValidationException('Invalid email restrictions.');
    }

    $emails = [];
    foreach ($raw as $email) {
      if (!is_string($email)) {
        throw new CouponBlockValidationException('Invalid email restriction.');
      }
      $email = strtolower(trim($this->wp->sanitizeEmail($email)));
      if (!$email) {
        continue;
      }
      if (!$this->wp->isEmail($email)) {
        throw new CouponBlockValidationException('Invalid email restriction.');
      }
      $emails[] = $email;
    }

    return array_values(array_unique($emails));
  }
}
