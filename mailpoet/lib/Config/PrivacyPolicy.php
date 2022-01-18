<?php

namespace MailPoet\Config;

use MailPoet\Util\Helpers;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class PrivacyPolicy {
  public function init() {
    if (function_exists('wp_add_privacy_policy_content')) {
      wp_add_privacy_policy_content(__('MailPoet', 'mailpoet'), $this->getPrivacyPolicyContent());
    }
  }

  public function getPrivacyPolicyContent() {
    $content = (
      '<h2>' .
        WPFunctions::get()->__('MailPoet newsletter & emails', 'mailpoet') .
      '</h2>' .
      '<p>' .
        WPFunctions::get()->__('If you have subscribed to our newsletter or if you are a member of our website (you can log in) or if you have purchased on our website, there is a good chance you will receive emails from us.', 'mailpoet') .
      '</p>' .
      '<p>' .
        WPFunctions::get()->__('We will only send you emails which you have signed up to receive, or which pertain to the services we provided to you.', 'mailpoet') .
      '</p>' .
      '<p>' .
        WPFunctions::get()->__('To send you emails, we use the name and email address you provide us. Our site also logs the IP address you used when you signed up for the service to prevent abuse of the system.', 'mailpoet') .
      '</p>' .
      '<p>' .
        Helpers::replaceLinkTags(
          WPFunctions::get()->__('This website can send emails through the [link]MailPoet sending service[/link]. This service allows us to track opens and clicks on our emails. We use this information to improve the content of our newsletters.', 'mailpoet'),
        'https://www.mailpoet.com/privacy-notice/',
          ['target' => '_blank']
        ) .
      '</p>' .
      '<p>' .
        WPFunctions::get()->__('No identifiable information is otherwise tracked outside this website except for the email address.', 'mailpoet') .
      '</p>'
    );
    $helper = new WooCommerceHelper();
    if ($helper->isWooCommerceActive()) {
      $content .= (
        '<p> ' .
        WPFunctions::get()->__('MailPoet creates and stores two cookies if you are using WooCommerce and MailPoet together. Those cookies are:', 'mailpoet') .
        '</p>' .
        '<p>' .
        sprintf(WPFunctions::get()->__('Cookie name: %s', 'mailpoet'), 'mailpoet_revenue_tracking' ) .
        '<br>' .
        sprintf(WPFunctions::get()->__('Cookie expiry: %s days.', 'mailpoet'), WPFunctions::get()->numberFormatI18n(14) ) .
        '<br>' .
        WPFunctions::get()->__('Cookie description: The purpose of this cookie is to track which newsletter sent from your website has acquired a click-through and a subsequent purchase in your WooCommerce store.', 'mailpoet') .
        '</p> ' .
        '<p>' .
        sprintf(WPFunctions::get()->__('Cookie name: %s', 'mailpoet'), 'mailpoet_abandoned_cart_tracking' ) .
        '<br>' .
        sprintf(WPFunctions::get()->__('Cookie expiry: %s days.', 'mailpoet'), WPFunctions::get()->numberFormatI18n(3650) ) .
        '<br>' .
        WPFunctions::get()->__('Cookie description: The purpose of this cookie is to track a user that has abandoned their cart in your WooCommerce store to then be able to send them an abandoned cart newsletter from MailPoet.', 'mailpoet') .
        '<br>' .
        '<br>' .
        WPFunctions::get()->__('Note: User must be opted-in and a confirmed subscriber.', 'mailpoet') .
        '</p>'
      );
    }
    return $content;
  }
}
