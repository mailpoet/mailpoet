<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class Customer implements CategoryInterface {
  /** @var WooCommerceHelper */
  private $wooCommerceHelper;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WooCommerceHelper $wooCommerceHelper,
    WPFunctions $wp
  ) {
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->wp = $wp;
  }

  public function process(
    array $shortcodeDetails,
    ?NewsletterEntity $newsletter = null,
    ?SubscriberEntity $subscriber = null,
    ?SendingQueueEntity $queue = null,
    string $content = '',
    bool $wpUserPreview = false
  ): ?string {
    // If WooCommerce is not active, return the original shortcode
    if (!$this->wooCommerceHelper->isWooCommerceActive()) {
      return $shortcodeDetails['shortcode'];
    }

    // If no subscriber, we can't determine the customer
    if (!($subscriber instanceof SubscriberEntity)) {
      return $shortcodeDetails['shortcode'];
    }

    // Get default value if specified
    $defaultValue = ($shortcodeDetails['action_argument'] === 'default') ?
      $shortcodeDetails['action_argument_value'] :
      '';

    // Get WP User ID from subscriber
    $wpUserId = $subscriber->getWpUserId();
    if (!$wpUserId) {
      return $defaultValue;
    }

    // Try to get WooCommerce customer
    $customer = null;
    try {
      // Check if wc_get_customer_id_from_user_id function exists
      if (function_exists('wc_get_customer_id_from_user_id')) {
        $customerId = wc_get_customer_id_from_user_id($wpUserId);
        if ($customerId) {
          $customer = new \WC_Customer($customerId);
        }
      } else {
        // Fallback to direct customer object creation
        $customer = new \WC_Customer($wpUserId);
      }
    } catch (\Exception $e) {
      // Customer not found or error, return default value
      return $defaultValue;
    }

    if (!$customer) {
      return $defaultValue;
    }

    // Process different customer fields
    switch ($shortcodeDetails['action']) {
      case 'first_name':
        return !empty($customer->get_first_name()) ? 
          htmlspecialchars($customer->get_first_name(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) : 
          $defaultValue;
      
      case 'last_name':
        return !empty($customer->get_last_name()) ? 
          htmlspecialchars($customer->get_last_name(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) : 
          $defaultValue;
      
      case 'email':
        return !empty($customer->get_email()) ? 
          $customer->get_email() : 
          $defaultValue;
          
      case 'username':
        $wpUser = $this->wp->getUserdata($wpUserId);
        return $wpUser ? $wpUser->user_login : $defaultValue; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        
      case 'display_name':
        $wpUser = $this->wp->getUserdata($wpUserId);
        return $wpUser ? $wpUser->display_name : $defaultValue; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        
      default:
        return $shortcodeDetails['shortcode'];
    }
  }
}
