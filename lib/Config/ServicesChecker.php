<?php
namespace MailPoet\Config;

use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Util\Helpers;
use MailPoet\WP\Notice as WPNotice;

if(!defined('ABSPATH')) exit;

class ServicesChecker {
  function checkMailPoetAPIKeyValid($display_error_notice = true) {
    if(!Bridge::isMPSendingServiceEnabled()) {
      return null;
    }

    $state = Setting::getValue(Bridge::API_KEY_STATE_SETTING_NAME);
    if(empty($state['code']) || $state['code'] == Bridge::MAILPOET_KEY_VALID) {
      return true;
    }

    if($state['code'] == Bridge::MAILPOET_KEY_INVALID) {
      $error = Helpers::replaceLinkTags(
        __('All sending is currently paused! Your key to send with MailPoet is invalid. [link]Visit MailPoet.com to purchase a key[/link]', 'mailpoet'),
        'https://account.mailpoet.com?s=' . Subscriber::getTotalSubscribers()
      );
      if($display_error_notice) {
        WPNotice::displayError($error);
      }
      return false;
    } elseif($state['code'] == Bridge::MAILPOET_KEY_EXPIRING
      && !empty($state['data']['expire_at'])
    ) {
      $date = date('Y-m-d', strtotime($state['data']['expire_at']));
      $error = Helpers::replaceLinkTags(
        __('Your newsletters are awesome! Don\'t forget to [link]upgrade your MailPoet email plan[/link] by %s to keep sending them to your subscribers.', 'mailpoet'),
        'https://account.mailpoet.com?s=' . Subscriber::getTotalSubscribers()
      );
      $error = sprintf($error, $date);
      if($display_error_notice) {
        WPNotice::displayWarning($error);
      }
      return true;
    }

    return true;
  }
}
