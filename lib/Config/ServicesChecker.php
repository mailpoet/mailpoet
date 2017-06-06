<?php
namespace MailPoet\Config;

use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Util\Helpers;
use MailPoet\Util\License\License;
use MailPoet\WP\DateTime;
use MailPoet\WP\Notice as WPNotice;

if(!defined('ABSPATH')) exit;

class ServicesChecker {
  function isMailPoetAPIKeyValid($display_error_notice = true, $force_check = false) {
    if(!$force_check && !Bridge::isMPSendingServiceEnabled()) {
      return null;
    }

    $mss_key_specified = Bridge::isMSSKeySpecified();
    $mss_key = Setting::getValue(Bridge::API_KEY_STATE_SETTING_NAME);

    if(!$mss_key_specified
      || empty($mss_key['state'])
      || $mss_key['state'] == Bridge::MAILPOET_KEY_INVALID
    ) {
      if($display_error_notice) {
        $error = Helpers::replaceLinkTags(
          __('All sending is currently paused! Your key to send with MailPoet is invalid. [link]Visit MailPoet.com to purchase a key[/link]', 'mailpoet'),
          'https://account.mailpoet.com?s=' . Subscriber::getTotalSubscribers()
        );
        WPNotice::displayError($error);
      }
      return false;
    } elseif($mss_key['state'] == Bridge::MAILPOET_KEY_EXPIRING
      && !empty($mss_key['data']['expire_at'])
    ) {
      if($display_error_notice) {
        $date_time = new DateTime();
        $date = $date_time->formatDate(strtotime($mss_key['data']['expire_at']));
        $error = Helpers::replaceLinkTags(
          __('Your newsletters are awesome! Don\'t forget to [link]upgrade your MailPoet email plan[/link] by %s to keep sending them to your subscribers.', 'mailpoet'),
          'https://account.mailpoet.com?s=' . Subscriber::getTotalSubscribers()
        );
        $error = sprintf($error, $date);
        WPNotice::displayWarning($error);
      }
      return true;
    } elseif($mss_key['state'] == Bridge::MAILPOET_KEY_VALID) {
      return true;
    }

    return false;
  }

  function isPremiumKeyValid($display_error_notice = true) {
    $premium_key_specified = Bridge::isPremiumKeySpecified();
    $premium_plugin_active = License::getLicense();
    $premium_key = Setting::getValue(Bridge::PREMIUM_KEY_STATE_SETTING_NAME);

    if(!$premium_plugin_active) {
      $display_error_notice = false;
    }

    if(!$premium_key_specified
      || empty($premium_key['state'])
      || $premium_key['state'] === Bridge::PREMIUM_KEY_INVALID
      || $premium_key['state'] === Bridge::PREMIUM_KEY_ALREADY_USED
    ) {
      if($display_error_notice) {
        $error = Helpers::replaceLinkTags(
          __('Warning! Your License Key is either invalid or expired. [link]Renew your License now[/link] to enjoy automatic updates and Premium support.', 'mailpoet'),
          'https://account.mailpoet.com'
        );
        WPNotice::displayError($error);
      }
      return false;
    } elseif($premium_key['state'] === Bridge::PREMIUM_KEY_EXPIRING
      && !empty($premium_key['data']['expire_at'])
    ) {
      if($display_error_notice) {
        $date_time = new DateTime();
        $date = $date_time->formatDate(strtotime($premium_key['data']['expire_at']));
        $error = Helpers::replaceLinkTags(
          __('Your License Key is expiring! Don\'t forget to [link]renew your license[/link] by %s to keep enjoying automatic updates and Premium support.', 'mailpoet'),
          'https://account.mailpoet.com'
        );
        $error = sprintf($error, $date);
        WPNotice::displayWarning($error);
      }
      return true;
    } elseif($premium_key['state'] === Bridge::PREMIUM_KEY_VALID) {
      return true;
    }

    return false;
  }
}
