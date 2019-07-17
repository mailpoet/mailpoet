<?php
namespace MailPoet\Config;

use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\Util\License\License;
use MailPoet\WP\DateTime;
use MailPoet\WP\Notice as WPNotice;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;


class ServicesChecker {

  /** @var SettingsController */
  private $settings;

  public function __construct() {
    $this->settings = new SettingsController();
  }

  function isMailPoetAPIKeyValid($display_error_notice = true, $force_check = false) {
    if (!$force_check && !Bridge::isMPSendingServiceEnabled()) {
      return null;
    }

    $mss_key_specified = Bridge::isMSSKeySpecified();
    $mss_key = $this->settings->get(Bridge::API_KEY_STATE_SETTING_NAME);

    if (!$mss_key_specified
      || empty($mss_key['state'])
      || $mss_key['state'] == Bridge::KEY_INVALID
    ) {
      if ($display_error_notice) {
        $error = Helpers::replaceLinkTags(
          WPFunctions::get()->__('All sending is currently paused! Your key to send with MailPoet is invalid. [link]Visit MailPoet.com to purchase a key[/link]', 'mailpoet'),
          'https://account.mailpoet.com?s=' . Subscriber::getTotalSubscribers(),
          ['target' => '_blank']
        );
        WPNotice::displayError($error);
      }
      return false;
    } elseif ($mss_key['state'] == Bridge::KEY_EXPIRING
      && !empty($mss_key['data']['expire_at'])
    ) {
      if ($display_error_notice) {
        $date_time = new DateTime();
        $date = $date_time->formatDate(strtotime($mss_key['data']['expire_at']));
        $error = Helpers::replaceLinkTags(
          WPFunctions::get()->__("Your newsletters are awesome! Don't forget to [link]upgrade your MailPoet email plan[/link] by %s to keep sending them to your subscribers.", 'mailpoet'),
          'https://account.mailpoet.com?s=' . Subscriber::getTotalSubscribers(),
          ['target' => '_blank']
        );
        $error = sprintf($error, $date);
        WPNotice::displayWarning($error);
      }
      return true;
    } elseif ($mss_key['state'] == Bridge::KEY_VALID) {
      return true;
    }

    return false;
  }

  function isPremiumKeyValid($display_error_notice = true) {
    $premium_key_specified = Bridge::isPremiumKeySpecified();
    $premium_plugin_active = License::getLicense();
    $premium_key = $this->settings->get(Bridge::PREMIUM_KEY_STATE_SETTING_NAME);

    if (!$premium_plugin_active) {
      $display_error_notice = false;
    }

    if (!$premium_key_specified
      || empty($premium_key['state'])
      || $premium_key['state'] === Bridge::KEY_INVALID
      || $premium_key['state'] === Bridge::KEY_ALREADY_USED
    ) {
      if ($display_error_notice) {
        $error_string = WPFunctions::get()->__('[link1]Register[/link1] your copy of the MailPoet Premium plugin to receive access to automatic upgrades and support. Need a license key? [link2]Purchase one now.[/link2]', 'mailpoet');
        $error = Helpers::replaceLinkTags(
          $error_string,
          'admin.php?page=mailpoet-settings#premium',
          [],
          'link1'
        );
        $error = Helpers::replaceLinkTags(
          $error,
          'admin.php?page=mailpoet-premium',
          [],
          'link2'
        );
        WPNotice::displayWarning($error);
      }
      return false;
    } elseif ($premium_key['state'] === Bridge::KEY_EXPIRING
      && !empty($premium_key['data']['expire_at'])
    ) {
      if ($display_error_notice) {
        $date_time = new DateTime();
        $date = $date_time->formatDate(strtotime($premium_key['data']['expire_at']));
        $error = Helpers::replaceLinkTags(
          WPFunctions::get()->__("Your License Key for MailPoet is expiring! Don't forget to [link]renew your license[/link] by %s to keep enjoying automatic updates and Premium support.", 'mailpoet'),
          'https://account.mailpoet.com',
          ['target' => '_blank']
        );
        $error = sprintf($error, $date);
        WPNotice::displayWarning($error);
      }
      return true;
    } elseif ($premium_key['state'] === Bridge::KEY_VALID) {
      return true;
    }

    return false;
  }
}
