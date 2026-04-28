<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Coupons;

class CouponBlockFailureTranslator {
  public function getFailureMessage(CouponBlockGenerationFailureCollector $failureCollector): string {
    $failures = $failureCollector->getFailures();
    $firstFailure = $failures[0] ?? null;
    if (is_array($firstFailure) && !empty($firstFailure['message']) && is_string($firstFailure['message'])) {
      return sprintf(
        // translators: %s is the specific coupon generation failure.
        __('Auto-generated coupon code could not be created: %s', 'mailpoet'),
        $firstFailure['message']
      );
    }

    return __('Auto-generated coupon codes are only supported in regular newsletters and automation emails sent to one subscriber at a time. Remove the generated coupon block or use an existing coupon before sending this email.', 'mailpoet');
  }
}
