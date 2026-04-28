<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Coupons;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\WooCommerce\Helper;
use MailPoet\WooCommerce\RandomCouponCodeGenerator;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Generates coupon codes only while WooCommerce renders a MailPoet block email.
 *
 * Renderer clears the failure collector before each render and reads it
 * immediately after rendering, so failures recorded here stop that send without
 * leaking details into later renders.
 */
class CouponBlockGenerator {
  const SAFE_PLACEHOLDER = 'XXXX-XXXXXX-XXXX';
  const MAX_CODE_RETRIES = 5;

  /** @var Helper */
  private $wcHelper;

  /** @var CouponBlockValidator */
  private $validator;

  /** @var CouponBlockGenerationFailureCollector */
  private $failureCollector;

  /** @var WPFunctions */
  private $wp;

  private RandomCouponCodeGenerator $randomCouponCodeGenerator;

  public function __construct(
    Helper $wcHelper,
    CouponBlockValidator $validator,
    CouponBlockGenerationFailureCollector $failureCollector,
    WPFunctions $wp,
    RandomCouponCodeGenerator $randomCouponCodeGenerator
  ) {
    $this->wcHelper = $wcHelper;
    $this->validator = $validator;
    $this->failureCollector = $failureCollector;
    $this->wp = $wp;
    $this->randomCouponCodeGenerator = $randomCouponCodeGenerator;
  }

  public function init(): void {
    if (!class_exists(Rendering_Context::class)) {
      return;
    }

    $this->wp->addFilter(
      'woocommerce_coupon_code_block_auto_generate',
      [$this, 'generate'],
      5,
      3
    );
  }

  public function generate($couponCode, array $attrs, Rendering_Context $renderingContext): string {
    $couponCode = (string)$couponCode;
    if ($couponCode !== '') {
      return $couponCode;
    }

    $context = $renderingContext->get_email_context();
    if (($context['integration'] ?? null) !== 'mailpoet') {
      return $couponCode;
    }

    if (!$this->isCreateNewCouponBlock($attrs)) {
      return self::SAFE_PLACEHOLDER;
    }

    if (!($context['is_real_send'] ?? false)) {
      return self::SAFE_PLACEHOLDER;
    }

    $unsupportedContextMessage = $this->getUnsupportedRealSendContextMessage($attrs, $renderingContext);
    if ($unsupportedContextMessage !== null) {
      $this->recordFailure('invalid_real_send_context', $unsupportedContextMessage, $attrs, $context);
      return self::SAFE_PLACEHOLDER;
    }

    if (!$this->wcHelper->isWooCommerceActive()) {
      $this->recordFailure('woocommerce_unavailable', 'WooCommerce is not available.', $attrs, $context);
      return self::SAFE_PLACEHOLDER;
    }

    try {
      $recipientEmail = (string)$renderingContext->get_recipient_email();
      $validatedAttrs = $this->validator->validate($attrs, $recipientEmail);
      $code = $this->generateUniqueCode();
      $coupon = $this->wcHelper->createWcCoupon('');
      $coupon->set_code($code);
      $coupon->set_description($this->getCouponDescription($context));
      $coupon->set_discount_type($validatedAttrs['discountType']);
      $coupon->set_amount($validatedAttrs['amount']);

      if ($validatedAttrs['expiryDay'] > 0) {
        $coupon->set_date_expires(time() + ($validatedAttrs['expiryDay'] * (defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 86400)));
      }

      $coupon->set_free_shipping($validatedAttrs['freeShipping']);
      $coupon->set_minimum_amount($validatedAttrs['minimumAmount']);
      $coupon->set_maximum_amount($validatedAttrs['maximumAmount']);
      $coupon->set_individual_use($validatedAttrs['individualUse']);
      $coupon->set_exclude_sale_items($validatedAttrs['excludeSaleItems']);
      $coupon->set_product_ids($validatedAttrs['productIds']);
      $coupon->set_excluded_product_ids($validatedAttrs['excludedProductIds']);
      $coupon->set_product_categories($validatedAttrs['productCategoryIds']);
      $coupon->set_excluded_product_categories($validatedAttrs['excludedProductCategoryIds']);
      $coupon->set_email_restrictions($validatedAttrs['emailRestrictions']);
      $coupon->set_usage_limit($validatedAttrs['usageLimit']);
      $coupon->set_usage_limit_per_user($validatedAttrs['usageLimitPerUser']);

      $couponId = $coupon->save();
      if (!$couponId) {
        throw new \RuntimeException('WooCommerce did not save the generated coupon.');
      }

      return $code;
    } catch (\Throwable $e) {
      $this->recordFailure('generation_failed', $e->getMessage(), $attrs, $context);
      return self::SAFE_PLACEHOLDER;
    }
  }

  private function getUnsupportedRealSendContextMessage(array $attrs, Rendering_Context $renderingContext): ?string {
    if ($this->isSupportedAutomationContext($renderingContext)) {
      return null;
    }

    if (CouponBlockAttributeParser::toBoolean($attrs['restrictToSubscriber'] ?? false)) {
      return 'Recipient-restricted generated coupons are only supported in automation emails sent to one subscriber at a time.';
    }

    if ($this->isSupportedStandardContext($renderingContext)) {
      return null;
    }

    return 'Auto-generated coupon codes are only supported in regular newsletters and automation emails sent to one subscriber at a time.';
  }

  private function isCreateNewCouponBlock(array $attrs): bool {
    if (array_key_exists('source', $attrs)) {
      return $attrs['source'] === 'createNew';
    }

    if (!empty($attrs['couponCode'])) {
      return false;
    }

    return $this->hasGeneratedCouponAttributes($attrs);
  }

  private function hasGeneratedCouponAttributes(array $attrs): bool {
    $generatedCouponAttributes = [
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

    foreach ($generatedCouponAttributes as $attribute) {
      if (array_key_exists($attribute, $attrs)) {
        return true;
      }
    }

    return false;
  }

  private function isSupportedAutomationContext(Rendering_Context $renderingContext): bool {
    $context = $renderingContext->get_email_context();
    $recipientEmail = $renderingContext->get_recipient_email();
    $automationTypes = [
      NewsletterEntity::TYPE_AUTOMATION,
      NewsletterEntity::TYPE_AUTOMATION_NOTIFICATION,
      NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL,
    ];

    return ($context['integration'] ?? null) === 'mailpoet'
      && ($context['is_real_send'] ?? null) === true
      && ($context['is_preview'] ?? null) === false
      && ($context['is_single_recipient'] ?? null) === true
      && is_numeric($context['subscriber_count'] ?? null)
      && (int)$context['subscriber_count'] === 1
      && ($context['mailpoet_is_automation'] ?? null) === true
      && in_array($context['email_type'] ?? '', $automationTypes, true)
      && is_string($recipientEmail)
      && (bool)$this->wp->isEmail($recipientEmail);
  }

  private function isSupportedStandardContext(Rendering_Context $renderingContext): bool {
    $context = $renderingContext->get_email_context();
    return ($context['integration'] ?? null) === 'mailpoet'
      && ($context['is_real_send'] ?? null) === true
      && ($context['is_preview'] ?? null) === false
      && ($context['email_type'] ?? '') === NewsletterEntity::TYPE_STANDARD
      && ($context['mailpoet_is_automation'] ?? null) === false
      && is_numeric($context['subscriber_count'] ?? null)
      && (int)$context['subscriber_count'] >= 1;
  }

  private function generateUniqueCode(): string {
    for ($i = 0; $i < self::MAX_CODE_RETRIES; $i++) {
      $code = $this->randomCouponCodeGenerator->generate();
      if (!$this->wcHelper->wcGetCouponIdByCode($code)) {
        return $code;
      }
    }

    throw new \RuntimeException('Failed to generate a unique coupon code.');
  }

  private function getCouponDescription(array $context): string {
    return sprintf(
      // translators: %1$d is the MailPoet email ID.
      _x('Auto-generated coupon by MailPoet for email: %1$d', 'Coupon block code generation', 'mailpoet'),
      (int)($context['newsletter_id'] ?? 0)
    );
  }

  private function recordFailure(string $code, string $message, array $attrs, array $context): void {
    $safeContext = array_intersect_key($context, array_flip([
      'integration',
      'newsletter_id',
      'queue_id',
      'email_type',
      'is_real_send',
      'is_preview',
      'is_single_recipient',
      'subscriber_count',
      'mailpoet_is_automation',
    ]));

    $this->failureCollector->record($code, $message, $attrs, $safeContext);
  }
}
