<?php

namespace MailPoet\Config;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class PrivacyPolicy {

  function init() {
    if (function_exists('wp_add_privacy_policy_content')) {
      wp_add_privacy_policy_content(__('MailPoet', 'mailpoet'), $this->getPrivacyPolicyContent());
    }
  }

  function getPrivacyPolicyContent() {
    return
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
      '</p>';
  }

}
