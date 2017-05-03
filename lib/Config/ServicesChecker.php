<?php
namespace MailPoet\Config;

use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Util\Helpers;
use MailPoet\Util\License\License;
use MailPoet\WP\Notice as WPNotice;

if(!defined('ABSPATH')) exit;

class ServicesChecker {
  function checkMailPoetAPIKeyValid($display_error_notice = true) {
    if(!Bridge::isMPSendingServiceEnabled()) {
      return null;
    }

    $result = Setting::getValue(Bridge::API_KEY_STATE_SETTING_NAME);
    if(empty($result['state']) || $result['state'] == Bridge::MAILPOET_KEY_VALID) {
      return true;
    }

    if($result['state'] == Bridge::MAILPOET_KEY_INVALID) {
      $error = Helpers::replaceLinkTags(
        __('All sending is currently paused! Your key to send with MailPoet is invalid. [link]Visit MailPoet.com to purchase a key[/link]', 'mailpoet'),
        'https://account.mailpoet.com?s=' . Subscriber::getTotalSubscribers()
      );
      if($display_error_notice) {
        WPNotice::displayError($error);
      }
      return false;
    } elseif($result['state'] == Bridge::MAILPOET_KEY_EXPIRING
      && !empty($result['data']['expire_at'])
    ) {
      $date = date('Y-m-d', strtotime($result['data']['expire_at']));
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

  function isPremiumKeyValid($display_error_notice = true) {
    if(!Bridge::isPremiumKeySpecified()) {
      return null;
    }

    $premium_plugin_active = License::getLicense();
    $result = Setting::getValue(Bridge::PREMIUM_KEY_STATE_SETTING_NAME);
    if(empty($result['state']) || $result['state'] == Bridge::PREMIUM_KEY_VALID) {
      return true;
    }

    if($result['state'] == Bridge::PREMIUM_KEY_INVALID
      || $result['state'] == Bridge::PREMIUM_KEY_USED
    ) {
      $error = Helpers::replaceLinkTags(
        __('Warning! Your License Key is either invalid or expired. [link]Renew your License now[/link] to enjoy automatic updates and Premium support.', 'mailpoet'),
        'https://account.mailpoet.com'
      );
      if($premium_plugin_active && $display_error_notice) {
        WPNotice::displayError($error);
      }
      return false;
    } elseif($result['state'] == Bridge::PREMIUM_KEY_EXPIRING
      && !empty($result['data']['expire_at'])
    ) {
      $date = date('Y-m-d', strtotime($result['data']['expire_at']));
      $error = Helpers::replaceLinkTags(
        __('Your License Key is expiring! Don\'t forget to [link]renew your license[/link] by %s to keep enjoying automatic updates and Premium support.', 'mailpoet'),
        'https://account.mailpoet.com'
      );
      $error = sprintf($error, $date);
      if($premium_plugin_active && $display_error_notice) {
        WPNotice::displayWarning($error);
      }
      return true;
    }

    return false;
  }
}
