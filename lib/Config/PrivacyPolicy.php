<?php

namespace MailPoet\Config;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class PrivacyPolicy {

  function init() {
    if (function_exists('wp_add_privacy_policy_content')) {
      wp_add_privacy_policy_content(__('MailPoet', 'mailpoet'), $this->getPrivacyPolicyContent());
    }
  }

  function getPrivacyPolicyContent() {
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
    $helper = new WooCommerceHelper(new WPFunctions);
    if ($helper->isWooCommerceActive()) {
      $content .= (
        '<p> ' .
        WPFunctions::get()->__('MailPoet creates and stores two cookies if you are using WooCommerce and MailPoet together. Those cookies are:', 'mailpoet') .
        '</p>' .
        '<p>' .
        WPFunctions::get()->__('Cookie name: mailpoet_revenue_tracking', 'mailpoet') .
        '<br>' .
        WPFunctions::get()->__('Cookie expiry: 14 days.', 'mailpoet') .
        '<br>' .
        WPFunctions::get()->__('Cookie description: The purpose of this cookie is to track which newsletter sent from your website has acquired a click-through and a subsequent purchase in your WooCommerce store.', 'mailpoet') .
        '</p> ' .
        '<p>' .
        WPFunctions::get()->__('Cookie name: mailpoet_abandoned_cart_tracking', 'mailpoet') .
        '<br>' .
        WPFunctions::get()->__('Cookie expiry: 3,650 days.', 'mailpoet') .
        '<br>' .
        WPFunctions::get()->__('Cookie description: The purpose of this cookie is to track a user that has abandoned their cart in your WooCommerce store to then be able to send them an abandoned cart newsletter from MailPoet. <br>', 'mailpoet') .
        '<br>' .
        WPFunctions::get()->__('Note: User must be opted-in and a confirmed subscriber.', 'mailpoet') .
        '</p>'
      );
    }
    return $content;
  }

}
